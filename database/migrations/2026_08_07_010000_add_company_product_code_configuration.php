<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_code_configurations') || ! Schema::hasTable('product_code_components')) {
            return;
        }

        if (DB::table('product_code_configurations')->where('code_type', 'company')->exists()) {
            return;
        }

        $definition = (array) config('product_code.code_type_defaults.company', []);
        if ($definition === []) {
            return;
        }

        $now = now();
        $configurationId = DB::table('product_code_configurations')->insertGetId([
            'name' => (string) ($definition['name'] ?? 'Company Code'),
            'code_type' => 'company',
            'auto_generate' => (int) ($definition['auto_generate'] ?? true),
            'template' => (string) ($definition['template'] ?? '{PREFIX}-{NAME_CODE}-{SEQUENCE}'),
            'separator' => (string) ($definition['separator'] ?? config('product_code.default_separator', '-')),
            'sequence_scope' => (string) ($definition['sequence_scope'] ?? config('product_code.default_sequence_scope', 'global')),
            'sequence_length' => (int) ($definition['sequence_length'] ?? config('product_code.default_sequence_length', 6)),
            'sequence_start' => (int) ($definition['sequence_start'] ?? config('product_code.default_sequence_start', 1)),
            'reset_rule' => (string) ($definition['reset_rule'] ?? config('product_code.default_reset_rule', 'never')),
            'strict_mode' => (int) ($definition['strict_mode'] ?? config('product_code.default_strict_mode', true)),
            'skip_empty_components' => (int) ($definition['skip_empty_components'] ?? config('product_code.default_skip_empty_components', false)),
            'allow_manual_override' => (int) ($definition['allow_manual_override'] ?? config('product_code.default_allow_manual_override', false)),
            'allow_regeneration' => (int) ($definition['allow_regeneration'] ?? config('product_code.default_allow_regeneration', true)),
            'effective_from' => null,
            'effective_to' => null,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ((array) ($definition['components'] ?? []) as $component) {
            DB::table('product_code_components')->insert([
                'configuration_id' => $configurationId,
                'component_type' => (string) ($component['component_type'] ?? 'sequence'),
                'position' => (int) ($component['position'] ?? 1),
                'static_value' => $component['static_value'] ?? null,
                'format_options' => isset($component['format_options']) ? json_encode($component['format_options']) : null,
                'is_required' => (int) ($component['is_required'] ?? 1),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_code_configurations') || ! Schema::hasTable('product_code_components')) {
            return;
        }

        $configurationIds = DB::table('product_code_configurations')
            ->where('code_type', 'company')
            ->where('name', (string) config('product_code.code_type_defaults.company.name', 'Company Code'))
            ->pluck('id');

        if ($configurationIds->isEmpty()) {
            return;
        }

        DB::table('product_code_components')->whereIn('configuration_id', $configurationIds)->delete();
        DB::table('product_code_configurations')->whereIn('id', $configurationIds)->delete();
    }
};
