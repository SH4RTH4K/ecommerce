<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RenameProductPricingColumns extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('product', 'product_price') && !Schema::hasColumn('product', 'offer_price')) {
            Schema::table('product', function (Blueprint $table) {
                $table->decimal('offer_price', 12, 2)->default(0)->after('product_price');
            });
            DB::table('product')->update(['offer_price' => DB::raw('product_price')]);
            Schema::table('product', function (Blueprint $table) {
                $table->dropColumn('product_price');
            });
        }

        if (Schema::hasColumn('product', 'old_price') && !Schema::hasColumn('product', 'regular_price')) {
            Schema::table('product', function (Blueprint $table) {
                $table->decimal('regular_price', 12, 2)->nullable()->after('offer_price');
            });
            DB::table('product')->update(['regular_price' => DB::raw('old_price')]);
            Schema::table('product', function (Blueprint $table) {
                $table->dropColumn('old_price');
            });
        }

        if (Schema::hasColumn('product', 'cost_price') && !Schema::hasColumn('product', 'purchase_price')) {
            Schema::table('product', function (Blueprint $table) {
                $table->decimal('purchase_price', 12, 2)->default(0)->after('offer_price');
            });
            DB::table('product')->update(['purchase_price' => DB::raw('cost_price')]);
            Schema::table('product', function (Blueprint $table) {
                $table->dropColumn('cost_price');
            });
        }

        if (Schema::hasColumn('order_items', 'price') && !Schema::hasColumn('order_items', 'offer_price')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->decimal('offer_price', 12, 2)->default(0)->after('sku');
            });
            DB::table('order_items')->update(['offer_price' => DB::raw('price')]);
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }

        if (Schema::hasColumn('order_items', 'unit_cost') && !Schema::hasColumn('order_items', 'unit_purchase_price')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->decimal('unit_purchase_price', 12, 2)->default(0)->after('offer_price');
            });
            DB::table('order_items')->update(['unit_purchase_price' => DB::raw('unit_cost')]);
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('unit_cost');
            });
        }
    }

    public function down()
    {
        $this->restoreColumn('product', 'offer_price', 'product_price', false);
        $this->restoreColumn('product', 'regular_price', 'old_price', true);
        $this->restoreColumn('product', 'purchase_price', 'cost_price', false);
        $this->restoreColumn('order_items', 'offer_price', 'price', false);
        $this->restoreColumn('order_items', 'unit_purchase_price', 'unit_cost', false);
    }

    private function restoreColumn($tableName, $current, $legacy, $nullable)
    {
        if (!Schema::hasColumn($tableName, $current) || Schema::hasColumn($tableName, $legacy)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($legacy, $nullable) {
            $column = $table->decimal($legacy, 12, 2);
            if ($nullable) {
                $column->nullable();
            } else {
                $column->default(0);
            }
        });
        DB::table($tableName)->update([$legacy => DB::raw($current)]);
        Schema::table($tableName, function (Blueprint $table) use ($current) {
            $table->dropColumn($current);
        });
    }
}
