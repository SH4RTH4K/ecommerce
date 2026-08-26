<?php

namespace App\Services;

use App\ProductCodeConfiguration;
use App\ProductCodeHistory;
use App\ProductCodeRegenerationBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CodeRegenerationService
{
    private const TYPES = ['company','category','subcategory','brand','series','product'];

    public function preview(ProductCodeConfiguration $configuration, string $mode = 'UPDATE_ALL', array $selected = [], bool $preserveSequence = false): array
    {
        $type = strtolower((string) $configuration->code_type);
        if (! in_array($type, self::TYPES, true)) throw ValidationException::withMessages(['code_type' => 'This code type cannot be regenerated.']);
        $rows = $this->records($type, $mode, $selected);
        $items = [];
        $plannedCodes = [];
        $reservedSequences = [];
        // These records are being replaced together, so their old codes must
        // not block the fresh serial range during the dry run.
        $regeneratingIds = $rows->pluck('entity_id')->map(fn ($id) => (int) $id)->all();
        $nextSequence = max(1, (int) $configuration->sequence_start);
        $hasSequence = $this->hasSequenceComponent($configuration);
        $preservedSequenceCount = 0;
        $allocatedSequenceCount = 0;

        foreach ($rows as $row) {
            $old = trim((string) $row->current_code);
            $oldSequence = $preserveSequence && preg_match('/(\d+)$/', $old, $match) ? max(1, (int) $match[1]) : null;
            $sequence = null;
            $sequenceAction = 'Not used';
            if ($hasSequence) {
                if ($oldSequence !== null && ! isset($reservedSequences[$oldSequence])) {
                    $sequence = $oldSequence;
                    $sequenceAction = 'Preserved';
                    $preservedSequenceCount++;
                } else {
                    $sequence = $this->nextAvailableSequence($nextSequence, $reservedSequences);
                    $sequenceAction = 'Allocated';
                    $allocatedSequenceCount++;
                }
                $reservedSequences[$sequence] = true;
                $nextSequence = max($nextSequence, $sequence + 1);
            }

            $context = $this->context($type, $row);
            try {
                $rendered = $hasSequence
                    ? app(ProductCodeGenerator::class)->previewWithSequence($context, (int) $sequence, $configuration)
                    : app(ProductCodeGenerator::class)->preview($context, $configuration);
                $new = trim((string) ($rendered['preview'] ?? ''));
                $error = $new === '' ? 'Generator returned an empty code.' : null;
            } catch (\Throwable $exception) {
                $new = '';
                $error = $exception->getMessage();
            }

            $attempts = 0;
            while (! $error && $hasSequence && $this->hasCodeConflict($type, $new, (int) $row->entity_id, $plannedCodes, $regeneratingIds)) {
                if (++$attempts > 10000) {
                    $error = 'No unused sequence number could be allocated for this record.';
                    break;
                }
                $sequence = $this->nextAvailableSequence($nextSequence, $reservedSequences);
                $reservedSequences[$sequence] = true;
                $nextSequence = $sequence + 1;
                if ($sequenceAction !== 'Allocated') {
                    $preservedSequenceCount--;
                    $allocatedSequenceCount++;
                    $sequenceAction = 'Allocated to avoid a duplicate';
                }
                try {
                    $new = trim((string) (app(ProductCodeGenerator::class)->previewWithSequence($context, $sequence, $configuration)['preview'] ?? ''));
                    if ($new === '') $error = 'Generator returned an empty code.';
                } catch (\Throwable $exception) {
                    $new = '';
                    $error = $exception->getMessage();
                }
            }

            if (! $error && ! $hasSequence && $this->hasCodeConflict($type, $new, (int) $row->entity_id, $plannedCodes, $regeneratingIds)) {
                $error = 'Duplicate proposed code. Add a Numeric Sequence component to this format, then preview again.';
            }
            if (! $error && $new !== '') $plannedCodes[$new] = (int) $row->entity_id;
            $items[] = ['entity_type' => $type, 'entity_id' => (int) $row->entity_id, 'name' => (string) $row->entity_name, 'old_code' => $old, 'new_code' => $new, 'sequence_number' => $sequence, 'sequence_action' => $sequenceAction, 'status' => $error ? 'CONFLICT' : 'READY', 'error' => $error];
        }
        return ['code_type' => $type, 'configuration_id' => $configuration->id, 'mode' => $mode, 'items' => $items, 'total' => count($items), 'ready' => count(array_filter($items, fn ($i) => $i['status'] === 'READY')), 'conflicts' => count(array_filter($items, fn ($i) => $i['status'] !== 'READY')), 'sequence_preserved' => $preservedSequenceCount, 'sequence_allocated' => $allocatedSequenceCount];
    }

    public function apply(ProductCodeConfiguration $configuration, array $preview, string $reason, int $adminId, bool $allOrNothing = true): ProductCodeRegenerationBatch
    {
        if (trim($reason) === '') throw ValidationException::withMessages(['reason' => 'A reason is required for code regeneration.']);
        if ($allOrNothing && ($preview['conflicts'] ?? 0) > 0) throw ValidationException::withMessages(['preview' => 'Resolve all conflicts before applying this regeneration.']);
        $changedItems = array_values(array_filter($preview['items'], static fn ($item) => $item['status'] === 'READY' && $item['old_code'] !== $item['new_code']));

        return DB::transaction(function () use ($configuration, $preview, $reason, $adminId, $changedItems) {
            // Old versions created the batch before the transaction. Mark those
            // abandoned records as failed before attempting a new controlled run.
            ProductCodeRegenerationBatch::query()
                ->where('configuration_id', $configuration->id)
                ->where('status', 'RUNNING')
                ->where('started_at', '<', now()->subMinutes(5))
                ->update(['status' => 'FAILED', 'failed_count' => DB::raw('total_records'), 'completed_at' => now(), 'updated_at' => now()]);

            $batch = ProductCodeRegenerationBatch::create([
                'code_type' => $preview['code_type'],
                'configuration_id' => $configuration->id,
                'configuration_version' => (int) ($configuration->version ?: 1),
                'mode' => $preview['mode'],
                'preserve_sequence' => false,
                'total_records' => $preview['total'],
                'skipped_count' => $preview['total'] - count($changedItems),
                'initiated_by' => $adminId,
                'status' => 'RUNNING',
                'reason' => $reason,
                'started_at' => now(),
            ]);

            // A unique code may be held by another record that is also being
            // regenerated. Move every changing record out of the final code
            // namespace first, then write the proposed final codes.
            foreach ($changedItems as $item) {
                $this->updateEntity($item['entity_type'], $item['entity_id'], $this->temporaryCode($item['entity_type'], $item['entity_id'], $batch->id));
            }

            foreach ($changedItems as $item) {
                $this->updateEntity($item['entity_type'], $item['entity_id'], $item['new_code']);
                ProductCodeHistory::create(['configuration_id' => $configuration->id, 'configuration_version' => (int) ($configuration->version ?: 1), 'product_id' => $item['entity_type'] === 'product' ? $item['entity_id'] : null, 'entity_type' => $item['entity_type'], 'entity_id' => $item['entity_id'], 'entity_name' => $item['name'], 'old_code' => $item['old_code'], 'new_code' => $item['new_code'], 'reason' => $reason, 'change_type' => $preview['mode'] === 'UPDATE_SELECTED' ? 'SELECTED_REGENERATION' : 'CONFIGURATION_REGENERATION', 'batch_id' => $batch->id, 'changed_by' => $adminId, 'changed_at' => now()]);
            }
            $batch->update(['success_count' => count($changedItems), 'status' => 'COMPLETED', 'completed_at' => now()]);
            return $batch->fresh();
        });
    }

    private function records(string $type, string $mode, array $selected)
    {
        $map = ['company'=>['table'=>'companies','id'=>'id','name'=>'name','code'=>'company_code'],'category'=>['table'=>'category','id'=>'category_id','name'=>'category_name','code'=>'category_code'],'subcategory'=>['table'=>'sub_category','id'=>'sub_category_id','name'=>'sub_category_name','code'=>'subcategory_code'],'brand'=>['table'=>'manufacturer','id'=>'manufacturer_id','name'=>'manufacturer_name','code'=>'brand_code'],'series'=>['table'=>'product_series','id'=>'id','name'=>'name','code'=>'series_code'],'product'=>['table'=>'product','id'=>'id','name'=>'product_name','code'=>'product_code']][$type];
        return DB::table($map['table'])->whereNull($map['table'].'.deleted_at')->when($mode === 'UPDATE_SELECTED' && $selected !== [], fn ($q) => $q->whereIn($map['id'], $selected))->orderBy($map['id'])->get([$map['id'].' as entity_id', $map['name'].' as entity_name', $map['code'].' as current_code']);
    }

    private function context(string $type, $row): array
    {
        $base = ['code_type' => $type, 'name' => $row->entity_name, 'entity_name' => $row->entity_name];
        if ($type === 'company') $base['company_id'] = $row->entity_id;
        if ($type === 'category') $base['category_id'] = $row->entity_id;
        if ($type === 'subcategory') { $base['subcategory_id'] = $row->entity_id; $base['category_id'] = DB::table('sub_category')->where('sub_category_id',$row->entity_id)->value('category_id'); }
        if ($type === 'brand') $base['manufacturer_id'] = $row->entity_id;
        if ($type === 'series') { $base['series_id'] = $row->entity_id; $base['manufacturer_id'] = DB::table('product_series')->where('id',$row->entity_id)->value('manufacturer_id'); }
        if ($type === 'product') { $p=DB::table('product')->where('id',$row->entity_id)->first(); $base=array_merge($base,['category_id'=>$p->category_id,'subcategory_id'=>$p->sub_category,'manufacturer_id'=>$p->manufacturer_id,'series_id'=>$p->product_series_id,'company_id'=>$p->company_id,'branch_id'=>$p->branch_id,'product_name'=>$p->product_name]); }
        return $base;
    }

    private function updateEntity(string $type, int $id, string $code): void
    {
        $map = ['company'=>['companies','id','company_code'],'category'=>['category','category_id','category_code'],'subcategory'=>['sub_category','sub_category_id','subcategory_code'],'brand'=>['manufacturer','manufacturer_id','brand_code'],'series'=>['product_series','id','series_code'],'product'=>['product','id','product_code']][$type];
        $data=[$map[2]=>$code,'updated_at'=>now()]; if ($type === 'product') $data['sku']=$code; DB::table($map[0])->where($map[1],$id)->update($data);
    }

    private function temporaryCode(string $type, int $entityId, int $batchId): string
    {
        $map = ['company'=>['companies','id','company_code'],'category'=>['category','category_id','category_code'],'subcategory'=>['sub_category','sub_category_id','subcategory_code'],'brand'=>['manufacturer','manufacturer_id','brand_code'],'series'=>['product_series','id','series_code'],'product'=>['product','id','product_code']][$type];
        $prefix = 'PCR'.base_convert((string) $batchId, 10, 36).'-'.base_convert((string) $entityId, 10, 36);
        $candidate = $prefix;
        $attempt = 0;

        while (DB::table($map[0])->where($map[2], $candidate)->exists()) {
            $attempt++;
            $suffix = '-'.$attempt;
            $candidate = substr($prefix, 0, 30 - strlen($suffix)).$suffix;
        }

        return $candidate;
    }

    private function conflict(string $type, string $code, int $id, array $regeneratingIds = []): bool
    {
        $map = ['company'=>['companies','id','company_code'],'category'=>['category','category_id','category_code'],'subcategory'=>['sub_category','sub_category_id','subcategory_code'],'brand'=>['manufacturer','manufacturer_id','brand_code'],'series'=>['product_series','id','series_code'],'product'=>['product','id','product_code']][$type];
        return DB::table($map[0])->where($map[2],$code)->whereNotIn($map[1], array_values(array_unique(array_map('intval', $regeneratingIds))))->exists();
    }

    private function hasSequenceComponent(ProductCodeConfiguration $configuration): bool
    {
        return $configuration->components->contains(function ($component) {
            return strtolower((string) $component->component_type) === 'sequence';
        });
    }

    private function nextAvailableSequence(int $candidate, array $reservedSequences): int
    {
        while (isset($reservedSequences[$candidate])) $candidate++;
        return $candidate;
    }

    private function hasCodeConflict(string $type, string $code, int $entityId, array $plannedCodes, array $regeneratingIds = []): bool
    {
        return $code === '' || isset($plannedCodes[$code]) || $this->conflict($type, $code, $entityId, $regeneratingIds);
    }
}
