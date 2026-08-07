<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('admin_roles')->get(['id', 'name', 'permissions']) as $role) {
            $permissions = json_decode((string) $role->permissions, true);
            $permissions = is_array($permissions) ? $permissions : [];

            if ($role->name === 'Super Admin' || in_array('settings', $permissions, true)) {
                $permissions = array_values(array_unique(array_merge($permissions, [
                    'view_storefront_navbar',
                    'change_storefront_navbar',
                ])));

                DB::table('admin_roles')->where('id', $role->id)->update([
                    'permissions' => json_encode($permissions),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep permission repair non-destructive.
    }
};
