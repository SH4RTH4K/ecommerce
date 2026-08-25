<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_code_configurations') && ! Schema::hasColumn('product_code_configurations', 'version')) {
            Schema::table('product_code_configurations', function (Blueprint $table) { $table->unsignedInteger('version')->default(1)->after('id'); });
        }
        if (Schema::hasTable('product_code_histories')) {
            Schema::table('product_code_histories', function (Blueprint $table) {
                if (Schema::hasColumn('product_code_histories', 'product_id')) {
                    $table->unsignedInteger('product_id')->nullable()->change();
                }
                if (! Schema::hasColumn('product_code_histories', 'entity_type')) $table->string('entity_type', 30)->default('product')->after('product_id')->index();
                if (! Schema::hasColumn('product_code_histories', 'entity_id')) $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type')->index();
                if (! Schema::hasColumn('product_code_histories', 'entity_name')) $table->string('entity_name', 255)->nullable()->after('entity_id');
                if (! Schema::hasColumn('product_code_histories', 'configuration_version')) $table->unsignedInteger('configuration_version')->nullable()->after('configuration_id');
                if (! Schema::hasColumn('product_code_histories', 'change_type')) $table->string('change_type', 40)->default('OTHER')->after('reason');
                if (! Schema::hasColumn('product_code_histories', 'batch_id')) $table->unsignedBigInteger('batch_id')->nullable()->after('change_type')->index();
            });
        }

        if (Schema::hasTable('product_code_configuration_histories')) {
            Schema::table('product_code_configuration_histories', function (Blueprint $table) {
                if (! Schema::hasColumn('product_code_configuration_histories', 'existing_record_policy')) $table->string('existing_record_policy', 30)->default('FUTURE_ONLY')->after('new_template');
                if (! Schema::hasColumn('product_code_configuration_histories', 'cascade_policy')) $table->string('cascade_policy', 30)->default('NONE')->after('existing_record_policy');
                if (! Schema::hasColumn('product_code_configuration_histories', 'configuration_version')) $table->unsignedInteger('configuration_version')->nullable()->after('configuration_id');
            });
        }

        if (! Schema::hasTable('product_code_regeneration_batches')) {
            Schema::create('product_code_regeneration_batches', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('code_type', 30)->index();
                $table->unsignedInteger('configuration_id')->nullable()->index();
                $table->unsignedInteger('configuration_version')->nullable();
                $table->string('mode', 30);
                $table->string('cascade_policy', 30)->default('NONE');
                $table->boolean('preserve_sequence')->default(true);
                $table->unsignedInteger('total_records')->default(0);
                $table->unsignedInteger('success_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->unsignedInteger('skipped_count')->default(0);
                $table->unsignedInteger('initiated_by')->nullable()->index();
                $table->string('status', 20)->default('PREVIEW');
                $table->text('reason')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_code_regeneration_batches');
        if (Schema::hasTable('product_code_configurations') && Schema::hasColumn('product_code_configurations', 'version')) Schema::table('product_code_configurations', function (Blueprint $table) { $table->dropColumn('version'); });
    }
};
