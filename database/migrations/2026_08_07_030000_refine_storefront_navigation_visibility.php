<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefineStorefrontNavigationVisibility extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('category', 'show_in_navigation')) {
            Schema::table('category', function (Blueprint $table) {
                $table->boolean('show_in_navigation')->default(true)->after('is_featured');
            });
        }

        // These remain valid catalog categories, but their menu destinations are
        // already represented as subcategories under the primary navigation.
        DB::table('category')
            ->whereIn('category_name', ['Air Conditioner', 'Air Cooler', 'Air Purifier', 'Access Control', 'ROUTER'])
            ->update(['show_in_navigation' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function down()
    {
        DB::table('category')
            ->whereIn('category_name', ['Air Conditioner', 'Air Cooler', 'Air Purifier', 'Access Control', 'ROUTER'])
            ->update(['show_in_navigation' => 1, 'updated_at' => date('Y-m-d H:i:s')]);

        if (Schema::hasColumn('category', 'show_in_navigation')) {
            Schema::table('category', function (Blueprint $table) {
                $table->dropColumn('show_in_navigation');
            });
        }
    }
}
