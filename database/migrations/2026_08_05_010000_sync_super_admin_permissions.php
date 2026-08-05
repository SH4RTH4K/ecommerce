<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'dashboard',
            'catalog',
            'inventory',
            'orders',
            'customers',
            'marketing',
            'reports',
            'settings',
            'staff',
            'view_product_code_configuration',
            'change_product_code_configuration',
            'view_product_code_sequence',
            'change_product_code_sequence',
            'reset_product_code_sequence',
            'override_product_code',
            'regenerate_product_code',
            'view_product_code_history',
            'view_recycle_bin',
            'restore_deleted_items',
            'permanently_delete_items',
            'empty_recycle_bin',
            'view_orphan_media',
            'cleanup_orphan_media',
        ];

        DB::table('admin_roles')
            ->where('name', 'Super Admin')
            ->update([
                'permissions' => json_encode($permissions),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $permissions = [
            'dashboard',
            'catalog',
            'inventory',
            'orders',
            'customers',
            'marketing',
            'reports',
            'settings',
            'staff',
        ];

        DB::table('admin_roles')
            ->where('name', 'Super Admin')
            ->update([
                'permissions' => json_encode($permissions),
                'updated_at' => now(),
            ]);
    }
};
