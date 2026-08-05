<?php

namespace App\Http\Controllers;

use App\Product;
use App\ProductCodeComponent;
use App\ProductCodeConfiguration;
use App\ProductCodeConfigurationHistory;
use App\ProductCodeHistory;
use App\ProductCodeSequence;
use App\Services\ProductCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductCodeConfigurationController extends Controller
{
    public function index(Request $request, ProductCodeGenerator $generator)
    {
        $this->requireAdminPermission('view_product_code_configuration');

        $configurationId = $request->integer('configuration');
        $configurations = ProductCodeConfiguration::with(['components', 'company', 'branch'])
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();
        $categories = DB::table('category')->orderBy('category_name')->get();
        $subcategories = DB::table('sub_category')->orderBy('sub_category_name')->get();
        $brands = DB::table('manufacturer')->orderBy('manufacturer_name')->get();
        $series = DB::table('product_series')->orderBy('name')->get();

        $selectedConfiguration = $configurationId
            ? $configurations->firstWhere('id', $configurationId)
            : $configurations->firstWhere('is_active', true);

        if (! $selectedConfiguration && $configurations->isNotEmpty()) {
            $selectedConfiguration = $configurations->first();
        }

        $selectedConfiguration = $selectedConfiguration
            ? $selectedConfiguration->loadMissing(['components', 'company', 'branch'])
            : null;

        $activeConfiguration = $configurations->firstWhere('is_active', true);
        $activeConfiguration = $activeConfiguration
            ? $activeConfiguration->loadMissing(['components', 'company', 'branch'])
            : null;

        $snapshot = $selectedConfiguration
            ? $generator->snapshot($selectedConfiguration)
            : $this->defaultSnapshot();

        $sequences = ProductCodeSequence::with('configuration')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $productCodeHistories = ProductCodeHistory::with(['configuration', 'product'])
            ->orderByDesc('changed_at')
            ->limit(50)
            ->get();

        $configurationHistories = ProductCodeConfigurationHistory::with('configuration')
            ->orderByDesc('changed_at')
            ->limit(30)
            ->get();

        $companies = DB::table('companies')->orderBy('name')->get();
        $branches = DB::table('inventory_locations')->where('type', 'branch')->orderBy('name')->get();
        $availableComponents = config('product_code.component_types', []);
        $separators = config('product_code.separators', []);
        $sequenceScopes = config('product_code.sequence_scopes', []);
        $resetRules = config('product_code.reset_rules', []);

        return view('admin.admin-master')->with('admin_main_content', view('admin.admin-pages.product-code-configuration', compact(
            'configurations',
            'selectedConfiguration',
            'activeConfiguration',
            'snapshot',
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
            'resetRules'
        )));
    }

    public function save(Request $request, ProductCodeGenerator $generator)
    {
        $this->requireAdminPermission('change_product_code_configuration');

        $validated = $request->validate([
            'configuration_id' => 'nullable|integer|exists:product_code_configurations,id',
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
        ]);

        $components = $this->normalizeComponents($validated['components']);
        $template = $this->compileTemplate($components, $validated['separator'] ?? config('product_code.default_separator', '-'));
        if (! collect($components)->contains(fn (array $component) => $component['component_type'] === 'sequence')) {
            return back()->withInput()->withErrors(['components' => 'The sequence component must be included in every product code configuration.']);
        }

        $now = now();
        $configurationId = $validated['configuration_id'] ?? null;

        DB::transaction(function () use ($validated, $components, $template, $configurationId, $now) {
            $configuration = $configurationId
                ? ProductCodeConfiguration::lockForUpdate()->findOrFail($configurationId)
                : new ProductCodeConfiguration();

            $oldSnapshot = $configuration->exists ? $this->configurationSnapshot($configuration->loadMissing('components')) : null;

            $configuration->fill([
                'name' => trim($validated['name']),
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
            ]);

            if (! $configuration->exists) {
                $configuration->created_by = session('admin_id');
                $configuration->created_at = $now;
            }

            $configuration->save();

            if ((int) $configuration->is_active === 1) {
                ProductCodeConfiguration::where('id', '<>', $configuration->id)
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
                'old_template' => $oldSnapshot['template'] ?? null,
                'new_template' => $template,
                'old_settings' => $oldSnapshot,
                'new_settings' => $this->configurationSnapshot($configuration->fresh(['components', 'company', 'branch'])),
                'changed_by' => session('admin_id'),
                'changed_at' => $now,
            ]);
        });

        return redirect()
            ->to('/product-code-configuration?configuration='.($configurationId ?: ProductCodeConfiguration::latest('id')->value('id')))
            ->with('message', 'Product code configuration saved successfully.');
    }

    public function preview(Request $request, ProductCodeGenerator $generator)
    {
        $request->validate([
            'configuration_id' => 'nullable|integer|exists:product_code_configurations,id',
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
        if ($request->filled('configuration_id')) {
            $configuration = ProductCodeConfiguration::with('components')->find($request->integer('configuration_id'));
        }
        $hasDraftChanges = $request->filled('components') || $request->filled('name') || $request->filled('sequence_length') || $request->filled('sequence_scope') || $request->filled('reset_rule');
        if ($hasDraftChanges) {
            $configuration = $this->buildDraftConfiguration($request, $configuration);
        }
        if (! $configuration) {
            $configuration = ProductCodeConfiguration::with('components')
                ->where('is_active', 1)
                ->orderByDesc('id')
                ->first();
        }

        if (! $configuration) {
            return response()->json(['preview' => null, 'message' => 'No active product code configuration is available.'], 422);
        }

        $context = [
            'company_id' => $request->integer('company_id'),
            'branch_id' => $request->integer('branch_id'),
            'category_id' => $request->integer('category_id'),
            'subcategory_id' => $request->integer('subcategory_id'),
            'manufacturer_id' => $request->integer('manufacturer_id'),
            'series_id' => $request->integer('series_id'),
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
            'configuration' => $this->configurationSnapshot($configuration->loadMissing(['components', 'company', 'branch'])),
        ]);
    }

    public function configuration(ProductCodeGenerator $generator)
    {
        $configuration = ProductCodeConfiguration::with(['components', 'company', 'branch'])
            ->where('is_active', 1)
            ->orderByDesc('id')
            ->first();

        if (! $configuration) {
            return response()->json(['configuration' => null]);
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
            return $this->defaultSnapshot();
        }

        $configuration->loadMissing(['components', 'company', 'branch']);

        return [
            'id' => $configuration->id,
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

    private function defaultSnapshot(): array
    {
        return [
            'id' => null,
            'name' => config('product_code.default_name', 'Default Product Code'),
            'company_id' => null,
            'branch_id' => null,
            'company_name' => null,
            'branch_name' => null,
            'auto_generate' => (bool) config('product_code.default_auto_generate', true),
            'template' => config('product_code.default_template', '{CATEGORY}-{BRAND}-{SEQUENCE}'),
            'separator' => config('product_code.default_separator', '-'),
            'sequence_scope' => config('product_code.default_sequence_scope', 'global'),
            'sequence_length' => (int) config('product_code.default_sequence_length', 6),
            'sequence_start' => (int) config('product_code.default_sequence_start', 1),
            'reset_rule' => config('product_code.default_reset_rule', 'never'),
            'strict_mode' => (bool) config('product_code.default_strict_mode', true),
            'skip_empty_components' => (bool) config('product_code.default_skip_empty_components', false),
            'allow_manual_override' => (bool) config('product_code.default_allow_manual_override', false),
            'allow_regeneration' => (bool) config('product_code.default_allow_regeneration', true),
            'effective_from' => null,
            'effective_to' => null,
            'is_active' => true,
            'components' => [],
        ];
    }

    private function buildDraftConfiguration(Request $request, ?ProductCodeConfiguration $existing = null): ProductCodeConfiguration
    {
        $configuration = $existing ? clone $existing : new ProductCodeConfiguration();
        $components = $this->normalizeComponents((array) $request->input('components', $existing ? $existing->components->toArray() : []));

        $configuration->forceFill([
            'id' => $existing?->id,
            'name' => $request->input('name', $existing->name ?? config('product_code.default_name', 'Default Product Code')),
            'company_id' => $request->filled('company_id') ? (int) $request->input('company_id') : ($existing->company_id ?? null),
            'branch_id' => $request->filled('branch_id') ? (int) $request->input('branch_id') : ($existing->branch_id ?? null),
            'auto_generate' => (int) ($request->boolean('auto_generate', $existing->auto_generate ?? config('product_code.default_auto_generate', true))),
            'template' => $this->compileTemplate($components ?: ($existing ? $this->normalizeComponents($existing->components->toArray()) : []), $request->input('separator', $existing->separator ?? config('product_code.default_separator', '-'))),
            'separator' => $request->input('separator', $existing->separator ?? config('product_code.default_separator', '-')),
            'sequence_scope' => $request->input('sequence_scope', $existing->sequence_scope ?? config('product_code.default_sequence_scope', 'global')),
            'sequence_length' => (int) $request->input('sequence_length', $existing->sequence_length ?? config('product_code.default_sequence_length', 6)),
            'sequence_start' => (int) $request->input('sequence_start', $existing->sequence_start ?? config('product_code.default_sequence_start', 1)),
            'reset_rule' => $request->input('reset_rule', $existing->reset_rule ?? config('product_code.default_reset_rule', 'never')),
            'strict_mode' => (int) ($request->boolean('strict_mode', $existing->strict_mode ?? config('product_code.default_strict_mode', true))),
            'skip_empty_components' => (int) ($request->boolean('skip_empty_components', $existing->skip_empty_components ?? config('product_code.default_skip_empty_components', false))),
            'allow_manual_override' => (int) ($request->boolean('allow_manual_override', $existing->allow_manual_override ?? config('product_code.default_allow_manual_override', false))),
            'allow_regeneration' => (int) ($request->boolean('allow_regeneration', $existing->allow_regeneration ?? config('product_code.default_allow_regeneration', true))),
            'effective_from' => $request->input('effective_from', $existing->effective_from ?? null),
            'effective_to' => $request->input('effective_to', $existing->effective_to ?? null),
            'is_active' => (int) ($request->boolean('is_active', $existing->is_active ?? true)),
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
