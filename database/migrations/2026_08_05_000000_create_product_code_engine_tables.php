<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->addMasterDataColumns();
        $this->addProductColumns();
        $this->createEngineTables();
        $this->seedDefaultConfiguration();
        $this->backfillMasterDataCodes();
        $this->backfillProductCodes();
    }

    public function down(): void
    {
        Schema::dropIfExists('product_code_configuration_histories');
        Schema::dropIfExists('product_code_histories');
        Schema::dropIfExists('product_code_sequences');
        Schema::dropIfExists('product_code_components');
        Schema::dropIfExists('product_code_configurations');

        if (Schema::hasColumn('product', 'product_code')) {
            Schema::table('product', function (Blueprint $table) {
                $table->dropUnique('product_product_code_unique');
                $table->dropColumn(['product_code', 'company_id', 'branch_id']);
            });
        }

        if (Schema::hasColumn('companies', 'company_code')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropUnique('companies_company_code_unique');
                $table->dropColumn('company_code');
            });
        }

        if (Schema::hasColumn('category', 'category_code')) {
            Schema::table('category', function (Blueprint $table) {
                $table->dropUnique('category_category_code_unique');
                $table->dropUnique('category_slug_unique');
                $table->dropColumn(['category_code', 'slug']);
            });
        }

        if (Schema::hasColumn('sub_category', 'subcategory_code')) {
            Schema::table('sub_category', function (Blueprint $table) {
                $table->dropUnique('sub_category_subcategory_code_unique');
                $table->dropUnique('sub_category_category_id_slug_unique');
                $table->dropColumn(['subcategory_code', 'slug']);
            });
        }

        if (Schema::hasColumn('manufacturer', 'brand_code')) {
            Schema::table('manufacturer', function (Blueprint $table) {
                $table->dropUnique('manufacturer_brand_code_unique');
                $table->dropUnique('manufacturer_company_id_slug_unique');
                $table->dropColumn(['brand_code', 'slug']);
            });
        }

        if (Schema::hasColumn('product_series', 'series_code')) {
            Schema::table('product_series', function (Blueprint $table) {
                $table->dropUnique('product_series_series_code_unique');
                $table->dropUnique('product_series_manufacturer_id_slug_unique');
                $table->dropColumn(['series_code', 'slug']);
            });
        }
    }

    private function addMasterDataColumns(): void
    {
        if (Schema::hasTable('companies') && ! Schema::hasColumn('companies', 'company_code')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('company_code', 30)->nullable()->after('name')->unique('companies_company_code_unique');
            });
        }

        if (Schema::hasTable('category')) {
            Schema::table('category', function (Blueprint $table) {
                if (! Schema::hasColumn('category', 'category_code')) {
                    $table->string('category_code', 30)->nullable()->after('category_name');
                }

                if (! Schema::hasColumn('category', 'slug')) {
                    $table->string('slug', 160)->nullable()->after('category_code');
                }
            });

            if (Schema::hasColumn('category', 'category_code')) {
                Schema::table('category', function (Blueprint $table) {
                    $table->unique('category_code', 'category_category_code_unique');
                    $table->unique('slug', 'category_slug_unique');
                });
            }
        }

        if (Schema::hasTable('sub_category')) {
            Schema::table('sub_category', function (Blueprint $table) {
                if (! Schema::hasColumn('sub_category', 'subcategory_code')) {
                    $table->string('subcategory_code', 30)->nullable()->after('sub_category_name');
                }

                if (! Schema::hasColumn('sub_category', 'slug')) {
                    $table->string('slug', 160)->nullable()->after('subcategory_code');
                }
            });

            if (Schema::hasColumn('sub_category', 'subcategory_code')) {
                Schema::table('sub_category', function (Blueprint $table) {
                    $table->unique('subcategory_code', 'sub_category_subcategory_code_unique');
                    $table->unique(['category_id', 'slug'], 'sub_category_category_id_slug_unique');
                });
            }
        }

        if (Schema::hasTable('manufacturer')) {
            Schema::table('manufacturer', function (Blueprint $table) {
                if (! Schema::hasColumn('manufacturer', 'brand_code')) {
                    $table->string('brand_code', 30)->nullable()->after('manufacturer_name');
                }

                if (! Schema::hasColumn('manufacturer', 'slug')) {
                    $table->string('slug', 160)->nullable()->after('brand_code');
                }
            });

            if (Schema::hasColumn('manufacturer', 'brand_code')) {
                Schema::table('manufacturer', function (Blueprint $table) {
                    $table->unique('brand_code', 'manufacturer_brand_code_unique');
                    $table->unique(['company_id', 'slug'], 'manufacturer_company_id_slug_unique');
                });
            }
        }

        if (Schema::hasTable('product_series')) {
            Schema::table('product_series', function (Blueprint $table) {
                if (! Schema::hasColumn('product_series', 'series_code')) {
                    $table->string('series_code', 30)->nullable()->after('name');
                }

                if (! Schema::hasColumn('product_series', 'slug')) {
                    $table->string('slug', 160)->nullable()->after('series_code');
                }
            });

            if (Schema::hasColumn('product_series', 'series_code')) {
                Schema::table('product_series', function (Blueprint $table) {
                    $table->unique('series_code', 'product_series_series_code_unique');
                    $table->unique(['manufacturer_id', 'slug'], 'product_series_manufacturer_id_slug_unique');
                });
            }
        }
    }

    private function addProductColumns(): void
    {
        if (! Schema::hasTable('product')) {
            return;
        }

        Schema::table('product', function (Blueprint $table) {
            if (! Schema::hasColumn('product', 'product_code')) {
                $table->string('product_code', 100)->nullable()->after('sku');
            }

            if (! Schema::hasColumn('product', 'company_id')) {
                $table->unsignedInteger('company_id')->nullable()->after('manufacturer_id')->index('product_company_id_index');
            }

            if (! Schema::hasColumn('product', 'branch_id')) {
                $table->unsignedInteger('branch_id')->nullable()->after('company_id')->index('product_branch_id_index');
            }
        });

        Schema::table('product', function (Blueprint $table) {
            $table->unique('product_code', 'product_product_code_unique');
        });
    }

    private function createEngineTables(): void
    {
        if (! Schema::hasTable('product_code_configurations')) {
            Schema::create('product_code_configurations', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 160);
                $table->unsignedInteger('company_id')->nullable()->index();
                $table->unsignedInteger('branch_id')->nullable()->index();
                $table->boolean('auto_generate')->default(true);
                $table->text('template')->nullable();
                $table->string('separator', 10)->default('-');
                $table->string('sequence_scope', 30)->default('global');
                $table->unsignedInteger('sequence_length')->default(6);
                $table->unsignedInteger('sequence_start')->default(1);
                $table->string('reset_rule', 30)->default('never');
                $table->boolean('strict_mode')->default(true);
                $table->boolean('skip_empty_components')->default(false);
                $table->boolean('allow_manual_override')->default(false);
                $table->boolean('allow_regeneration')->default(true);
                $table->timestamp('effective_from')->nullable();
                $table->timestamp('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('created_by')->nullable()->index();
                $table->unsignedInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->index(['company_id', 'branch_id', 'is_active'], 'product_code_configurations_scope_index');
            });
        }

        if (! Schema::hasTable('product_code_components')) {
            Schema::create('product_code_components', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('configuration_id')->index();
                $table->string('component_type', 50);
                $table->unsignedSmallInteger('position')->default(1);
                $table->string('static_value', 255)->nullable();
                $table->json('format_options')->nullable();
                $table->boolean('is_required')->default(true);
                $table->timestamps();
                $table->index(['configuration_id', 'position'], 'product_code_components_position_index');
            });
        }

        if (! Schema::hasTable('product_code_sequences')) {
            Schema::create('product_code_sequences', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('configuration_id')->index();
                $table->unsignedInteger('company_id')->nullable()->index();
                $table->unsignedInteger('branch_id')->nullable()->index();
                $table->unsignedInteger('category_id')->nullable()->index();
                $table->unsignedInteger('subcategory_id')->nullable()->index();
                $table->unsignedInteger('brand_id')->nullable()->index();
                $table->unsignedInteger('series_id')->nullable()->index();
                $table->string('sequence_scope', 30)->default('global');
                $table->string('period_key', 40)->default('GLOBAL');
                $table->string('scope_signature', 64);
                $table->unsignedBigInteger('last_number')->default(0);
                $table->timestamps();
                $table->unique(['configuration_id', 'scope_signature', 'period_key'], 'product_code_sequences_scope_unique');
            });
        }

        if (! Schema::hasTable('product_code_histories')) {
            Schema::create('product_code_histories', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('configuration_id')->nullable()->index();
                $table->unsignedInteger('product_id')->index();
                $table->string('old_code', 100)->nullable();
                $table->string('new_code', 100);
                $table->text('reason')->nullable();
                $table->unsignedInteger('changed_by')->nullable()->index();
                $table->timestamp('changed_at')->index();
            });
        }

        if (! Schema::hasTable('product_code_configuration_histories')) {
            Schema::create('product_code_configuration_histories', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('configuration_id')->nullable()->index();
                $table->text('old_template')->nullable();
                $table->text('new_template')->nullable();
                $table->json('old_settings')->nullable();
                $table->json('new_settings')->nullable();
                $table->unsignedInteger('changed_by')->nullable()->index();
                $table->timestamp('changed_at')->index();
            });
        }
    }

    private function seedDefaultConfiguration(): void
    {
        if (! Schema::hasTable('product_code_configurations')) {
            return;
        }

        if (DB::table('product_code_configurations')->exists()) {
            return;
        }

        $now = now();
        $configurationId = DB::table('product_code_configurations')->insertGetId([
            'name' => config('product_code.default_name', 'Default Product Code'),
            'auto_generate' => (int) config('product_code.default_auto_generate', true),
            'template' => config('product_code.default_template', '{CATEGORY}-{BRAND}-{SEQUENCE}'),
            'separator' => config('product_code.default_separator', '-'),
            'sequence_scope' => config('product_code.default_sequence_scope', 'global'),
            'sequence_length' => (int) config('product_code.default_sequence_length', 6),
            'sequence_start' => (int) config('product_code.default_sequence_start', 1),
            'reset_rule' => config('product_code.default_reset_rule', 'never'),
            'strict_mode' => (int) config('product_code.default_strict_mode', true),
            'skip_empty_components' => (int) config('product_code.default_skip_empty_components', false),
            'allow_manual_override' => (int) config('product_code.default_allow_manual_override', false),
            'allow_regeneration' => (int) config('product_code.default_allow_regeneration', true),
            'effective_from' => null,
            'effective_to' => null,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $components = [
            ['component_type' => 'category', 'position' => 1, 'is_required' => 1],
            ['component_type' => 'brand', 'position' => 2, 'is_required' => 1],
            ['component_type' => 'sequence', 'position' => 3, 'is_required' => 1],
        ];

        foreach ($components as $component) {
            DB::table('product_code_components')->insert([
                'configuration_id' => $configurationId,
                'component_type' => $component['component_type'],
                'position' => $component['position'],
                'static_value' => null,
                'format_options' => null,
                'is_required' => $component['is_required'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function backfillMasterDataCodes(): void
    {
        $this->backfillCompanyCodes();
        $this->backfillCategoryCodes();
        $this->backfillSubCategoryCodes();
        $this->backfillBrandCodes();
        $this->backfillSeriesCodes();
    }

    private function backfillCompanyCodes(): void
    {
        if (! Schema::hasColumn('companies', 'company_code')) {
            return;
        }

        $used = DB::table('companies')->whereNotNull('company_code')->pluck('company_code')->map(static fn ($value) => strtoupper((string) $value))->all();
        foreach (DB::table('companies')->orderBy('id')->get() as $company) {
            $code = $this->uniqueBusinessCode((string) ($company->company_code ?: suggest_business_code((string) $company->name, 8)), $used, 30, 'CO');
            DB::table('companies')->where('id', $company->id)->update([
                'company_code' => $code,
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillCategoryCodes(): void
    {
        if (! Schema::hasColumn('category', 'category_code')) {
            return;
        }

        $usedCodes = DB::table('category')->whereNotNull('category_code')->pluck('category_code')->map(static fn ($value) => strtoupper((string) $value))->all();
        $usedSlugs = DB::table('category')->whereNotNull('slug')->pluck('slug')->map(static fn ($value) => (string) $value)->all();

        foreach (DB::table('category')->orderBy('category_id')->get() as $category) {
            $code = $this->uniqueBusinessCode((string) ($category->category_code ?: suggest_business_code((string) $category->category_name, 8)), $usedCodes, 30, 'CAT');
            $slug = $this->uniqueSlug((string) ($category->slug ?: Str::slug((string) $category->category_name)), $usedSlugs, (int) $category->category_id);
            DB::table('category')->where('category_id', $category->category_id)->update([
                'category_code' => $code,
                'slug' => $slug,
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillSubCategoryCodes(): void
    {
        if (! Schema::hasColumn('sub_category', 'subcategory_code')) {
            return;
        }

        $usedCodes = DB::table('sub_category')->whereNotNull('subcategory_code')->pluck('subcategory_code')->map(static fn ($value) => strtoupper((string) $value))->all();
        $usedSlugsByCategory = [];
        foreach (DB::table('sub_category')->whereNotNull('slug')->get(['category_id', 'slug']) as $row) {
            $usedSlugsByCategory[(int) $row->category_id][] = (string) $row->slug;
        }

        foreach (DB::table('sub_category')->orderBy('sub_category_id')->get() as $subCategory) {
            $code = $this->uniqueBusinessCode((string) ($subCategory->subcategory_code ?: suggest_business_code((string) $subCategory->sub_category_name, 8)), $usedCodes, 30, 'SUB');
            $slugBase = (string) ($subCategory->slug ?: Str::slug((string) $subCategory->sub_category_name));
            $slug = $this->uniqueSlug($slugBase, $usedSlugsByCategory[(int) $subCategory->category_id] ?? [], (int) $subCategory->sub_category_id);
            $usedSlugsByCategory[(int) $subCategory->category_id][] = $slug;

            DB::table('sub_category')->where('sub_category_id', $subCategory->sub_category_id)->update([
                'subcategory_code' => $code,
                'slug' => $slug,
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillBrandCodes(): void
    {
        if (! Schema::hasColumn('manufacturer', 'brand_code')) {
            return;
        }

        $usedCodes = DB::table('manufacturer')->whereNotNull('brand_code')->pluck('brand_code')->map(static fn ($value) => strtoupper((string) $value))->all();
        $usedSlugsByCompany = [];
        foreach (DB::table('manufacturer')->whereNotNull('slug')->get(['company_id', 'slug']) as $row) {
            $usedSlugsByCompany[(int) ($row->company_id ?? 0)][] = (string) $row->slug;
        }

        foreach (DB::table('manufacturer')->orderBy('manufacturer_id')->get() as $brand) {
            $code = $this->uniqueBusinessCode((string) ($brand->brand_code ?: suggest_business_code((string) $brand->manufacturer_name, 8)), $usedCodes, 30, 'BR');
            $slugBase = (string) ($brand->slug ?: Str::slug((string) $brand->manufacturer_name));
            $companyKey = (int) ($brand->company_id ?? 0);
            $slug = $this->uniqueSlug($slugBase, $usedSlugsByCompany[$companyKey] ?? [], (int) $brand->manufacturer_id);
            $usedSlugsByCompany[$companyKey][] = $slug;

            DB::table('manufacturer')->where('manufacturer_id', $brand->manufacturer_id)->update([
                'brand_code' => $code,
                'slug' => $slug,
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillSeriesCodes(): void
    {
        if (! Schema::hasColumn('product_series', 'series_code')) {
            return;
        }

        $usedCodes = DB::table('product_series')->whereNotNull('series_code')->pluck('series_code')->map(static fn ($value) => strtoupper((string) $value))->all();
        $usedSlugsByBrand = [];
        foreach (DB::table('product_series')->whereNotNull('slug')->get(['manufacturer_id', 'slug']) as $row) {
            $usedSlugsByBrand[(int) $row->manufacturer_id][] = (string) $row->slug;
        }

        foreach (DB::table('product_series')->orderBy('id')->get() as $series) {
            $code = $this->uniqueBusinessCode((string) ($series->series_code ?: suggest_business_code((string) $series->name, 8)), $usedCodes, 30, 'SR');
            $slugBase = (string) ($series->slug ?: Str::slug((string) $series->name));
            $brandKey = (int) ($series->manufacturer_id ?? 0);
            $slug = $this->uniqueSlug($slugBase, $usedSlugsByBrand[$brandKey] ?? [], (int) $series->id);
            $usedSlugsByBrand[$brandKey][] = $slug;

            DB::table('product_series')->where('id', $series->id)->update([
                'series_code' => $code,
                'slug' => $slug,
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillProductCodes(): void
    {
        if (! Schema::hasColumn('product', 'product_code')) {
            return;
        }

        foreach (DB::table('product')->orderBy('id')->get() as $product) {
            $productCode = trim((string) $product->sku);
            if ($productCode === '') {
                $productCode = 'PRD-'.str_pad((string) $product->id, 6, '0', STR_PAD_LEFT);
            }

            $companyId = null;
            if (! empty($product->manufacturer_id)) {
                $companyId = DB::table('manufacturer')->where('manufacturer_id', $product->manufacturer_id)->value('company_id');
            }

            DB::table('product')->where('id', $product->id)->update([
                'product_code' => $productCode,
                'sku' => trim((string) $product->sku) !== '' ? $product->sku : $productCode,
                'company_id' => $companyId ?: null,
                'updated_at' => now(),
            ]);
        }
    }

    private function uniqueBusinessCode(string $value, array &$used, int $maxLength, string $fallbackPrefix): string
    {
        $value = normalize_business_code($value, $maxLength) ?: $fallbackPrefix;
        $candidate = $value;
        $counter = 2;

        while (in_array($candidate, $used, true)) {
            $suffix = (string) $counter;
            $prefix = substr($value, 0, max(1, $maxLength - strlen($suffix)));
            $candidate = $this->trimCode($prefix.$suffix, $maxLength);
            $counter++;
        }

        $used[] = $candidate;

        return $candidate;
    }

    private function uniqueSlug(string $value, array $used, int $fallbackId): string
    {
        $value = trim($value) !== '' ? $value : 'item-'.$fallbackId;
        $candidate = $this->trimSlug(Str::slug($value), 160);
        if ($candidate === '') {
            $candidate = 'item-'.$fallbackId;
        }

        $counter = 2;
        while (in_array($candidate, $used, true)) {
            $candidate = $this->trimSlug(Str::slug($value.'-'.$counter), 160);
            if ($candidate === '') {
                $candidate = 'item-'.$fallbackId.'-'.$counter;
            }
            $counter++;
        }

        return $candidate;
    }

    private function trimCode(string $value, int $maxLength): string
    {
        $value = strtoupper(trim($value));
        if ($value === '') {
            return '';
        }

        return substr($value, 0, max(1, $maxLength));
    }

    private function trimSlug(string $value, int $maxLength): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        return substr($value, 0, max(1, $maxLength));
    }
};
