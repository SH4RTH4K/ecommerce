<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStorefrontNavbarItems extends Migration
{
    public function up()
    {
        // Some production backups already contain the newer navbar table.
        // Preserve that configuration instead of recreating the table.
        if (Schema::hasTable('storefront_navbar_items')) {
            return;
        }

        Schema::create('storefront_navbar_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('category_id')->unique();
            $table->string('display_name')->nullable();
            $table->string('placement', 20)->default('PRIMARY');
            $table->unsignedInteger('priority')->default(10);
            $table->boolean('show_subcategories')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['placement', 'priority']);
            $table->index(['is_active', 'placement']);
        });

        $now = date('Y-m-d H:i:s');
        $categoryQuery = DB::table('category')->whereNull('deleted_at');
        if (Schema::hasColumn('category', 'show_in_navigation')) {
            $categoryQuery->where('show_in_navigation', 1);
        }
        $categories = $categoryQuery
            ->orderByRaw('CASE WHEN display_order IS NULL OR display_order = 0 THEN 999999 ELSE display_order END')
            ->orderBy('category_name')
            ->get(['category_id']);

        foreach ($categories as $index => $category) {
            DB::table('storefront_navbar_items')->insert([
                'category_id' => $category->category_id,
                'display_name' => null,
                'placement' => $index < 18 ? 'PRIMARY' : 'MORE',
                'priority' => ($index + 1) * 10,
                'show_subcategories' => 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('site_settings')) {
            DB::table('site_settings')->updateOrInsert(
                ['setting_key' => 'storefront_navbar_more_label'],
                ['setting_value' => 'More', 'created_at' => $now, 'updated_at' => $now]
            );
            DB::table('site_settings')->updateOrInsert(
                ['setting_key' => 'storefront_navbar_overflow_mode'],
                ['setting_value' => 'MANUAL_RESPONSIVE', 'created_at' => $now, 'updated_at' => $now]
            );
        }

        foreach (DB::table('admin_roles')->get(['id', 'name', 'permissions']) as $role) {
            $permissions = json_decode($role->permissions, true);
            $permissions = is_array($permissions) ? $permissions : [];
            if ($role->name === 'Super Admin' || in_array('settings', $permissions, true)) {
                $permissions = array_values(array_unique(array_merge($permissions, [
                    'view_storefront_navbar',
                    'change_storefront_navbar',
                ])));
                DB::table('admin_roles')->where('id', $role->id)->update([
                    'permissions' => json_encode($permissions),
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('storefront_navbar_items');
        if (Schema::hasTable('site_settings')) {
            DB::table('site_settings')->whereIn('setting_key', [
                'storefront_navbar_more_label',
                'storefront_navbar_overflow_mode',
            ])->delete();
        }
    }
}
