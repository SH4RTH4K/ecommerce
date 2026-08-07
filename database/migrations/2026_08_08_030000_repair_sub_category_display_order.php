<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sub_category') || Schema::hasColumn('sub_category', 'display_order')) {
            return;
        }

        Schema::table('sub_category', function (Blueprint $table): void {
            $table->unsignedSmallInteger('display_order')->default(0)->after('sub_category_name');
        });
    }

    public function down(): void
    {
        // Keep this repair migration non-destructive so existing ordering data is preserved.
    }
};
