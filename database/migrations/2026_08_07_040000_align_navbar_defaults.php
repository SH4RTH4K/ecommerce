<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlignNavbarDefaults extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('storefront_navbar_items')
            || ! Schema::hasColumn('category', 'show_in_navigation')
            || ! Schema::hasColumn('storefront_navbar_items', 'placement')
            || ! Schema::hasColumn('storefront_navbar_items', 'is_active')) {
            return;
        }

        $hiddenIds = DB::table('category')
            ->where('show_in_navigation', 0)
            ->pluck('category_id');

        if ($hiddenIds->isNotEmpty()) {
            DB::table('storefront_navbar_items')
                ->whereIn('category_id', $hiddenIds)
                ->update([
                    'placement' => 'HIDDEN',
                    'is_active' => 0,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down()
    {
        // The administrator's saved navbar layout is not reconstructed on rollback.
    }
}
