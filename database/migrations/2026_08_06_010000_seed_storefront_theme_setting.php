<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedStorefrontThemeSetting extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('site_settings')) {
            return;
        }

        $exists = DB::table('site_settings')->where('setting_key', 'storefront_theme')->exists();
        if ($exists) {
            return;
        }

        DB::table('site_settings')->insert([
            'setting_key' => 'storefront_theme',
            'setting_value' => json_encode(['preset' => 'lucent-tech-bd'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        if (!Schema::hasTable('site_settings')) {
            return;
        }

        DB::table('site_settings')->where('setting_key', 'storefront_theme')->delete();
    }
}
