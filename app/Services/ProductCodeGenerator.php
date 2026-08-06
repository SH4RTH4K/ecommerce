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
        $codeType = $this->normalizeCodeType($context['code_type'] ?? null);
        $companyId = $this->normalizeNullableId($context['company_id'] ?? null);
        $branchId = $this->normalizeNullableId($context['branch_id'] ?? null);

        $query = ProductCodeConfiguration::query()
            ->when($codeType !== null, static function ($query) use ($codeType) {
                $query->where(function ($builder) use ($codeType) {
                    $builder->where('code_type', $codeType);

                    if ($codeType === 'product') {
                        $builder->orWhereNull('code_type');
                    }
                });
            })
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
                'configuration' => $this->configurationUnavailableMessage($context['code_type'] ?? null),
            ]);
        }

        return $this->render($configuration, $context, $this->previewSequenceNumber($configuration, $context));
    }

    public function allocate(array $context, ?ProductCodeConfiguration $configuration = null): array
    {
        $configuration = $configuration ? $configuration->loadMissing(['components', 'company', 'branch']) : $this->resolveConfiguration($context);
        if (! $configuration) {
            throw ValidationException::withMessages([
                'configuration' => $this->configurationUnavailableMessage($context['code_type'] ?? null),
            ]);
        }

        $lastException = null;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return DB::transaction(function () use ($configuration, $context) {
                    for ($sequenceAttempt = 0; $sequenceAttempt < 20; $sequenceAttempt++) {
                        $allocation = $this->lockAndIncrementSequence($configuration, $context);
                        $rendered = $this->render($configuration, $context, $allocation['sequence_number']);

                        if (! $this->codeIsTakenForConfiguration($configuration, $rendered['preview'])) {
                            return [
                                'configuration' => $configuration,
                                'code_type' => $this->normalizeCodeType($configuration->code_type ?? $context['code_type'] ?? null),
                                'sequence_number' => $allocation['sequence_number'],
                                'sequence_id' => $allocation['sequence_id'],
                                'code' => $rendered['preview'],
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
            'code_type' => $this->normalizeCodeType($configuration->code_type ?? null),
            'code_type_label' => $this->codeTypeLabel($configuration->code_type ?? null),
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
            'prefix' => $this->configurationPrefix($configuration),
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
            $value = $type === 'static_text'
                ? trim((string) $component->static_value)
                : ($resolved[$type] ?? '');

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
        $codeType = $this->normalizeCodeType($configuration->code_type ?? ($context['code_type'] ?? 'product'));
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
        $customPrefix = trim((string) ($context['custom_prefix'] ?? ''));
        $customSuffix = trim((string) ($context['custom_suffix'] ?? ''));
        $customText = trim((string) ($context['custom_text'] ?? $context['custom_prefix'] ?? $context['custom_suffix'] ?? ''));
        $variant = trim((string) ($context['variant_code'] ?? ($context['variant'] ?? '')));
        if ($variant === '' && is_array($context['variant'] ?? null)) {
            $variant = trim((string) Arr::get($context['variant'], 'sku', Arr::get($context['variant'], 'name', '')));
        }

        $prefix = $this->configurationPrefix($configuration);
        $nameCode = $this->resolveNameCode($this->resolveEntityName($codeType, $context), $this->componentLength($configuration, 'name_code', 3));
        $categoryNameCode = $this->resolveNameCode($this->resolveCategoryName($context), $this->componentLength($configuration, 'category_name_code', 3));
        $subcategoryNameCode = $this->resolveNameCode($this->resolveSubCategoryName($context), $this->componentLength($configuration, 'subcategory_name_code', 3));
        $brandNameCode = $this->resolveNameCode($this->resolveBrandName($context), $this->componentLength($configuration, 'brand_name_code', 3));
        $seriesNameCode = $this->resolveNameCode($this->resolveSeriesName($context), $this->componentLength($configuration, 'series_name_code', 3));

        return [
            'code_type' => $codeType,
            'prefix' => $prefix,
            'name_code' => $nameCode,
            'category_name_code' => $categoryNameCode,
            'subcategory_name_code' => $subcategoryNameCode,
            'brand_name_code' => $brandNameCode,
            'series_name_code' => $seriesNameCode,
            'category_code' => $category->category_code ?? '',
            'subcategory_code' => $subCategory->subcategory_code ?? '',
            'brand_code' => $brand->brand_code ?? '',
            'series_code' => $series->series_code ?? '',
            'company_code' => $company->company_code ?? '',
            'branch_code' => $branch->branch_code ?? '',
            'company' => $company->company_code ?? '',
            'branch' => $branch->branch_code ?? '',
            'category' => $category->category_code ?? '',
            'subcategory' => $subCategory->subcategory_code ?? '',
            'brand' => $brand->brand_code ?? '',
            'series' => $series->series_code ?? '',
            'product_type' => $productType,
            'custom_text' => $this->sanitizeToken($customText),
            'year' => $date->format('Y'),
            'year_short' => $date->format('y'),
            'month' => $date->format('m'),
            'day' => $date->format('d'),
            'date' => $date->format('Ymd'),
            'sequence' => $sequence,
            'variant' => $this->sanitizeToken($variant),
            'custom_prefix' => $this->sanitizeToken($customPrefix),
            'custom_suffix' => $this->sanitizeToken($customSuffix),
            'category_prefix' => $this->prefixFromCode($category->category_code ?? ''),
            'subcategory_prefix' => $this->prefixFromCode($subCategory->subcategory_code ?? ''),
            'brand_prefix' => $this->prefixFromCode($brand->brand_code ?? ''),
            'series_prefix' => $this->prefixFromCode($series->series_code ?? ''),
        ];
    }

    private function configurationUnavailableMessage($codeType): string
    {
        return 'No active '.$this->codeTypeLabel($codeType).' configuration is available.';
    }

    private function normalizeCodeType($codeType): string
    {
        $codeType = strtolower(trim((string) $codeType));
        $types = array_keys((array) config('product_code.code_types', []));

        if ($codeType === '') {
            return 'product';
        }

        return in_array($codeType, $types, true) ? $codeType : 'product';
    }

    private function codeTypeLabel($codeType): string
    {
        $codeType = $this->normalizeCodeType($codeType);
        $labels = (array) config('product_code.code_types', []);

        return $labels[$codeType] ?? Str::headline(str_replace(['_', '-'], ' ', $codeType));
    }

    private function configurationPrefix(ProductCodeConfiguration $configuration): string
    {
        foreach ($configuration->components as $component) {
            $type = strtolower(trim((string) $component->component_type));
            if (! in_array($type, ['prefix', 'static_text'], true)) {
                continue;
            }

            $value = trim((string) ($component->static_value ?? ''));
            if ($value !== '') {
                return $this->sanitizeToken($value);
            }
        }

        return '';
    }

    private function componentLength(ProductCodeConfiguration $configuration, string $componentType, int $default = 3): int
    {
        foreach ($configuration->components as $component) {
            if (strtolower(trim((string) $component->component_type)) !== strtolower($componentType)) {
                continue;
            }

            $options = (array) ($component->format_options ?? []);
            $length = (int) ($options['length'] ?? $options['max_length'] ?? 0);
            if ($length > 0) {
                return $length;
            }
        }

        return max(1, $default);
    }

    private function resolveNameCode(string $value, int $length = 3): string
    {
        return suggest_business_code($value, max(1, $length));
    }

    private function resolveEntityName(string $codeType, array $context): string
    {
        return match ($this->normalizeCodeType($codeType)) {
            'category' => $this->resolveCategoryName($context),
            'subcategory' => $this->resolveSubCategoryName($context),
            'brand' => $this->resolveBrandName($context),
            'series' => $this->resolveSeriesName($context),
            default => trim((string) (
                Arr::get($context, 'name')
                ?? Arr::get($context, 'entity_name')
                ?? Arr::get($context, 'product_name')
                ?? Arr::get($context, 'product_model')
                ?? Arr::get($context, 'category_name')
                ?? Arr::get($context, 'manufacturer_name')
                ?? Arr::get($context, 'series_name')
                ?? ''
            )),
        };
    }

    private function resolveCategoryName(array $context): string
    {
        $category = $this->resolveCategory($context);
        if ($category && trim((string) $category->category_name) !== '') {
            return (string) $category->category_name;
        }

        return trim((string) (Arr::get($context, 'category_name') ?? Arr::get($context, 'name') ?? ''));
    }

    private function resolveSubCategoryName(array $context): string
    {
        $subCategory = $this->resolveSubCategory($context);
        if ($subCategory && trim((string) $subCategory->sub_category_name) !== '') {
            return (string) $subCategory->sub_category_name;
        }

        return trim((string) (Arr::get($context, 'subcategory_name') ?? Arr::get($context, 'sub_category_name') ?? Arr::get($context, 'name') ?? ''));
    }

    private function resolveBrandName(array $context): string
    {
        $brand = $this->resolveBrand($context);
        if ($brand && trim((string) $brand->manufacturer_name) !== '') {
            return (string) $brand->manufacturer_name;
        }

        return trim((string) (Arr::get($context, 'brand_name') ?? Arr::get($context, 'manufacturer_name') ?? Arr::get($context, 'name') ?? ''));
    }

    private function resolveSeriesName(array $context): string
    {
        $series = $this->resolveSeries($context);
        if ($series && trim((string) $series->name) !== '') {
            return (string) $series->name;
        }

        return trim((string) (Arr::get($context, 'series_name') ?? Arr::get($context, 'name') ?? ''));
    }

    private function prefixFromCode(?string $code): string
    {
        $code = trim((string) $code);
        if ($code === '') {
            return '';
        }

        $segments = preg_split('/[^A-Z0-9]+/i', $code, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($segments) || $segments === []) {
            return $this->sanitizeToken($code);
        }

        return $this->sanitizeToken((string) $segments[0]);
    }

    private function codeIsTakenForConfiguration(ProductCodeConfiguration $configuration, string $code): bool
    {
        return $this->codeIsTakenForType($this->normalizeCodeType($configuration->code_type ?? null), $code);
    }

    private function codeIsTakenForType(string $codeType, string $code): bool
    {
        $code = $this->normalizeFinalCode($code);
        if ($code === '') {
            return false;
        }

        return match ($this->normalizeCodeType($codeType)) {
            'category' => DB::table('category')->where('category_code', $code)->exists(),
            'subcategory' => DB::table('sub_category')->where('subcategory_code', $code)->exists(),
            'brand' => DB::table('manufacturer')->where('brand_code', $code)->exists(),
            'series' => DB::table('product_series')->where('series_code', $code)->exists(),
            'product' => $this->productCodeIsTaken($code),
            default => $this->productCodeIsTaken($code),
        };
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
        $requestedType = $this->normalizeCodeType($context['code_type'] ?? 'product');
        $configurationType = $this->normalizeCodeType($configuration->code_type ?? 'product');
        $companyId = $this->normalizeNullableId($context['company_id'] ?? null);
        $branchId = $this->normalizeNullableId($context['branch_id'] ?? null);

        if (! $this->isConfigurationActiveForDate($configuration)) {
            return false;
        }

        if ($requestedType !== $configurationType) {
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
