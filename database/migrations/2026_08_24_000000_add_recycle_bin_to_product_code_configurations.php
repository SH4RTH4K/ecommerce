<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_code_configurations')) {
            return;
        }

        Schema::table('product_code_configurations', function (Blueprint $table) {
            if (! Schema::hasColumn('product_code_configurations', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasColumn('product_code_configurations', 'deleted_by')) {
                $table->unsignedInteger('deleted_by')->nullable()->index();
            }
            if (! Schema::hasColumn('product_code_configurations', 'delete_reason')) {
                $table->text('delete_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_code_configurations')) {
            return;
        }

        Schema::table('product_code_configurations', function (Blueprint $table) {
            if (Schema::hasColumn('product_code_configurations', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('product_code_configurations', 'deleted_by')) {
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn('product_code_configurations', 'delete_reason')) {
                $table->dropColumn('delete_reason');
            }
        });
    }
};
