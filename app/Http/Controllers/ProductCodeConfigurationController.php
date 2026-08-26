<?php

namespace App\Http\Controllers;

use App\Product;
use App\ProductCodeComponent;
use App\ProductCodeConfiguration;
use App\ProductCodeConfigurationHistory;
use App\ProductCodeHistory;
use App\Services\CodeRegenerationService;
use App\ProductCodeSequence;
use App\Services\ProductCodeGenerator;
use App\Services\RecycleBinService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductCodeConfigurationController extends Controller
{
    public function destroy(int $id, RecycleBinService $recycleBin)
    {
        $this->requireAdminPermission('change_product_code_configuration');
        $configuration = ProductCodeConfiguration::findOrFail($id);
        $configuration->forceFill([
            'is_active' => 0,
            'deleted_by' => session('admin_id'),
            'delete_reason' => 'Product code configuration deleted by administrator.',
        ])->save();
        $recycleBin->softDelete('product_code_configuration', $id, session('admin_id'), 'Product code configuration moved to Recycle Bin.');

        return redirect()->route('product-code-configuration.index', ['code_type' => $configuration->code_type])
            ->with('message', 'Product code configuration moved to the Recycle Bin.');
    }

    public function index(Request $request, ProductCodeGenerator $generator)
    {
        $this->requireAdminPermission('view_product_code_configuration');

        $codeTypes = (array) config('product_code.code_types', []);
        $availableCodeTypes = array_keys($codeTypes);
        $selectedCodeType = $this->normalizeCodeType($request->input('code_type', $request->input('type', 'product')), $availableCodeTypes);
        $configurationId = $request->integer('configuration');
        $allConfigurations = ProductCodeConfiguration::with(['components', 'company', 'branch'])
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();
        $selectedConfiguration = null;

        if ($configurationId) {
            $selectedConfiguration = $allConfigurations->firstWhere('id', $configurationId);
            if ($selectedConfiguration) {
                $selectedCodeType = $this->normalizeCodeType($selectedConfiguration->code_type ?? 'product', $availableCodeTypes);
            }
        }

        $configurations = $allConfigurations
            ->filter(function ($configuration) use ($selectedCodeType) {
                return $this->normalizeCodeType($configuration->code_type ?? 'product') === $selectedCodeType;
            })
            ->values();

        if (! $selectedConfiguration) {
            $selectedConfiguration = $configurations->firstWhere('is_active', true);
        }
        if (! $selectedConfiguration && $configurations->isNotEmpty()) {
            $selectedConfiguration = $configurations->first();
        }

        $categories = DB::table('category')->whereNull('deleted_at')->orderBy('category_name')->get();
        $subcategories = DB::table('sub_category')->whereNull('deleted_at')->orderBy('sub_category_name')->get();
        $brands = DB::table('manufacturer')->whereNull('deleted_at')->orderBy('manufacturer_name')->get();
        $series = DB::table('product_series')->whereNull('deleted_at')->orderBy('name')->get();

        $selectedConfiguration = $selectedConfiguration
            ? $selectedConfiguration->loadMissing(['components', 'company', 'branch'])
            : null;

        $activeConfiguration = $configurations->firstWhere('is_active', true);
        $activeConfiguration = $activeConfiguration
            ? $activeConfiguration->loadMissing(['components', 'company', 'branch'])
            : null;

        $snapshot = $selectedConfiguration
            ? $generator->snapshot($selectedConfiguration)
            : $this->defaultSnapshot($selectedCodeType);

        $typeCounts = $allConfigurations
            ->groupBy(function ($configuration) use ($availableCodeTypes) {
                return $this->normalizeCodeType($configuration->code_type ?? 'product', $availableCodeTypes);
            })
            ->map
            ->count()
            ->all();
        $codeTypeSummaries = $allConfigurations
            ->groupBy(function ($configuration) use ($availableCodeTypes) {
                return $this->normalizeCodeType($configuration->code_type ?? 'product', $availableCodeTypes);
            })
            ->map(function ($items) use ($generator) {
                $configuration = $items->firstWhere('is_active', true) ?: $items->first();
                return $configuration ? $generator->snapshot($configuration) : null;
            })
            ->all();

        $sequences = ProductCodeSequence::with('configuration')
            ->whereHas('configuration', function ($query) use ($selectedCodeType) {
                $query->forType($selectedCodeType);
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $productCodeHistories = ProductCodeHistory::with(['configuration', 'product'])
            ->whereHas('configuration', function ($query) use ($selectedCodeType) {
                $query->forType($selectedCodeType);
            })
            ->orderByDesc('changed_at')
            ->limit(50)
            ->get();

        $configurationHistories = ProductCodeConfigurationHistory::with('configuration')
            ->whereHas('configuration', function ($query) use ($selectedCodeType) {
                $query->forType($selectedCodeType);
            })
            ->orderByDesc('changed_at')
            ->limit(30)
            ->get();

        $companies = DB::table('companies')->whereNull('deleted_at')->orderBy('name')->get();
        $branches = DB::table('inventory_locations')->where('type', 'branch')->orderBy('name')->get();
        $availableComponents = config('product_code.component_types', []);
        $separators = config('product_code.separators', []);
        $sequenceScopes = config('product_code.sequence_scopes', []);
        $resetRules = config('product_code.reset_rules', []);
        $previewContextDefaults = $this->previewContextDefaults($companies, $branches, $categories, $subcategories, $brands, $series);
        $previewWarnings = $this->previewWarnings($snapshot, $companies, $branches, $categories, $subcategories, $brands, $series);
        $recordMeta = [
            'company' => ['table' => 'companies', 'id' => 'id', 'name' => 'name', 'code' => 'company_code'],
            'category' => ['table' => 'category', 'id' => 'category_id', 'name' => 'category_name', 'code' => 'category_code'],
            'subcategory' => ['table' => 'sub_category', 'id' => 'sub_category_id', 'name' => 'sub_category_name', 'code' => 'subcategory_code'],
            'brand' => ['table' => 'manufacturer', 'id' => 'manufacturer_id', 'name' => 'manufacturer_name', 'code' => 'brand_code'],
            'series' => ['table' => 'product_series', 'id' => 'id', 'name' => 'name', 'code' => 'series_code'],
            'product' => ['table' => 'product', 'id' => 'id', 'name' => 'product_name', 'code' => 'product_code'],
        ];
        $selectedRecordMeta = $recordMeta[$selectedCodeType] ?? $recordMeta['product'];
        $existingRecordCount = DB::table($selectedRecordMeta['table'])->whereNull('deleted_at')->count();
        $currentCodeSample = DB::table($selectedRecordMeta['table'])
            ->whereNull('deleted_at')
            ->whereNotNull($selectedRecordMeta['code'])
            ->where($selectedRecordMeta['code'], '<>', '')
            ->orderBy($selectedRecordMeta['name'])
            ->limit(3)
            ->get([$selectedRecordMeta['name'].' as name', $selectedRecordMeta['code'].' as code']);
        $wizardComponentTypes = [
            'company' => ['prefix','name_code','year','year_short','month','day','date','sequence','static_text','custom_prefix','custom_suffix','custom_text'],
            'category' => ['prefix','name_code','year','year_short','month','day','date','sequence','static_text','company','custom_prefix','custom_suffix','custom_text'],
            'subcategory' => ['prefix','name_code','category_name_code','category_code','year','year_short','month','day','date','sequence','static_text','company','custom_prefix','custom_suffix','custom_text'],
            'brand' => ['prefix','name_code','company','year','year_short','month','day','date','sequence','static_text','custom_prefix','custom_suffix','custom_text'],
            'series' => ['prefix','name_code','brand_name_code','brand_code','company','year','year_short','month','day','date','sequence','static_text','custom_prefix','custom_suffix','custom_text'],
            'product' => array_keys($availableComponents),
        ];
        $wizardComponentLabels = collect($availableComponents)->only($wizardComponentTypes[$selectedCodeType] ?? array_keys($availableComponents))->all();

        return view('admin.admin-master')->with('admin_main_content', view('admin.admin-pages.product-code-configuration', compact(
            'configurations',
            'selectedConfiguration',
            'activeConfiguration',
            'snapshot',
            'selectedCodeType',
            'codeTypes',
            'typeCounts',
            'codeTypeSummaries',
            'sequences',
            'productCodeHistories',
            'configurationHistories',
            'companies',
            'branches',
            'categories',
            'subcategories',
            'brands',
            'series',
            'availableComponents',
            'separators',
            'sequenceScopes',
            'resetRules',
            'previewContextDefaults',
            'previewWarnings',
            'existingRecordCount',
            'currentCodeSample',
            'wizardComponentLabels'
        )));
    }

    public function save(Request $request, ProductCodeGenerator $generator)
    {
        $this->requireAdminPermission('change_product_code_configuration');

        $validated = $request->validate([
            'configuration_id' => 'nullable|integer|exists:product_code_configurations,id',
            'code_type' => ['required', 'string', Rule::in(array_keys(config('product_code.code_types', [])))],
            'name' => 'required|string|max:160',
            'company_id' => 'nullable|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:inventory_locations,id',
            'auto_generate' => 'nullable|boolean',
            'separator' => 'nullable|string|max:10',
            'sequence_scope' => ['required', 'string', Rule::in(array_keys(config('product_code.sequence_scopes', [])))],
            'sequence_length' => 'required|integer|min:1|max:12',
            'sequence_start' => 'required|integer|min:1|max:999999999',
            'reset_rule' => ['required', 'string', Rule::in(array_keys(config('product_code.reset_rules', [])))],
            'strict_mode' => 'nullable|boolean',
            'skip_empty_components' => 'nullable|boolean',
            'allow_manual_override' => 'nullable|boolean',
            'allow_regeneration' => 'nullable|boolean',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
            'components' => 'required|array|min:1',
            'components.*.component_type' => ['required', 'string', Rule::in(array_keys(config('product_code.component_types', [])))],
            'components.*.position' => 'required|integer|min:1|max:100',
            'components.*.static_value' => 'nullable|string|max:255',
            'components.*.format_options' => 'nullable',
            'components.*.is_required' => 'nullable|boolean',
            'preview_company_id' => 'nullable|integer|exists:companies,id',
            'preview_branch_id' => 'nullable|integer|exists:inventory_locations,id',
            'preview_category_id' => 'nullable|integer|exists:category,category_id',
            'preview_subcategory_id' => 'nullable|integer|exists:sub_category,sub_category_id',
            'preview_brand_id' => 'nullable|integer|exists:manufacturer,manufacturer_id',
            'preview_series_id' => 'nullable|integer|exists:product_series,id',
            'preview_variant_code' => 'nullable|string|max:255',
            'existing_record_policy' => ['nullable', Rule::in(['FUTURE_ONLY', 'UPDATE_ALL', 'UPDATE_SELECTED'])],
            'cascade_policy' => ['nullable', Rule::in(['NONE', 'DEPENDENTS'])],
        ]);

        $components = $this->normalizeComponents($validated['components']);
        $template = $this->compileTemplate($components, $validated['separator'] ?? config('product_code.default_separator', '-'));

        $now = now();
        $configurationId = $validated['configuration_id'] ?? null;
        $savedConfigurationId = $configurationId;

        DB::transaction(function () use ($validated, $components, $template, $configurationId, $now, &$savedConfigurationId) {
            $configuration = $configurationId
                ? ProductCodeConfiguration::lockForUpdate()->findOrFail($configurationId)
                : new ProductCodeConfiguration();

            $oldSnapshot = $configuration->exists ? $this->configurationSnapshot($configuration->loadMissing('components')) : null;

            $configuration->fill([
                'name' => trim($validated['name']),
                'code_type' => $this->normalizeCodeType($validated['code_type']),
                'company_id' => $validated['company_id'] ?? null,
                'branch_id' => $validated['branch_id'] ?? null,
                'auto_generate' => (int) ($validated['auto_generate'] ?? 0),
                'template' => $template,
                'separator' => $validated['separator'] ?? config('product_code.default_separator', '-'),
                'sequence_scope' => $validated['sequence_scope'],
                'sequence_length' => (int) $validated['sequence_length'],
                'sequence_start' => (int) $validated['sequence_start'],
                'reset_rule' => $validated['reset_rule'],
                'strict_mode' => (int) ($validated['strict_mode'] ?? 0),
                'skip_empty_components' => (int) ($validated['skip_empty_components'] ?? 0),
                'allow_manual_override' => (int) ($validated['allow_manual_override'] ?? 0),
                'allow_regeneration' => (int) ($validated['allow_regeneration'] ?? 0),
                'effective_from' => $validated['effective_from'] ?? null,
                'effective_to' => $validated['effective_to'] ?? null,
                'is_active' => (int) ($validated['is_active'] ?? 0),
                'updated_by' => session('admin_id'),
                'updated_at' => $now,
                'version' => $configuration->exists ? ((int) $configuration->version + 1) : 1,
            ]);

            if (! $configuration->exists) {
                $configuration->created_by = session('admin_id');
                $configuration->created_at = $now;
            }

            $configuration->save();

            if ((int) $configuration->is_active === 1) {
                ProductCodeConfiguration::where('code_type', $configuration->code_type)
                    ->where('id', '<>', $configuration->id)
                    ->where(function ($query) use ($configuration) {
                        $query->whereNull('company_id')->orWhere('company_id', $configuration->company_id);
                    })
                    ->where(function ($query) use ($configuration) {
                        $query->whereNull('branch_id')->orWhere('branch_id', $configuration->branch_id);
                    })
                    ->where('is_active', 1)
                    ->update([
                        'is_active' => 0,
                        'updated_at' => $now,
                    ]);
            }

            ProductCodeComponent::where('configuration_id', $configuration->id)->delete();
            foreach ($components as $component) {
                ProductCodeComponent::create([
                    'configuration_id' => $configuration->id,
                    'component_type' => $component['component_type'],
                    'position' => $component['position'],
                    'static_value' => $component['static_value'],
                    'format_options' => $component['format_options'],
                    'is_required' => $component['is_required'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            ProductCodeConfigurationHistory::create([
                'configuration_id' => $configuration->id,
                'configuration_version' => (int) ($configuration->version ?: 1),
                'old_template' => $oldSnapshot['template'] ?? null,
                'new_template' => $template,
                'old_settings' => $oldSnapshot,
                'new_settings' => array_merge($this->configurationSnapshot($configuration->fresh(['components', 'company', 'branch'])), [
                    'existing_record_policy' => $validated['existing_record_policy'] ?? 'FUTURE_ONLY',
                    'cascade_policy' => $validated['cascade_policy'] ?? 'NONE',
                ]),
                'changed_by' => session('admin_id'),
                'changed_at' => $now,
            ]);

            $savedConfigurationId = $configuration->id;
        });

        $policy = $validated['existing_record_policy'] ?? 'FUTURE_ONLY';
        $redirect = redirect()
            ->to('/product-code-configuration?code_type='.$validated['code_type'].'&configuration='.$savedConfigurationId)
            ->with('message', 'Product code configuration saved successfully.');
        if ($policy !== 'FUTURE_ONLY') {
            $redirect = redirect()->route('product-code-configuration.regeneration-preview', ['configuration_id' => $savedConfigurationId, 'mode' => $policy])
                ->with('message', 'Configuration saved. Review the dry-run preview before applying existing-code changes.');
        }
        return $redirect;
    }

    public function regenerationPreview(Request $request, CodeRegenerationService $regenerator)
    {
        $mode = strtoupper((string) $request->input('mode', 'UPDATE_ALL'));
        abort_unless(in_array($mode, ['UPDATE_ALL', 'UPDATE_SELECTED'], true), 422);
        $configuration = ProductCodeConfiguration::with('components')->findOrFail($request->integer('configuration_id'));
        $this->requireAdminPermission('view_product_code_configuration');
        $selected = array_map('intval', (array) $request->input('selected', []));
        try {
            // Regeneration creates a fresh sequence for the new format. Existing
            // numeric suffixes must not influence the first generated code.
            $preview = $regenerator->preview($configuration, $mode, $selected, false);
        } catch (\Throwable $exception) {
            Log::error('Product code regeneration preview failed.', [
                'configuration_id' => $configuration->id,
                'code_type' => $configuration->code_type,
                'mode' => $mode,
                'admin_id' => session('admin_id'),
                'exception' => $exception,
            ]);

            $message = 'The dry-run preview could not be generated. No existing codes were changed. Please review the configuration or contact an administrator with the time of this error.';
            if ($request->isMethod('post') || $request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            $preview = [
                'code_type' => $configuration->code_type,
                'configuration_id' => $configuration->id,
                'mode' => $mode,
                'items' => [],
                'total' => 0,
                'ready' => 0,
                'conflicts' => 1,
                'error' => $message,
            ];
        }
        if ($request->isMethod('post') || $request->expectsJson()) return response()->json($preview);
        return view('admin.admin-pages.product-code-regeneration-preview', compact('configuration', 'preview', 'mode'));
    }

    public function applyRegeneration(Request $request, CodeRegenerationService $regenerator)
    {
        if (! Schema::hasTable('product_code_regeneration_batches')
            || ! Schema::hasColumn('product_code_histories', 'batch_id')
            || ! Schema::hasColumn('product_code_histories', 'entity_type')) {
            return redirect()->route('product-code-configuration.index')
                ->with('exception', 'Code regeneration is not ready on this server. Deploy the latest database migrations, then try again.');
        }

        $validated = $request->validate(['configuration_id' => 'required|integer|exists:product_code_configurations,id', 'mode' => ['required', Rule::in(['UPDATE_ALL','UPDATE_SELECTED'])], 'selected' => 'nullable|array', 'selected.*' => 'integer', 'reason' => 'required|string|max:2000', 'confirmation' => 'required|in:REGENERATE']);
        $this->requireAdminPermission('regenerate_product_code');
        $configuration = ProductCodeConfiguration::with('components')->findOrFail($validated['configuration_id']);
        try {
            $preview = $regenerator->preview($configuration, $validated['mode'], array_map('intval', $validated['selected'] ?? []), false);
            $batch = $regenerator->apply($configuration, $preview, $validated['reason'], (int) session('admin_id'), true);
            return redirect()->route('product-code-configuration.index', ['code_type' => $configuration->code_type, 'configuration' => $configuration->id])->with('message', 'Code regeneration completed. Batch #'.$batch->id.' updated '.$batch->success_count.' record(s).');
        } catch (\Throwable $exception) {
            Log::error('Product code regeneration apply failed.', ['configuration_id' => $configuration->id, 'admin_id' => session('admin_id'), 'exception' => $exception]);
            return redirect()->back()->withInput()->with('exception', 'Code regeneration could not be applied. No changes were made. Review the preview and database migration status, then try again.');
        }
    }

    public function regenerationApplyPage()
    {
        return redirect()->route('product-code-configuration.index')->with('exception', 'This is an action endpoint. Open the regeneration preview and submit it from there.');
    }

    public function preview(Request $request, ProductCodeGenerator $generator)
    {
        $request->validate([
            'configuration_id' => 'nullable|integer|exists:product_code_configurations,id',
            'code_type' => 'nullable|in:'.implode(',', array_keys((array) config('product_code.code_types', []))),
            'name' => 'nullable|string|max:160',
            'company_id' => 'nullable|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:inventory_locations,id',
            'auto_generate' => 'nullable|boolean',
            'separator' => 'nullable|string|max:10',
            'sequence_scope' => ['nullable', 'string', Rule::in(array_keys(config('product_code.sequence_scopes', [])))],
            'sequence_length' => 'nullable|integer|min:1|max:12',
            'sequence_start' => 'nullable|integer|min:1|max:999999999',
            'reset_rule' => ['nullable', 'string', Rule::in(array_keys(config('product_code.reset_rules', [])))],
            'strict_mode' => 'nullable|boolean',
            'skip_empty_components' => 'nullable|boolean',
            'allow_manual_override' => 'nullable|boolean',
            'allow_regeneration' => 'nullable|boolean',
            'category_id' => 'nullable|integer|exists:category,category_id',
            'subcategory_id' => 'nullable|integer|exists:sub_category,sub_category_id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturer,manufacturer_id',
            'series_id' => 'nullable|integer|exists:product_series,id',
            'variant_code' => 'nullable|string|max:255',
            'custom_prefix' => 'nullable|string|max:255',
            'custom_suffix' => 'nullable|string|max:255',
            'product_type_code' => 'nullable|string|max:255',
            'components' => 'nullable|array',
            'components.*.component_type' => ['required_with:components', 'string', Rule::in(array_keys(config('product_code.component_types', [])))],
            'components.*.position' => 'nullable|integer|min:1|max:100',
            'components.*.static_value' => 'nullable|string|max:255',
            'components.*.format_options' => 'nullable',
            'components.*.is_required' => 'nullable|boolean',
        ]);

        $configuration = null;
        $codeType = $this->normalizeCodeType($request->input('code_type', 'product'));
        if ($request->filled('configuration_id')) {
            $configuration = ProductCodeConfiguration::with('components')->find($request->integer('configuration_id'));
            if ($configuration) {
                $codeType = $this->normalizeCodeType($configuration->code_type ?? 'product');
            }
        }
        $hasDraftChanges = $request->filled('components') || $request->filled('name') || $request->filled('sequence_length') || $request->filled('sequence_scope') || $request->filled('reset_rule');
        if ($hasDraftChanges) {
            $configuration = $this->buildDraftConfiguration($request, $configuration);
        }
        if (! $configuration) {
            $configuration = ProductCodeConfiguration::with('components')
                ->forType($codeType)
                ->where('is_active', 1)
                ->orderByDesc('id')
                ->first();
        }

        if (! $configuration) {
            return response()->json(['preview' => null, 'message' => $this->configurationUnavailableMessage($codeType)], 422);
        }

        $snapshot = $this->configurationSnapshot($configuration->loadMissing(['components', 'company', 'branch']));
        $companies = DB::table('companies')->orderBy('name')->get();
        $branches = DB::table('inventory_locations')->where('type', 'branch')->orderBy('name')->get();
        $categories = DB::table('category')->whereNull('deleted_at')->orderBy('category_name')->get();
        $subcategories = DB::table('sub_category')->whereNull('deleted_at')->orderBy('sub_category_name')->get();
        $brands = DB::table('manufacturer')->whereNull('deleted_at')->orderBy('manufacturer_name')->get();
        $series = DB::table('product_series')->whereNull('deleted_at')->orderBy('name')->get();
        $previewContextDefaults = $this->previewContextDefaults($companies, $branches, $categories, $subcategories, $brands, $series);
        $previewWarnings = $this->previewWarnings($snapshot, $companies, $branches, $categories, $subcategories, $brands, $series);

        if ($previewWarnings !== []) {
            return response()->json([
                'preview' => null,
                'message' => implode(' ', $previewWarnings),
                'warnings' => $previewWarnings,
            ], 422);
        }

        $context = [
            'code_type' => $codeType,
            'company_id' => $request->filled('company_id') ? $request->integer('company_id') : ($previewContextDefaults['company_id'] ?? null),
            'branch_id' => $request->filled('branch_id') ? $request->integer('branch_id') : ($previewContextDefaults['branch_id'] ?? null),
            'category_id' => $request->filled('category_id') ? $request->integer('category_id') : ($previewContextDefaults['category_id'] ?? null),
            'subcategory_id' => $request->filled('subcategory_id') ? $request->integer('subcategory_id') : ($previewContextDefaults['subcategory_id'] ?? null),
            'manufacturer_id' => $request->filled('manufacturer_id') ? $request->integer('manufacturer_id') : ($previewContextDefaults['manufacturer_id'] ?? null),
            'series_id' => $request->filled('series_id') ? $request->integer('series_id') : ($previewContextDefaults['series_id'] ?? null),
            'variant_code' => $request->input('variant_code'),
            'custom_prefix' => $request->input('custom_prefix'),
            'custom_suffix' => $request->input('custom_suffix'),
            'product_type_code' => $request->input('product_type_code'),
        ];

        try {
            $details = $generator->preview($context + ['company_id' => $context['company_id'] ?: $configuration->company_id, 'branch_id' => $context['branch_id'] ?: $configuration->branch_id], $configuration);
        } catch (\Throwable $exception) {
            return response()->json(['preview' => null, 'message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'preview' => $details['preview'],
            'values' => $details['values'],
            'configuration' => $snapshot,
        ]);
    }

    public function configuration(ProductCodeGenerator $generator)
    {
        $request = request();
        $codeType = $this->normalizeCodeType($request->input('code_type', 'product'));
        $configuration = null;

        if ($request->filled('configuration_id')) {
            $configuration = ProductCodeConfiguration::with(['components', 'company', 'branch'])
                ->find($request->integer('configuration_id'));

            if ($configuration) {
                $codeType = $this->normalizeCodeType($configuration->code_type ?? 'product');
            }
        }

        if (! $configuration) {
            $configuration = ProductCodeConfiguration::with(['components', 'company', 'branch'])
                ->forType($codeType)
                ->where('is_active', 1)
                ->orderByDesc('id')
                ->first();
        }

        if (! $configuration) {
            return response()->json(['configuration' => $this->defaultSnapshot($codeType)]);
        }

        return response()->json(['configuration' => $generator->snapshot($configuration)]);
    }

    public function resetSequence(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'next_number' => 'nullable|integer|min:1|max:999999999',
        ]);

        if ($request->filled('next_number')) {
            $this->requireAdminPermission('change_product_code_sequence');
        } else {
            $this->requireAdminPermission('reset_product_code_sequence');
        }

        $sequence = ProductCodeSequence::with('configuration')->findOrFail($id);
        $isCorrection = $request->filled('next_number');
        $targetNumber = $isCorrection
            ? max(1, (int) $validated['next_number'])
            : max(1, (int) $sequence->configuration->sequence_start);
        $lastNumber = max(0, $targetNumber - 1);

        DB::transaction(function () use ($sequence, $validated, $isCorrection, $targetNumber, $lastNumber) {
            $sequence->update([
                'last_number' => $lastNumber,
                'updated_at' => now(),
            ]);

            DB::table('admin_activity_logs')->insert([
                'admin_id' => session('admin_id'),
                'admin_name' => session('admin_name'),
                'action' => $isCorrection ? 'correct_product_code_sequence' : 'reset_product_code_sequence',
                'method' => 'POST',
                'path' => request()->path(),
                'ip_hash' => request()->ip() ? hash('sha256', request()->ip()) : null,
                'details' => json_encode([
                    'sequence_id' => $sequence->id,
                    'configuration_id' => $sequence->configuration_id,
                    'reason' => $validated['reason'],
                    'next_number' => $isCorrection ? $targetNumber : null,
                    'last_number' => $lastNumber,
                ]),
                'created_at' => now(),
            ]);
        });

        return back()->with('message', $isCorrection
            ? 'Product code sequence corrected successfully.'
            : 'Product code sequence reset successfully.');
    }

    private function normalizeComponents(array $components): array
    {
        $normalized = [];
        foreach (array_values($components) as $index => $component) {
            $type = strtolower(trim((string) Arr::get($component, 'component_type', '')));
            if ($type === '') {
                continue;
            }

            $normalized[] = [
                'component_type' => $type,
                'position' => max(1, (int) Arr::get($component, 'position', $index + 1)),
                'static_value' => trim((string) Arr::get($component, 'static_value', '')) ?: null,
                'format_options' => $this->normalizeFormatOptions(Arr::get($component, 'format_options')),
                'is_required' => (int) Arr::get($component, 'is_required', 1) === 1 ? 1 : 0,
            ];
        }

        usort($normalized, static function (array $a, array $b) {
            return $a['position'] <=> $b['position'];
        });

        return $normalized;
    }

    private function normalizeFormatOptions(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function compileTemplate(array $components, ?string $separator = null): string
    {
        $parts = [];
        foreach ($components as $component) {
            if ($component['component_type'] === 'static_text') {
                $parts[] = trim((string) $component['static_value']);
                continue;
            }

            $parts[] = '{'.strtoupper($component['component_type']).'}';
        }

        $separator = $separator ?? config('product_code.default_separator', '-');

        return implode((string) $separator, array_values(array_filter($parts, static function ($part) {
            return trim((string) $part) !== '';
        })));
    }

    private function configurationSnapshot(?ProductCodeConfiguration $configuration): array
    {
        if (! $configuration) {
            return $this->defaultSnapshot('product');
        }

        $configuration->loadMissing(['components', 'company', 'branch']);
        $codeType = $this->normalizeCodeType($configuration->code_type ?? 'product');

        return [
            'id' => $configuration->id,
            'version' => (int) ($configuration->version ?: 1),
            'code_type' => $codeType,
            'code_type_label' => $this->codeTypeLabel($codeType),
            'name' => $configuration->name,
            'company_id' => $configuration->company_id,
            'branch_id' => $configuration->branch_id,
            'company_name' => optional($configuration->company)->name,
            'branch_name' => optional($configuration->branch)->name,
            'auto_generate' => (bool) $configuration->auto_generate,
            'template' => $configuration->template,
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

    private function configurationPrefix(ProductCodeConfiguration $configuration): string
    {
        foreach ($configuration->components as $component) {
            $type = strtolower(trim((string) $component->component_type));
            if (! in_array($type, ['prefix', 'static_text'], true)) {
                continue;
            }

            $value = trim((string) ($component->static_value ?? ''));
            if ($value !== '') {
                return normalize_business_code($value, 30) ?: '';
            }
        }

        return '';
    }

    private function defaultSnapshot(string $codeType = 'product'): array
    {
        $codeType = $this->normalizeCodeType($codeType);
        $definition = (array) data_get(config('product_code.code_type_defaults', []), $codeType, []);
        $components = collect((array) ($definition['components'] ?? []))->map(function (array $component, int $index) {
            $staticValue = $component['static_value'] ?? null;
            if (is_string($staticValue)) {
                $staticValue = trim($staticValue);
                $staticValue = $staticValue !== '' ? $staticValue : null;
            }

            return [
                'id' => null,
                'component_type' => (string) ($component['component_type'] ?? 'sequence'),
                'position' => (int) ($component['position'] ?? ($index + 1)),
                'static_value' => $staticValue,
                'format_options' => $component['format_options'] ?? null,
                'is_required' => (bool) ($component['is_required'] ?? true),
            ];
        })->values()->all();

        return [
            'id' => null,
            'code_type' => $codeType,
            'code_type_label' => $this->codeTypeLabel($codeType),
            'name' => (string) ($definition['name'] ?? $this->codeTypeLabel($codeType)),
            'company_id' => null,
            'branch_id' => null,
            'company_name' => null,
            'branch_name' => null,
            'auto_generate' => (bool) ($definition['auto_generate'] ?? config('product_code.default_auto_generate', true)),
            'template' => (string) ($definition['template'] ?? config('product_code.default_template', '{PREFIX}-{CATEGORY_CODE}-{SUBCATEGORY_CODE}-{BRAND_CODE}-{SERIES_CODE}-{SEQUENCE}')),
            'separator' => (string) ($definition['separator'] ?? config('product_code.default_separator', '-')),
            'sequence_scope' => (string) ($definition['sequence_scope'] ?? config('product_code.default_sequence_scope', 'global')),
            'sequence_length' => (int) ($definition['sequence_length'] ?? config('product_code.default_sequence_length', 6)),
            'sequence_start' => (int) ($definition['sequence_start'] ?? config('product_code.default_sequence_start', 1)),
            'reset_rule' => (string) ($definition['reset_rule'] ?? config('product_code.default_reset_rule', 'never')),
            'strict_mode' => (bool) ($definition['strict_mode'] ?? config('product_code.default_strict_mode', true)),
            'skip_empty_components' => (bool) ($definition['skip_empty_components'] ?? config('product_code.default_skip_empty_components', false)),
            'allow_manual_override' => (bool) ($definition['allow_manual_override'] ?? config('product_code.default_allow_manual_override', false)),
            'allow_regeneration' => (bool) ($definition['allow_regeneration'] ?? config('product_code.default_allow_regeneration', true)),
            'effective_from' => null,
            'effective_to' => null,
            'is_active' => true,
            'prefix' => $this->configurationPrefixFromDefinition($definition),
            'components' => $components,
        ];
    }

    private function normalizeCodeType($codeType, ?array $allowed = null): string
    {
        $allowed = $allowed ?: array_keys((array) config('product_code.code_types', []));
        $codeType = strtolower(trim((string) $codeType));

        if ($codeType === '') {
            return 'product';
        }

        return in_array($codeType, $allowed, true) ? $codeType : 'product';
    }

    private function codeTypeLabel($codeType): string
    {
        $codeType = $this->normalizeCodeType($codeType);
        $labels = (array) config('product_code.code_types', []);

        return $labels[$codeType] ?? Str::headline(str_replace(['_', '-'], ' ', $codeType));
    }

    private function configurationPrefixFromDefinition(array $definition): string
    {
        foreach ((array) ($definition['components'] ?? []) as $component) {
            $type = strtolower(trim((string) ($component['component_type'] ?? '')));
            if (! in_array($type, ['prefix', 'static_text'], true)) {
                continue;
            }

            $value = trim((string) ($component['static_value'] ?? ''));
            if ($value !== '') {
                return normalize_business_code($value, 30) ?: '';
            }
        }

        return '';
    }

    private function previewContextDefaults($companies, $branches, $categories, $subcategories, $brands, $series): array
    {
        return [
            'company_id' => $this->firstCodedRecordId($companies, 'company_code', 'id'),
            'branch_id' => $this->firstCodedRecordId($branches, 'code', 'id'),
            'category_id' => $this->firstCodedRecordId($categories, 'category_code', 'category_id'),
            'subcategory_id' => $this->firstCodedRecordId($subcategories, 'subcategory_code', 'sub_category_id'),
            'manufacturer_id' => $this->firstCodedRecordId($brands, 'brand_code', 'manufacturer_id'),
            'series_id' => $this->firstCodedRecordId($series, 'series_code', 'id'),
        ];
    }

    private function previewWarnings(array $snapshot, $companies, $branches, $categories, $subcategories, $brands, $series): array
    {
        $requiredTypes = collect($snapshot['components'] ?? [])
            ->pluck('component_type')
            ->map(static function ($type) {
                return strtolower(trim((string) $type));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $codeType = $this->normalizeCodeType($snapshot['code_type'] ?? 'product');
        $entityMap = [
            'company' => [
                'records' => $companies,
                'label' => 'companies',
                'singular' => 'company',
                'code_label' => 'Company Code',
                'field' => 'company_code',
                'requires_code' => true,
            ],
            'branch' => [
                'records' => $branches,
                'label' => 'branches',
                'singular' => 'branch',
                'code_label' => 'Branch Code',
                'field' => 'code',
                'requires_code' => true,
            ],
            'category' => [
                'records' => $categories,
                'label' => 'categories',
                'singular' => 'category',
                'code_label' => 'Category Code',
                'field' => 'category_code',
                'requires_code' => true,
            ],
            'subcategory' => [
                'records' => $subcategories,
                'label' => 'subcategories',
                'singular' => 'subcategory',
                'code_label' => 'Subcategory Code',
                'field' => 'subcategory_code',
                'requires_code' => true,
            ],
            'brand' => [
                'records' => $brands,
                'label' => 'brands',
                'singular' => 'brand',
                'code_label' => 'Brand Code',
                'field' => 'brand_code',
                'requires_code' => true,
            ],
            'series' => [
                'records' => $series,
                'label' => 'series',
                'singular' => 'series',
                'code_label' => 'Series Code',
                'field' => 'series_code',
                'requires_code' => true,
            ],
        ];

        $componentDependencies = [
            'company' => ['company'],
            'company_code' => ['company'],
            'branch' => ['branch'],
            'branch_code' => ['branch'],
            'category' => ['category'],
            'category_code' => ['category'],
            'category_name_code' => ['category'],
            'category_prefix' => ['category'],
            'subcategory' => ['subcategory'],
            'subcategory_code' => ['subcategory'],
            'subcategory_name_code' => ['subcategory'],
            'subcategory_prefix' => ['subcategory'],
            'brand' => ['brand'],
            'brand_code' => ['brand'],
            'brand_name_code' => ['brand'],
            'brand_prefix' => ['brand'],
            'series' => ['series'],
            'series_code' => ['series'],
            'series_name_code' => ['series'],
            'series_prefix' => ['series'],
            'name_code' => $codeType === 'product' ? [] : [$codeType],
            'prefix' => [],
            'static_text' => [],
            'sequence' => [],
            'variant' => [],
            'custom_prefix' => [],
            'custom_suffix' => [],
            'custom_text' => [],
            'product_type' => [],
            'year' => [],
            'year_short' => [],
            'month' => [],
            'day' => [],
            'date' => [],
        ];

        $entityTypes = collect($requiredTypes)
            ->flatMap(static function (string $type) use ($componentDependencies) {
                return $componentDependencies[$type] ?? [];
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $warnings = [];
        foreach ($entityTypes as $type) {
            if (! isset($entityMap[$type])) {
                continue;
            }

            $meta = $entityMap[$type];
            $records = $meta['records'];
            if ($records->isEmpty()) {
                $warnings[] = 'No '.$meta['label'].' exist yet. Create at least one '.$meta['singular'].' before previewing this template.';
                continue;
            }

            if (! empty($meta['requires_code'])) {
                $hasCodes = $records->contains(static function ($record) use ($meta) {
                    return trim((string) data_get($record, $meta['field'])) !== '';
                });

                if (! $hasCodes) {
                    $warnings[] = 'The existing '.$meta['label'].' do not yet have '.$meta['code_label'].' values. Add one before previewing this template.';
                }
            }
        }

        return $warnings;
    }

    private function firstCodedRecordId($records, string $codeField, string $idField): ?int
    {
        $record = $records->first(static function ($item) use ($codeField) {
            return trim((string) data_get($item, $codeField)) !== '';
        });

        if (! $record) {
            return null;
        }

        $id = data_get($record, $idField);
        return $id !== null && $id !== '' ? (int) $id : null;
    }

    private function buildDraftConfiguration(Request $request, ?ProductCodeConfiguration $existing = null): ProductCodeConfiguration
    {
        $codeType = $this->normalizeCodeType($request->input('code_type', $existing?->code_type ?? 'product'));
        $defaults = $this->defaultSnapshot($codeType);
        $configuration = $existing ? clone $existing : new ProductCodeConfiguration();
        $components = $this->normalizeComponents((array) $request->input('components', $existing ? $existing->components->toArray() : $defaults['components']));
        $existingName = $existing?->name ?? null;
        $existingCompanyId = $existing?->company_id ?? null;
        $existingBranchId = $existing?->branch_id ?? null;
        $existingSeparator = $existing?->separator ?? $defaults['separator'];
        $existingSequenceScope = $existing?->sequence_scope ?? $defaults['sequence_scope'];
        $existingSequenceLength = $existing?->sequence_length ?? $defaults['sequence_length'];
        $existingSequenceStart = $existing?->sequence_start ?? $defaults['sequence_start'];
        $existingResetRule = $existing?->reset_rule ?? $defaults['reset_rule'];
        $existingEffectiveFrom = $existing?->effective_from ?? null;
        $existingEffectiveTo = $existing?->effective_to ?? null;
        $existingIsActive = $existing?->is_active ?? true;

        $configuration->forceFill([
            'id' => $existing?->id,
            'code_type' => $codeType,
            'name' => $request->input('name', $existingName ?? $defaults['name']),
            'company_id' => $request->filled('company_id') ? (int) $request->input('company_id') : $existingCompanyId,
            'branch_id' => $request->filled('branch_id') ? (int) $request->input('branch_id') : $existingBranchId,
            'auto_generate' => (int) ($request->boolean('auto_generate', $existing?->auto_generate ?? $defaults['auto_generate'])),
            'template' => $this->compileTemplate($components ?: ($existing ? $this->normalizeComponents($existing->components->toArray()) : $defaults['components']), $request->input('separator', $existingSeparator)),
            'separator' => $request->input('separator', $existingSeparator),
            'sequence_scope' => $request->input('sequence_scope', $existingSequenceScope),
            'sequence_length' => (int) $request->input('sequence_length', $existingSequenceLength),
            'sequence_start' => (int) $request->input('sequence_start', $existingSequenceStart),
            'reset_rule' => $request->input('reset_rule', $existingResetRule),
            'strict_mode' => (int) ($request->boolean('strict_mode', $existing?->strict_mode ?? $defaults['strict_mode'])),
            'skip_empty_components' => (int) ($request->boolean('skip_empty_components', $existing?->skip_empty_components ?? $defaults['skip_empty_components'])),
            'allow_manual_override' => (int) ($request->boolean('allow_manual_override', $existing?->allow_manual_override ?? $defaults['allow_manual_override'])),
            'allow_regeneration' => (int) ($request->boolean('allow_regeneration', $existing?->allow_regeneration ?? $defaults['allow_regeneration'])),
            'effective_from' => $request->input('effective_from', $existingEffectiveFrom),
            'effective_to' => $request->input('effective_to', $existingEffectiveTo),
            'is_active' => (int) ($request->boolean('is_active', $existingIsActive)),
        ]);

        $configuration->setRelation('components', collect($components)->map(function (array $component) {
            return new ProductCodeComponent([
                'component_type' => $component['component_type'],
                'position' => $component['position'],
                'static_value' => $component['static_value'],
                'format_options' => $component['format_options'],
                'is_required' => $component['is_required'],
            ]);
        }));

        return $configuration;
    }
}
