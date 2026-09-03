<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $now = now();
        foreach ([
            'homepage_featured_products_limit' => '20',
            'homepage_featured_products_per_row' => '5',
            'homepage_new_arrivals_limit' => '20',
            'homepage_new_arrivals_per_row' => '5',
        ] as $key => $value) {
            if (! DB::table('site_settings')->where('setting_key', $key)->exists()) {
                DB::table('site_settings')->insert([
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('site_settings')) {
            DB::table('site_settings')->whereIn('setting_key', [
                'homepage_featured_products_limit',
                'homepage_featured_products_per_row',
                'homepage_new_arrivals_limit',
                'homepage_new_arrivals_per_row',
            ])->delete();
        }
    }
};
