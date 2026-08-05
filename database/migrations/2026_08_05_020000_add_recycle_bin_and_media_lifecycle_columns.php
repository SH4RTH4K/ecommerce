<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addTrashColumns('product', 'id');
        $this->addTrashColumns('product_variants', 'id');
        $this->addTrashColumns('product_lots', 'id');
        $this->addTrashColumns('category', 'category_id');
        $this->addTrashColumns('sub_category', 'sub_category_id');
        $this->addTrashColumns('manufacturer', 'manufacturer_id');
        $this->addTrashColumns('companies', 'id');
        $this->addTrashColumns('product_series', 'id');
        $this->addTrashColumns('banners', 'id');
        $this->addTrashColumns('payment_methods', 'id');
        $this->addTrashColumns('top_announcements', 'id');
        $this->addTrashColumns('site_contact_items', 'id');

        if (! Schema::hasTable('media_cleanup_queue')) {
            Schema::create('media_cleanup_queue', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('path');
                $table->string('reason', 255);
                $table->string('entity_type', 100)->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->string('status', 20)->default('pending');
                $table->unsignedInteger('attempt_count')->default(0);
                $table->text('last_error')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_cleanup_queue');
        $this->dropTrashColumns('site_contact_items');
        $this->dropTrashColumns('top_announcements');
        $this->dropTrashColumns('payment_methods');
        $this->dropTrashColumns('banners');
        $this->dropTrashColumns('product_series');
        $this->dropTrashColumns('companies');
        $this->dropTrashColumns('manufacturer');
        $this->dropTrashColumns('sub_category');
        $this->dropTrashColumns('category');
        $this->dropTrashColumns('product_lots');
        $this->dropTrashColumns('product_variants');
        $this->dropTrashColumns('product');
    }

    private function addTrashColumns(string $table, string $idColumn): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (! Schema::hasColumn($table, 'deleted_at')) {
                $blueprint->timestamp('deleted_at')->nullable()->index();
            }

            if (! Schema::hasColumn($table, 'deleted_by')) {
                $blueprint->unsignedInteger('deleted_by')->nullable()->index();
            }

            if (! Schema::hasColumn($table, 'delete_reason')) {
                $blueprint->string('delete_reason', 255)->nullable();
            }
        });
    }

    private function dropTrashColumns(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            foreach (['deleted_at', 'deleted_by', 'delete_reason'] as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $blueprint->dropColumn($column);
                }
            }
        });
    }
};
