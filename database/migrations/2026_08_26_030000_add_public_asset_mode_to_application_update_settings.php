<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('application_update_settings') && ! Schema::hasColumn('application_update_settings', 'public_asset_mode')) {
            Schema::table('application_update_settings', function (Blueprint $table) {
                $table->string('public_asset_mode', 30)->default('auto')->after('dependency_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('application_update_settings') && Schema::hasColumn('application_update_settings', 'public_asset_mode')) {
            Schema::table('application_update_settings', function (Blueprint $table) {
                $table->dropColumn('public_asset_mode');
            });
        }
    }
};
