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

    public function preview(ProductCodeConfiguration $configuration, string $mode = 'UPDATE_ALL', array $selected = [], bool $preserveSequence = true): array
    {
        $type = strtolower((string) $configuration->code_type);
        if (! in_array($type, self::TYPES, true)) throw ValidationException::withMessages(['code_type' => 'This code type cannot be regenerated.']);
        $rows = $this->records($type, $mode, $selected);
        $items = [];
        $proposed = [];
        foreach ($rows as $row) {
            $old = trim((string) $row->current_code);
            if ($old === '') continue;
            $sequence = $preserveSequence && preg_match('/(\d+)$/', $old, $match) ? (int) $match[1] : null;
            $context = $this->context($type, $row);
            try {
                $rendered = $sequence !== null
                    ? app(ProductCodeGenerator::class)->previewWithSequence($context, $sequence, $configuration)
                    : app(ProductCodeGenerator::class)->preview($context, $configuration);
                $new = trim((string) ($rendered['preview'] ?? ''));
                $error = $new === '' ? 'Generator returned an empty code.' : null;
            } catch (\Throwable $exception) {
                $new = '';
                $error = $exception->getMessage();
            }
            $conflict = $error ? false : $this->conflict($type, $new, (int) $row->entity_id);
            if ($conflict) $error = 'Proposed code already belongs to another record.';
            if ($new !== '') $proposed[$new] = ($proposed[$new] ?? 0) + 1;
            $items[] = ['entity_type' => $type, 'entity_id' => (int) $row->entity_id, 'name' => (string) $row->entity_name, 'old_code' => $old, 'new_code' => $new, 'sequence_number' => $sequence, 'status' => $error ? 'CONFLICT' : 'READY', 'error' => $error];
        }
        foreach ($items as &$item) if ($item['new_code'] !== '' && ($proposed[$item['new_code']] ?? 0) > 1) { $item['status'] = 'CONFLICT'; $item['error'] = 'Duplicate proposed code in this preview.'; }
        unset($item);
        return ['code_type' => $type, 'configuration_id' => $configuration->id, 'mode' => $mode, 'items' => $items, 'total' => count($items), 'ready' => count(array_filter($items, fn ($i) => $i['status'] === 'READY')), 'conflicts' => count(array_filter($items, fn ($i) => $i['status'] !== 'READY'))];
    }

    public function apply(ProductCodeConfiguration $configuration, array $preview, string $reason, int $adminId, bool $allOrNothing = true): ProductCodeRegenerationBatch
    {
        if (trim($reason) === '') throw ValidationException::withMessages(['reason' => 'A reason is required for code regeneration.']);
        if ($allOrNothing && ($preview['conflicts'] ?? 0) > 0) throw ValidationException::withMessages(['preview' => 'Resolve all conflicts before applying this regeneration.']);
        $batch = ProductCodeRegenerationBatch::create(['code_type' => $preview['code_type'], 'configuration_id' => $configuration->id, 'configuration_version' => (int) ($configuration->version ?: 1), 'mode' => $preview['mode'], 'preserve_sequence' => true, 'total_records' => $preview['total'], 'skipped_count' => $preview['total'] - $preview['ready'], 'initiated_by' => $adminId, 'status' => 'RUNNING', 'reason' => $reason, 'started_at' => now()]);
        DB::transaction(function () use ($configuration, $preview, $reason, $adminId, $batch) {
            foreach ($preview['items'] as $item) {
                if ($item['status'] !== 'READY' || $item['old_code'] === $item['new_code']) continue;
                $this->updateEntity($item['entity_type'], $item['entity_id'], $item['new_code']);
                ProductCodeHistory::create(['configuration_id' => $configuration->id, 'configuration_version' => (int) ($configuration->version ?: 1), 'product_id' => $item['entity_type'] === 'product' ? $item['entity_id'] : null, 'entity_type' => $item['entity_type'], 'entity_id' => $item['entity_id'], 'entity_name' => $item['name'], 'old_code' => $item['old_code'], 'new_code' => $item['new_code'], 'reason' => $reason, 'change_type' => $preview['mode'] === 'UPDATE_SELECTED' ? 'SELECTED_REGENERATION' : 'CONFIGURATION_REGENERATION', 'batch_id' => $batch->id, 'changed_by' => $adminId, 'changed_at' => now()]);
            }
            $batch->update(['success_count' => $preview['ready'], 'status' => 'COMPLETED', 'completed_at' => now()]);
        });
        return $batch->fresh();
    }

    private function records(string $type, string $mode, array $selected)
    {
        $map = ['company'=>['table'=>'companies','id'=>'id','name'=>'name','code'=>'company_code'],'category'=>['table'=>'category','id'=>'category_id','name'=>'category_name','code'=>'category_code'],'subcategory'=>['table'=>'sub_category','id'=>'sub_category_id','name'=>'sub_category_name','code'=>'subcategory_code'],'brand'=>['table'=>'manufacturer','id'=>'manufacturer_id','name'=>'manufacturer_name','code'=>'brand_code'],'series'=>['table'=>'product_series','id'=>'id','name'=>'name','code'=>'series_code'],'product'=>['table'=>'product','id'=>'id','name'=>'product_name','code'=>'product_code']][$type];
        return DB::table($map['table'])->whereNull($map['table'].'.deleted_at')->when($mode === 'UPDATE_SELECTED' && $selected !== [], fn ($q) => $q->whereIn($map['id'], $selected))->get([$map['id'].' as entity_id', $map['name'].' as entity_name', $map['code'].' as current_code']);
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

    private function conflict(string $type, string $code, int $id): bool
    {
        $map = ['company'=>['companies','id','company_code'],'category'=>['category','category_id','category_code'],'subcategory'=>['sub_category','sub_category_id','subcategory_code'],'brand'=>['manufacturer','manufacturer_id','brand_code'],'series'=>['product_series','id','series_code'],'product'=>['product','id','product_code']][$type];
        return DB::table($map[0])->where($map[2],$code)->where($map[1],'<>',$id)->exists();
    }
}
