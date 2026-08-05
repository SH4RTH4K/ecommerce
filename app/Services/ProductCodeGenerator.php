<?php

namespace App\Services;

use App\Category;
use App\Company;
use App\InventoryLocation;
use App\Manufacturer;
use App\Product;
use App\ProductCodeComponent;
use App\ProductCodeConfiguration;
use App\ProductCodeHistory;
use App\ProductCodeSequence;
use App\ProductSeries;
use App\SubCategory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductCodeGenerator
{
    public function resolveConfiguration(array $context, bool $allowInactive = false): ?ProductCodeConfiguration
    {
        $companyId = $this->normalizeNullableId($context['company_id'] ?? null);
        $branchId = $this->normalizeNullableId($context['branch_id'] ?? null);

        $query = ProductCodeConfiguration::query()
            ->when(! $allowInactive, static function ($query) {
                $query->where('is_active', 1);
            })
            ->where(function ($query) use ($companyId, $branchId) {
                $query->whereNull('company_id')->whereNull('branch_id');

                if ($companyId !== null) {
                    $query->orWhere(function ($query) use ($companyId) {
                        $query->where('company_id', $companyId)->whereNull('branch_id');
                    });
                }

                if ($branchId !== null) {
                    $query->orWhere(function ($query) use ($companyId, $branchId) {
                        $query->where('branch_id', $branchId);

                        if ($companyId !== null) {
                            $query->where(function ($branchQuery) use ($companyId) {
                                $branchQuery->whereNull('company_id')->orWhere('company_id', $companyId);
                            });
                        }
                    });
                }
            })
            ->orderByDesc('is_active')
            ->orderByRaw('CASE WHEN branch_id IS NOT NULL THEN 2 WHEN company_id IS NOT NULL THEN 1 ELSE 0 END DESC')
            ->orderByDesc('id');

        foreach ($query->get() as $configuration) {
            if ($this->configurationMatchesContext($configuration, $context)) {
                return $configuration->loadMissing(['components', 'company', 'branch']);
            }
        }

        return null;
    }

    public function preview(array $context, ?ProductCodeConfiguration $configuration = null): array
    {
        $configuration = $configuration ? $configuration->loadMissing(['components', 'company', 'branch']) : $this->resolveConfiguration($context);
        if (! $configuration) {
            throw ValidationException::withMessages([
                'configuration' => 'No active product code configuration is available.',
            ]);
        }

        return $this->render($configuration, $context, $this->previewSequenceNumber($configuration, $context));
    }

    public function allocate(array $context, ?ProductCodeConfiguration $configuration = null): array
    {
        $configuration = $configuration ? $configuration->loadMissing(['components', 'company', 'branch']) : $this->resolveConfiguration($context);
        if (! $configuration) {
            throw ValidationException::withMessages([
                'configuration' => 'No active product code configuration is available.',
            ]);
        }

        $lastException = null;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return DB::transaction(function () use ($configuration, $context) {
                    for ($sequenceAttempt = 0; $sequenceAttempt < 20; $sequenceAttempt++) {
                        $allocation = $this->lockAndIncrementSequence($configuration, $context);
                        $rendered = $this->render($configuration, $context, $allocation['sequence_number']);

                        if (! $this->productCodeIsTaken($rendered['preview'])) {
                            return [
                                'configuration' => $configuration,
                                'sequence_number' => $allocation['sequence_number'],
                                'sequence_id' => $allocation['sequence_id'],
                                'product_code' => $rendered['preview'],
                                'details' => $rendered,
                            ];
                        }
                    }

                    throw ValidationException::withMessages([
                        'product_code' => 'Unable to generate a unique product code.',
                    ]);
                });
            } catch (QueryException $exception) {
                if (! $this->isDuplicateKeyException($exception)) {
                    throw $exception;
                }

                $lastException = $exception;
            }
        }

        throw $lastException ?: ValidationException::withMessages([
            'product_code' => 'Unable to generate a unique product code.',
        ]);
    }

    public function generateVariantSku(string $productCode, array $variant, array &$used = []): string
    {
        $baseParts = [];
        foreach (['storage', 'ram', 'color', 'size', 'material', 'variant_code', 'code'] as $field) {
            $value = trim((string) Arr::get($variant, $field, ''));
            if ($value !== '') {
                $baseParts[] = $value;
            }
        }

        $fallback = trim((string) Arr::get($variant, 'name', ''));
        if ($fallback === '' && $baseParts === []) {
            $fallback = 'VARIANT';
        }

        $suffixSource = $baseParts !== [] ? implode('-', $baseParts) : $fallback;
        $suffix = $this->sanitizeToken($suffixSource);
        if ($suffix === '') {
            $suffix = 'VARIANT';
        }

        $candidate = $this->normalizeFinalCode($productCode.'-'.$suffix);
        $index = 2;
        while ($candidate === '' || in_array($candidate, $used, true) || $this->productCodeIsTaken($candidate)) {
            $candidate = $this->normalizeFinalCode($productCode.'-'.$suffix.'-'.$index);
            $index++;
        }

        $used[] = $candidate;

        return $candidate;
    }

    public function snapshot(ProductCodeConfiguration $configuration): array
    {
        return [
            'id' => $configuration->id,
            'name' => $configuration->name,
            'company_id' => $configuration->company_id,
            'branch_id' => $configuration->branch_id,
            'auto_generate' => (bool) $configuration->auto_generate,
            'template' => $this->templateFromComponents($configuration->components->all()),
            'separator' => $configuration->separator,
            'sequence_scope' => $configuration->sequence_scope,
            'sequence_length' => (int) $configuration->sequence_length,
            'sequence_start' => (int) $configuration->sequence_start,
            'reset_rule' => $configuration->reset_rule,
            'strict_mode' => (bool) $configuration->strict_mode,
            'skip_empty_components' => (bool) $configuration->skip_empty_components,
            'allow_manual_override' => (bool) $configuration->allow_manual_override,
            'allow_regeneration' => (bool) $configuration->allow_regeneration,
            'effective_from' => optional($configuration->effective_from)->toDateTimeString(),
            'effective_to' => optional($configuration->effective_to)->toDateTimeString(),
            'is_active' => (bool) $configuration->is_active,
            'components' => $configuration->components->map(function (ProductCodeComponent $component) {
                return [
                    'id' => $component->id,
                    'component_type' => $component->component_type,
                    'position' => (int) $component->position,
                    'static_value' => $component->static_value,
                    'format_options' => $component->format_options,
                    'is_required' => (bool) $component->is_required,
                ];
            })->values()->all(),
        ];
    }

    public function recordHistory(ProductCodeConfiguration $configuration, Product $product, ?string $oldCode, string $newCode, ?string $reason, ?int $changedBy = null): void
    {
        ProductCodeHistory::create([
            'configuration_id' => $configuration->id,
            'product_id' => $product->id,
            'old_code' => $oldCode,
            'new_code' => $newCode,
            'reason' => $reason,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }

    private function lockAndIncrementSequence(ProductCodeConfiguration $configuration, array $context): array
    {
        $sequenceScope = (string) ($configuration->sequence_scope ?: config('product_code.default_sequence_scope', 'global'));
        $periodKey = $this->periodKey($configuration, $context);
        $scopeSignature = $this->scopeSignature($configuration, $sequenceScope, $periodKey, $context);
        $companyId = $this->normalizeNullableId($context['company_id'] ?? null);
        $branchId = $this->normalizeNullableId($context['branch_id'] ?? null);
        $categoryId = $this->normalizeNullableId($context['category_id'] ?? null);
        $subCategoryId = $this->normalizeNullableId($context['subcategory_id'] ?? ($context['sub_category_id'] ?? null));
        $brandId = $this->normalizeNullableId($context['manufacturer_id'] ?? null);
        $seriesId = $this->normalizeNullableId($context['series_id'] ?? ($context['product_series_id'] ?? null));

        $sequence = ProductCodeSequence::where('configuration_id', $configuration->id)
            ->where('scope_signature', $scopeSignature)
            ->where('period_key', $periodKey)
            ->lockForUpdate()
            ->first();

        $start = max(1, (int) $configuration->sequence_start);
        $nextNumber = $sequence ? ((int) $sequence->last_number + 1) : $start;

        if ($sequence) {
            $sequence->last_number = $nextNumber;
            $sequence->save();
        } else {
            try {
                $sequence = ProductCodeSequence::create([
                    'configuration_id' => $configuration->id,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'category_id' => $categoryId,
                    'subcategory_id' => $subCategoryId,
                    'brand_id' => $brandId,
                    'series_id' => $seriesId,
                    'sequence_scope' => $sequenceScope,
                    'period_key' => $periodKey,
                    'scope_signature' => $scopeSignature,
                    'last_number' => $nextNumber,
                ]);
            } catch (QueryException $exception) {
                if (! $this->isDuplicateKeyException($exception)) {
                    throw $exception;
                }

                $sequence = ProductCodeSequence::where('configuration_id', $configuration->id)
                    ->where('scope_signature', $scopeSignature)
                    ->where('period_key', $periodKey)
                    ->lockForUpdate()
                    ->firstOrFail();
                $nextNumber = ((int) $sequence->last_number) + 1;
                $sequence->last_number = $nextNumber;
                $sequence->save();
            }
        }

        return [
            'sequence_id' => $sequence->id,
            'sequence_number' => $nextNumber,
            'period_key' => $periodKey,
            'scope_signature' => $scopeSignature,
        ];
    }

    private function previewSequenceNumber(ProductCodeConfiguration $configuration, array $context): int
    {
        $sequenceScope = (string) ($configuration->sequence_scope ?: config('product_code.default_sequence_scope', 'global'));
        $periodKey = $this->periodKey($configuration, $context);
        $scopeSignature = $this->scopeSignature($configuration, $sequenceScope, $periodKey, $context);
        $start = max(1, (int) $configuration->sequence_start);

        $sequence = ProductCodeSequence::where('configuration_id', $configuration->id)
            ->where('scope_signature', $scopeSignature)
            ->where('period_key', $periodKey)
            ->first();

        return $sequence ? ((int) $sequence->last_number + 1) : $start;
    }

    private function render(ProductCodeConfiguration $configuration, array $context, int $sequenceNumber): array
    {
        $resolved = $this->resolveTokens($configuration, $context, $sequenceNumber);
        $separator = (string) ($configuration->separator ?? config('product_code.default_separator', '-'));
        $parts = [];
        $missing = [];

        foreach ($configuration->components as $component) {
            $type = strtolower(trim((string) $component->component_type));
            $value = $resolved[$type] ?? '';

            if ($value === '' || $value === null) {
                if ((bool) $configuration->strict_mode && (bool) $component->is_required) {
                    $missing[] = $this->missingComponentMessage($type);
                }
                if ((bool) $configuration->skip_empty_components) {
                    continue;
                }
            }

            $parts[] = $value === null ? '' : (string) $value;
        }

        if ($missing !== []) {
            throw ValidationException::withMessages(['product_code' => implode(' ', array_unique($missing))]);
        }

        $preview = $this->normalizeFinalCode(implode($separator, $parts));
        if ($preview === '') {
            throw ValidationException::withMessages(['product_code' => 'Unable to generate a product code from the active configuration.']);
        }

        return [
            'preview' => $preview,
            'values' => $resolved,
        ];
    }

    private function resolveTokens(ProductCodeConfiguration $configuration, array $context, int $sequenceNumber): array
    {
        $company = $this->resolveCompany($context);
        $branch = $this->resolveBranch($context);
        $category = $this->resolveCategory($context);
        $subCategory = $this->resolveSubCategory($context);
        $brand = $this->resolveBrand($context);
        $series = $this->resolveSeries($context);
        $productType = $this->resolveProductType($context);
        $sequenceLength = max(1, (int) ($configuration->sequence_length ?: config('product_code.default_sequence_length', 6)));
        $sequence = $sequenceNumber > 0 ? str_pad((string) $sequenceNumber, $sequenceLength, '0', STR_PAD_LEFT) : str_repeat('0', $sequenceLength);
        $date = now();
        $separator = (string) ($configuration->separator ?? config('product_code.default_separator', '-'));
        $customPrefix = trim((string) ($context['custom_prefix'] ?? ''));
        $customSuffix = trim((string) ($context['custom_suffix'] ?? ''));
        $variant = trim((string) ($context['variant_code'] ?? ($context['variant'] ?? '')));
        if ($variant === '' && is_array($context['variant'] ?? null)) {
            $variant = trim((string) Arr::get($context['variant'], 'sku', Arr::get($context['variant'], 'name', '')));
        }

        return [
            'company' => $company->company_code ?? '',
            'branch' => $branch->branch_code ?? '',
            'category' => $category->category_code ?? '',
            'subcategory' => $subCategory->subcategory_code ?? '',
            'brand' => $brand->brand_code ?? '',
            'series' => $series->series_code ?? '',
            'product_type' => $productType,
            'year' => $date->format('Y'),
            'year_short' => $date->format('y'),
            'month' => $date->format('m'),
            'day' => $date->format('d'),
            'date' => $date->format('Ymd'),
            'sequence' => $sequence,
            'variant' => $this->sanitizeToken($variant),
            'custom_prefix' => $this->sanitizeToken($customPrefix),
            'custom_suffix' => $this->sanitizeToken($customSuffix),
        ];
    }

    private function resolveCompany(array $context): ?Company
    {
        $companyId = $this->normalizeNullableId($context['company_id'] ?? null);
        if ($companyId === null) {
            $brand = $this->resolveBrand($context);
            if ($brand && $brand->company_id) {
                return Company::find($brand->company_id);
            }
            return null;
        }

        return Company::find($companyId);
    }

    private function resolveBranch(array $context): ?InventoryLocation
    {
        $branchId = $this->normalizeNullableId($context['branch_id'] ?? null);
        if ($branchId === null) {
            return null;
        }

        return InventoryLocation::find($branchId);
    }

    private function resolveCategory(array $context): ?Category
    {
        $categoryId = $this->normalizeNullableId($context['category_id'] ?? null);
        return $categoryId ? Category::find($categoryId) : null;
    }

    private function resolveSubCategory(array $context): ?SubCategory
    {
        $subCategoryId = $this->normalizeNullableId($context['subcategory_id'] ?? ($context['sub_category_id'] ?? null));
        return $subCategoryId ? SubCategory::find($subCategoryId) : null;
    }

    private function resolveBrand(array $context): ?Manufacturer
    {
        $brandId = $this->normalizeNullableId($context['manufacturer_id'] ?? null);
        return $brandId ? Manufacturer::find($brandId) : null;
    }

    private function resolveSeries(array $context): ?ProductSeries
    {
        $seriesId = $this->normalizeNullableId($context['series_id'] ?? ($context['product_series_id'] ?? null));
        return $seriesId ? ProductSeries::find($seriesId) : null;
    }

    private function resolveProductType(array $context): string
    {
        $type = strtolower(trim((string) ($context['industry_profile'] ?? '')));
        $map = (array) config('product_code.product_type_map', []);

        if ($type !== '' && isset($map[$type])) {
            return (string) $map[$type];
        }

        $explicit = trim((string) ($context['product_type_code'] ?? ''));
        return $this->sanitizeToken($explicit);
    }

    private function configurationMatchesContext(ProductCodeConfiguration $configuration, array $context): bool
    {
        $companyId = $this->normalizeNullableId($context['company_id'] ?? null);
        $branchId = $this->normalizeNullableId($context['branch_id'] ?? null);

        if (! $this->isConfigurationActiveForDate($configuration)) {
            return false;
        }

        if ($configuration->branch_id !== null && (int) $configuration->branch_id !== (int) $branchId) {
            return false;
        }

        if ($configuration->company_id !== null) {
            if ($companyId === null || (int) $configuration->company_id !== (int) $companyId) {
                return false;
            }
        }

        return true;
    }

    private function isConfigurationActiveForDate(ProductCodeConfiguration $configuration): bool
    {
        $now = now();

        if ($configuration->effective_from && $configuration->effective_from->gt($now)) {
            return false;
        }

        if ($configuration->effective_to && $configuration->effective_to->lt($now)) {
            return false;
        }

        return true;
    }

    private function periodKey(ProductCodeConfiguration $configuration, array $context): string
    {
        $rule = strtolower(trim((string) ($configuration->reset_rule ?: config('product_code.default_reset_rule', 'never'))));
        $now = now();

        return match ($rule) {
            'yearly' => $now->format('Y'),
            'monthly' => $now->format('Y-m'),
            'daily' => $now->format('Y-m-d'),
            'company' => 'COMPANY-'.($this->normalizeNullableId($context['company_id'] ?? null) ?? 'GLOBAL'),
            'branch' => 'BRANCH-'.($this->normalizeNullableId($context['branch_id'] ?? null) ?? 'GLOBAL'),
            'category' => 'CATEGORY-'.($this->normalizeNullableId($context['category_id'] ?? null) ?? 'GLOBAL'),
            'subcategory' => 'SUBCATEGORY-'.($this->normalizeNullableId($context['subcategory_id'] ?? ($context['sub_category_id'] ?? null)) ?? 'GLOBAL'),
            'brand' => 'BRAND-'.($this->normalizeNullableId($context['manufacturer_id'] ?? null) ?? 'GLOBAL'),
            'series' => 'SERIES-'.($this->normalizeNullableId($context['series_id'] ?? ($context['product_series_id'] ?? null)) ?? 'GLOBAL'),
            default => 'GLOBAL',
        };
    }

    private function scopeSignature(ProductCodeConfiguration $configuration, string $sequenceScope, string $periodKey, array $context): string
    {
        $parts = [
            'config:' . $configuration->id,
            'scope:' . $sequenceScope,
            'period:' . $periodKey,
            'company:' . ($this->normalizeNullableId($context['company_id'] ?? null) ?? ''),
            'branch:' . ($this->normalizeNullableId($context['branch_id'] ?? null) ?? ''),
            'category:' . ($this->normalizeNullableId($context['category_id'] ?? null) ?? ''),
            'subcategory:' . ($this->normalizeNullableId($context['subcategory_id'] ?? ($context['sub_category_id'] ?? null)) ?? ''),
            'brand:' . ($this->normalizeNullableId($context['manufacturer_id'] ?? null) ?? ''),
            'series:' . ($this->normalizeNullableId($context['series_id'] ?? ($context['product_series_id'] ?? null)) ?? ''),
        ];

        return hash('sha256', implode('|', $parts));
    }

    private function templateFromComponents(array $components): string
    {
        $parts = [];
        foreach ($components as $component) {
            $type = strtolower(trim((string) ($component->component_type ?? '')));
            if ($type === '') {
                continue;
            }
            if ($type === 'static_text') {
                $parts[] = trim((string) ($component->static_value ?? ''));
                continue;
            }
            $parts[] = '{'.strtoupper($type).'}';
        }

        return implode((string) config('product_code.default_separator', '-'), array_values(array_filter($parts, static function ($part) {
            return trim((string) $part) !== '';
        })));
    }

    private function missingComponentMessage(string $type): string
    {
        $labels = (array) config('product_code.component_types', []);
        $label = $labels[$type] ?? Str::headline(str_replace('_', ' ', $type));
        return $label.' is required because {'.strtoupper($type).'} is included in the active Product Code format.';
    }

    private function sanitizeToken(?string $value): string
    {
        $value = strtoupper(trim((string) $value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^A-Z0-9]+/', '', $value) ?: '';

        return $value;
    }

    private function normalizeFinalCode(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/\s+/', '', $value) ?: '';
        return trim($value);
    }

    private function normalizeNullableId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int) $value;
        return $value > 0 ? $value : null;
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? '');

        return in_array($sqlState, ['23000', '23505'], true) || in_array($driverCode, ['1062', '1555'], true);
    }

    private function productCodeIsTaken(string $code): bool
    {
        $code = $this->normalizeFinalCode($code);
        if ($code === '') {
            return false;
        }

        $productExists = DB::table('product')->where(function ($query) use ($code) {
            $query->where('product_code', $code)->orWhere('sku', $code);
        })->exists()
        || DB::table('product_variants')->where('sku', $code)->exists();

        if ($productExists) {
            return true;
        }

        if (! DB::getSchemaBuilder()->hasTable('product_code_histories')) {
            return false;
        }

        return DB::table('product_code_histories')->where(function ($query) use ($code) {
            $query->where('old_code', $code)->orWhere('new_code', $code);
        })->exists();
    }
}
