<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FlatStorefrontNavbarSettings extends Migration
{
    public function up()
    {
        if (Schema::hasTable('storefront_navbar_items')) {
            if (! Schema::hasColumn('storefront_navbar_items', 'show_in_navbar')) {
                Schema::table('storefront_navbar_items', function (Blueprint $table) {
                    $table->boolean('show_in_navbar')->default(false)->after('category_id');
                });
            }

            foreach (DB::table('storefront_navbar_items')->get(['id', 'placement', 'is_active']) as $item) {
                DB::table('storefront_navbar_items')->where('id', $item->id)->update([
                    'show_in_navbar' => (bool) $item->is_active && strtoupper((string) $item->placement) !== 'HIDDEN',
                    'updated_at' => now(),
                ]);
            }

            Schema::table('storefront_navbar_items', function (Blueprint $table) {
                $table->dropIndex(['placement', 'priority']);
                $table->dropIndex(['is_active', 'placement']);
                $table->dropColumn(['placement', 'is_active']);
            });
        }

        if (Schema::hasTable('sub_category')) {
            Schema::table('sub_category', function (Blueprint $table) {
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

            DB::table('sub_category')->update([
                'show_in_navbar' => 1,
                'navbar_order' => DB::raw('COALESCE(display_order, 0)'),
            ]);
        }

        if (Schema::hasTable('site_settings')) {
            DB::table('site_settings')->whereIn('setting_key', [
                'storefront_navbar_more_label',
                'storefront_navbar_overflow_mode',
            ])->delete();
        }
    }

    public function down()
    {
        // The old placement-based menu is intentionally not reconstructed.
    }
}
