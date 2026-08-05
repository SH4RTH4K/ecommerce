<?php

namespace Tests\Feature;

use App\Banner;
use App\Services\MediaLifecycleService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecycleBinLifecycleTest extends TestCase
{
    private function adminSession(array $extraPermissions = []): array
    {
        $admin = DB::table('tbl_admin')->where('is_active', 1)->orderBy('admin_id')->first();
        $this->assertNotNull($admin, 'An active administrator is required for this test.');

        $role = DB::table('admin_roles')->where('id', $admin->role_id)->first();
        $permissions = [];
        if ($role && ! empty($role->permissions)) {
            $decoded = json_decode($role->permissions, true);
            if (is_array($decoded)) {
                $permissions = $decoded;
            }
        }

        $permissions = array_values(array_unique(array_merge($permissions, [
            'settings',
            'view_recycle_bin',
            'restore_deleted_items',
            'permanently_delete_items',
        ], $extraPermissions)));

        DB::table('admin_roles')->where('id', $admin->role_id)->update([
            'permissions' => json_encode($permissions),
            'updated_at' => now(),
        ]);

        return ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name];
    }

    public function testBannerCanMoveToRecycleBinRestoreAndPurgeItsUnusedMedia(): void
    {
        DB::beginTransaction();
        $sharedPath = null;
        $mobilePath = null;
        try {
            $directory = public_path('asset/front-end/img/banners');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $suffix = str_random(10);
            $sharedPath = 'asset/front-end/img/banners/shared-'.$suffix.'.jpg';
            $mobilePath = 'asset/front-end/img/banners/mobile-'.$suffix.'.jpg';
            file_put_contents(public_path($sharedPath), 'shared-banner');
            file_put_contents(public_path($mobilePath), 'mobile-banner');

            $keeper = Banner::create([
                'banner_type' => 'information',
                'title' => 'Keeper '.$suffix,
                'image_path' => $sharedPath,
                'image_position' => 'center',
                'display_order' => 0,
                'is_active' => 0,
            ]);

            $banner = Banner::create([
                'banner_type' => 'information',
                'title' => 'Recycle '.$suffix,
                'image_path' => $sharedPath,
                'mobile_image' => $mobilePath,
                'image_position' => 'center',
                'display_order' => 1,
                'is_active' => 1,
            ]);

            $session = $this->adminSession();
            $this->withSession($session)->post('/delete-banner/'.$banner->id)->assertRedirect('/banner-management')->assertSessionHas('message');

            $trashed = Banner::withTrashed()->find($banner->id);
            $this->assertNotNull($trashed);
            $this->assertTrue($trashed->trashed());
            $this->assertFileExists(public_path($sharedPath));
            $this->assertFileExists(public_path($mobilePath));

            $this->withSession($session)->post('/recycle-bin/banner/'.$banner->id.'/restore')
                ->assertRedirect();
            $restored = Banner::find($banner->id);
            $this->assertNotNull($restored);
            $this->assertFalse($restored->trashed());

            $this->withSession($session)->post('/delete-banner/'.$banner->id)->assertRedirect('/banner-management')->assertSessionHas('message');
            $this->withSession($session)->delete('/recycle-bin/banner/'.$banner->id, [
                'confirm_text' => 'DELETE',
            ])->assertRedirect('/recycle-bin')->assertSessionHas('message');

            $this->assertNull(Banner::withTrashed()->find($banner->id));
            $this->assertFileExists(public_path($sharedPath));
            $this->assertFileDoesNotExist(public_path($mobilePath));
            $this->assertTrue(Banner::find($keeper->id) !== null);
        } finally {
            DB::rollBack();
            if ($sharedPath && is_file(public_path($sharedPath))) {
                unlink(public_path($sharedPath));
            }
            if ($mobilePath && is_file(public_path($mobilePath))) {
                unlink(public_path($mobilePath));
            }
        }
    }

    public function testOrphanScannerDryRunLeavesRecentlyCreatedFilesUntouched(): void
    {
        $path = null;
        try {
            $directory = public_path('asset/front-end/img/Product_image');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $suffix = str_random(10);
            $path = 'asset/front-end/img/Product_image/orphan-'.$suffix.'.png';
            file_put_contents(public_path($path), 'orphan-product-media');
            @touch(public_path($path), now()->subDays(3)->timestamp);

            $service = app(MediaLifecycleService::class);
            $report = $service->scanManagedMedia([
                'entity' => 'products',
                'older_than_days' => 1,
                'delete' => false,
            ]);

            $match = collect($report['rows'])->firstWhere('path', $path);
            $this->assertNotNull($match);
            $this->assertSame('ORPHAN', $match['status']);

            Artisan::call('scan_orphan_media', [
                '--dry-run' => true,
                '--entity' => 'products',
                '--older-than' => 24,
            ]);

            $this->assertFileExists(public_path($path));
        } finally {
            if ($path && is_file(public_path($path))) {
                unlink(public_path($path));
            }
        }
    }
}
