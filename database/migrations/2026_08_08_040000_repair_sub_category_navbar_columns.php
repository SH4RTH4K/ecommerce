<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sub_category')) {
            return;
        }

        Schema::table('sub_category', function (Blueprint $table): void {
            if (! Schema::hasColumn('sub_category', 'navbar_name')) {
                $table->string('navbar_name')->nullable()->after('sub_category_name');
            }

            if (! Schema::hasColumn('sub_category', 'show_in_navbar')) {
                $table->boolean('show_in_navbar')->default(true)->after('publication_status');
            }

            if (! Schema::hasColumn('sub_category', 'navbar_order')) {
                $table->unsignedInteger('navbar_order')->default(0)->after('display_order');
            }
        });
    }

    public function down(): void
    {
        // Keep this repair migration non-destructive.
    }
};
