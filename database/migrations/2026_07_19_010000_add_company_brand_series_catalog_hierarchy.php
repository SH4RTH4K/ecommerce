<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('manufacturer', 'company_id')) {
            Schema::table('manufacturer', function (Blueprint $table) {
                $table->unsignedInteger('company_id')->nullable()->after('manufacturer_id')->index();
            });
        }

        if (! Schema::hasTable('product_series')) {
            Schema::create('product_series', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('manufacturer_id')->index();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['manufacturer_id', 'name']);
            });
        }

        if (! Schema::hasColumn('product', 'product_series_id')) {
            Schema::table('product', function (Blueprint $table) {
                $table->unsignedInteger('product_series_id')->nullable()->after('manufacturer_id')->index();
            });
        }

        $catalog = [
            'Dell Technologies' => ['Dell' => [], 'Alienware' => []],
            'HP Inc.' => ['HP' => ['OMEN', 'Victus'], 'HyperX' => []],
            'Lenovo Group' => ['Lenovo' => ['ThinkPad', 'IdeaPad', 'Legion', 'LOQ']],
            'Acer Inc.' => ['Acer' => ['Predator', 'Nitro']],
            'ASUSTeK' => ['ASUS' => ['ROG', 'TUF Gaming', 'ProArt']],
            'Apple Inc.' => ['Apple' => ['MacBook', 'Mac Mini', 'iMac', 'Mac Studio']],
            'Microsoft' => ['Microsoft' => ['Surface']],
            'MSI' => ['MSI' => ['Creator', 'Stealth', 'Raider', 'Katana']],
            'Samsung Electronics' => ['Samsung' => ['Galaxy Book']],
            'Huawei' => ['Huawei' => ['MateBook']],
            'Xiaomi' => ['Xiaomi' => ['RedmiBook']],
            'Razer Inc.' => ['Razer' => ['Blade']],
            'Gigabyte Technology' => ['Gigabyte' => ['AORUS']],
            'Toshiba/Dynabook' => ['Dynabook' => []],
            'Fujitsu' => ['Fujitsu' => []],
            'Walton' => ['Walton' => ['Prelude', 'Tamarind', 'Karonda']],
            'Intel' => ['Intel' => ['NUC']],
            'Zotac' => ['Zotac' => ['ZBOX']],
            'Minisforum' => ['Minisforum' => []],
            'Beelink' => ['Beelink' => []],
        ];

        DB::transaction(function () use ($catalog) {
            foreach ($catalog as $companyName => $brands) {
                $companyId = DB::table('companies')->where('name', $companyName)->value('id');
                if (! $companyId) {
                    $companyId = DB::table('companies')->insertGetId(['name' => $companyName, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
                }

                foreach ($brands as $brandName => $seriesNames) {
                    $brandId = DB::table('manufacturer')->where('manufacturer_name', $brandName)->value('manufacturer_id');
                    if ($brandId) {
                        DB::table('manufacturer')->where('manufacturer_id', $brandId)->update(['company_id' => $companyId]);
                    } else {
                        $brandId = DB::table('manufacturer')->insertGetId(['company_id' => $companyId, 'manufacturer_name' => $brandName, 'publication_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
                    }

                    foreach ($seriesNames as $seriesName) {
                        DB::table('product_series')->updateOrInsert(
                            ['manufacturer_id' => $brandId, 'name' => $seriesName],
                            ['is_active' => 1, 'updated_at' => now(), 'created_at' => now()]
                        );
                    }
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('product', 'product_series_id')) {
            Schema::table('product', fn (Blueprint $table) => $table->dropColumn('product_series_id'));
        }
        Schema::dropIfExists('product_series');
        if (Schema::hasColumn('manufacturer', 'company_id')) {
            Schema::table('manufacturer', fn (Blueprint $table) => $table->dropColumn('company_id'));
        }
        Schema::dropIfExists('companies');
    }
};
