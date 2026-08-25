<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_code_configurations') && ! Schema::hasColumn('product_code_configurations', 'version')) {
            Schema::table('product_code_configurations', function (Blueprint $table) {
                $table->unsignedInteger('version')->default(1)->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_code_configurations') && Schema::hasColumn('product_code_configurations', 'version')) {
            Schema::table('product_code_configurations', function (Blueprint $table) {
                $table->dropColumn('version');
            });
        }
    }
};
