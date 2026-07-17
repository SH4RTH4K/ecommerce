<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReplaceLegacyLogoSetting extends Migration
{
    public function up()
    {
        if (Schema::hasTable('site_settings')) {
            DB::table('site_settings')
                ->where('setting_key', 'site_logo')
                ->whereIn('setting_value', [
                    'asset/front-end/img/logo.png',
                    '/asset/front-end/img/logo.png',
                ])
                ->update([
                    'setting_value' => 'asset/front-end/img/ecommerce-logo.png',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down()
    {
        if (Schema::hasTable('site_settings')) {
            DB::table('site_settings')
                ->where('setting_key', 'site_logo')
                ->where('setting_value', 'asset/front-end/img/ecommerce-logo.png')
                ->update([
                    'setting_value' => 'asset/front-end/img/logo.png',
                    'updated_at' => now(),
                ]);
        }
    }
}
