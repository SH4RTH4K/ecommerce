<?php

namespace Tests\Feature;

use App\ProductCodeConfiguration;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductCodeConfigurationTest extends TestCase
{
    private function adminSession(array $permissions): array
    {
        $admin = DB::table('tbl_admin')
            ->where('is_active', 1)
            ->orderBy('admin_id')
            ->first();

        $this->assertNotNull($admin, 'An active administrator is required for this test.');

        $role = DB::table('admin_roles')->where('id', $admin->role_id)->first();
        $currentPermissions = [];
        if ($role && ! empty($role->permissions)) {
            $decoded = json_decode($role->permissions, true);
            if (is_array($decoded)) {
                $currentPermissions = array_values(array_filter($decoded, static function ($permission) {
                    return is_string($permission) && trim($permission) !== '';
                }));
            }
        }

        $requiredPermissions = array_values(array_unique(array_merge(
            $currentPermissions,
            ['settings'],
            $permissions
        )));

        DB::table('admin_roles')
            ->where('id', $admin->role_id)
            ->update([
                'permissions' => json_encode($requiredPermissions),
                'updated_at' => now(),
            ]);

        return [
            'admin_id' => $admin->admin_id,
            'admin_name' => $admin->admin_name,
            'admin_permissions' => $requiredPermissions,
        ];
    }

    private function createProductCodePreviewData(string $suffix): array
    {
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'Preview Company '.$suffix,
            'company_code' => 'PC'.$suffix,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branchId = DB::table('inventory_locations')->insertGetId([
            'name' => 'Preview Branch '.$suffix,
            'code' => 'BR'.$suffix,
            'type' => 'branch',
            'address' => 'Preview address',
            'is_default' => 0,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('category')->insertGetId([
            'category_name' => 'Preview Category '.$suffix,
            'category_code' => 'CAT'.$suffix,
            'slug' => 'preview-category-'.$suffix,
            'category_description' => 'Preview category',
            'icon_class' => 'fa-laptop',
            'is_featured' => 1,
            'display_order' => 1,
            'publication_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subcategoryId = DB::table('sub_category')->insertGetId([
            'category_id' => $categoryId,
            'sub_category_name' => 'Preview Subcategory '.$suffix,
            'subcategory_code' => 'SUB'.$suffix,
            'slug' => 'preview-subcategory-'.$suffix,
            'publication_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $brandId = DB::table('manufacturer')->insertGetId([
            'company_id' => $companyId,
            'manufacturer_name' => 'Preview Brand '.$suffix,
            'brand_code' => 'BR'.$suffix,
            'slug' => 'preview-brand-'.$suffix,
            'publication_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $seriesId = DB::table('product_series')->insertGetId([
            'manufacturer_id' => $brandId,
            'name' => 'Preview Series '.$suffix,
            'series_code' => 'SER'.$suffix,
            'slug' => 'preview-series-'.$suffix,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return compact('companyId', 'branchId', 'categoryId', 'subcategoryId', 'brandId', 'seriesId');
    }

    public function testConfigurationPageRendersAndPreviewDoesNotConsumeASequence(): void
    {
        DB::beginTransaction();
        try {
            $session = $this->adminSession(['view_product_code_configuration']);
            $previewData = $this->createProductCodePreviewData(str_random(6));
            $configuration = ProductCodeConfiguration::with('components')->where('is_active', 1)->first();
            $this->assertNotNull($configuration, 'A default product code configuration is required.');

            $sequenceCountBefore = DB::table('product_code_sequences')->count();

            $this->withSession($session)
                ->get('/product-code-configuration?configuration='.$configuration->id)
                ->assertOk()
                ->assertSee('Product Code Configuration')
                ->assertSee('Configuration Builder')
                ->assertSee('Sequence Management');

            $response = $this->withSession($session)->post('/product-code-configuration/preview', [
                'configuration_id' => $configuration->id,
                'company_id' => $previewData['companyId'],
                'branch_id' => $previewData['branchId'],
                'category_id' => $previewData['categoryId'],
                'subcategory_id' => $previewData['subcategoryId'],
                'manufacturer_id' => $previewData['brandId'],
                'series_id' => $previewData['seriesId'],
                'variant_code' => 'BLACK',
                'custom_prefix' => 'PRD',
                'custom_suffix' => 'X',
                'product_type_code' => 'TECH',
            ]);

            $response->assertOk()->assertJsonStructure(['preview', 'values', 'configuration']);
            $this->assertNotEmpty($response->json('preview'));
            $this->assertSame($sequenceCountBefore, DB::table('product_code_sequences')->count());
        } finally {
            DB::rollBack();
        }
    }

    public function testConfigurationCanStoreACustomSeparator(): void
    {
        DB::beginTransaction();
        try {
            $session = $this->adminSession(['view_product_code_configuration', 'change_product_code_configuration']);
            $configuration = ProductCodeConfiguration::with('components')->where('is_active', 1)->first();
            $this->assertNotNull($configuration, 'A default product code configuration is required.');

            $components = [];
            foreach ($configuration->components as $component) {
                $components[] = [
                    'component_type' => $component->component_type,
                    'position' => $component->position,
                    'static_value' => $component->static_value,
                    'format_options' => is_array($component->format_options)
                        ? json_encode($component->format_options)
                        : ($component->format_options ?: ''),
                    'is_required' => (int) $component->is_required,
                ];
            }

            $this->withSession($session)
                ->from('/product-code-configuration')
                ->post('/product-code-configuration', [
                    'configuration_id' => $configuration->id,
                    'name' => $configuration->name,
                    'company_id' => $configuration->company_id,
                    'branch_id' => $configuration->branch_id,
                    'auto_generate' => (int) $configuration->auto_generate,
                    'separator' => '|',
                    'sequence_scope' => $configuration->sequence_scope,
                    'sequence_length' => $configuration->sequence_length,
                    'sequence_start' => $configuration->sequence_start,
                    'reset_rule' => $configuration->reset_rule,
                    'strict_mode' => (int) $configuration->strict_mode,
                    'skip_empty_components' => (int) $configuration->skip_empty_components,
                    'allow_manual_override' => (int) $configuration->allow_manual_override,
                    'allow_regeneration' => (int) $configuration->allow_regeneration,
                    'is_active' => 1,
                    'components' => $components,
                ])
                ->assertRedirect('/product-code-configuration?configuration='.$configuration->id)
                ->assertSessionHas('message');

            $this->assertSame('|', DB::table('product_code_configurations')->where('id', $configuration->id)->value('separator'));
            $this->assertStringContainsString('|', (string) DB::table('product_code_configurations')->where('id', $configuration->id)->value('template'));
            $this->assertDatabaseHas('product_code_configuration_histories', [
                'configuration_id' => $configuration->id,
            ]);
        } finally {
            DB::rollBack();
        }
    }

    public function testSequenceCorrectionUpdatesTheCounterAndAuditsTheChange(): void
    {
        DB::beginTransaction();
        try {
            $session = $this->adminSession(['view_product_code_configuration', 'change_product_code_sequence']);
            $configuration = ProductCodeConfiguration::with('components')->where('is_active', 1)->first();
            $this->assertNotNull($configuration, 'A default product code configuration is required.');

            $sequenceId = DB::table('product_code_sequences')->insertGetId([
                'configuration_id' => $configuration->id,
                'sequence_scope' => $configuration->sequence_scope,
                'period_key' => 'GLOBAL',
                'scope_signature' => hash('sha256', 'test-sequence-'.$configuration->id),
                'last_number' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->withSession($session)
                ->post('/product-code-configuration/'.$sequenceId.'/reset-sequence', [
                    'reason' => 'Adjust after manual audit',
                    'next_number' => 25,
                ])
                ->assertSessionHas('message');

            $this->assertSame(24, (int) DB::table('product_code_sequences')->where('id', $sequenceId)->value('last_number'));
            $this->assertDatabaseHas('admin_activity_logs', [
                'action' => 'correct_product_code_sequence',
            ]);
        } finally {
            DB::rollBack();
        }
    }
}
