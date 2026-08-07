<?php

use App\Services\StorefrontNavbarService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddStorefrontNavbarLayoutSetting extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('site_settings')) return;

        DB::table('site_settings')->updateOrInsert(
            ['setting_key' => StorefrontNavbarService::LAYOUT_SETTING_KEY],
            [
                'setting_value' => json_encode(app(StorefrontNavbarService::class)->defaultLayout()),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down()
    {
        if (Schema::hasTable('site_settings')) {
            DB::table('site_settings')->where('setting_key', StorefrontNavbarService::LAYOUT_SETTING_KEY)->delete();
        }
    }
}
