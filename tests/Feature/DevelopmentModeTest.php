<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DevelopmentModeTest extends TestCase
{
    public function testPublicPagesRemainAvailableWhenDevelopmentModeIsOff()
    {
        DB::beginTransaction();
        try {
            $this->putSetting('development_mode_enabled', '0');
            Cache::forget('site-settings');

            $this->get('/')->assertStatus(200)->assertDontSee('Website Under Development');
            $this->get('/cart')->assertStatus(200)->assertDontSee('Scheduled Maintenance');
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
        }
    }

    public function testPublicPagesAreBlockedButAdministratorAccessRemainsAvailable()
    {
        DB::beginTransaction();
        try {
            $this->enableMode();
            $admin = DB::table('tbl_admin')->where('is_active', 1)->first();
            $this->assertNotNull($admin);

            foreach (['/', '/cart', '/checkout', '/login', '/product-details/999'] as $path) {
                $this->get($path)->assertStatus(503)
                    ->assertHeader('Retry-After', '3600')
                    ->assertSee('Private test maintenance')
                    ->assertSee('asset/front-end/img/branding/test-favicon.png', false)
                    ->assertDontSee('Featured Categories');
            }

            $this->get('/admin/login')->assertStatus(200);
            $this->withSession(['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name])
                ->get('/dashboard')->assertStatus(200)->assertSee('Development Mode Active');
            $this->withSession(['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name])
                ->post('/admin/logout')->assertRedirect(route('admin.login'));
            $this->withSession(['admin_id' => 999999, 'admin_name' => 'invalid'])
                ->get('/')->assertStatus(503);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
        }
    }

    public function testDevelopmentSettingsAreValidatedAndSanitized()
    {
        DB::beginTransaction();
        try {
            $admin = DB::table('tbl_admin')->where('is_active', 1)->first();
            $this->assertNotNull($admin);
            $session = ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name];
            $this->putSetting('development_mode_enabled', '0');
            Cache::forget('site-settings');

            $this->withSession($session)->from('/site-customization')->post('/site-settings', [
                'site_name' => 'Test Store',
                'development_mode_enabled' => '1',
                'development_mode_message_type' => 'invalid',
                'development_mode_title' => 'Test',
                'development_mode_message' => 'Test',
                'development_mode_show_admin_login' => '1',
                'development_mode_login_button_text' => 'Admin Login',
            ])->assertRedirect('/site-customization')->assertSessionHasErrors('development_mode_message_type');

            $this->withSession($session)->post('/site-settings', [
                'site_name' => 'Test Store',
                'development_mode_enabled' => '1',
                'development_mode_message_type' => 'custom',
                'development_mode_title' => '<script>alert(1)</script> Safe title',
                'development_mode_message' => '<b>Plain message</b>',
                'development_mode_additional_message' => '',
                'development_mode_availability_text' => 'Back soon',
                'development_mode_show_admin_login' => '0',
                'development_mode_login_button_text' => 'Staff Login',
            ])->assertRedirect('/site-customization#development-mode')
                ->assertSessionHas('message', 'Development Mode has been enabled. Public visitors will now see the configured Development Mode message.');

            $this->assertSame('Safe title', DB::table('site_settings')->where('setting_key', 'development_mode_title')->value('setting_value'));
            $this->assertSame('Plain message', DB::table('site_settings')->where('setting_key', 'development_mode_message')->value('setting_value'));
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
        }
    }

    private function enableMode()
    {
        $settings = [
            'development_mode_enabled' => '1',
            'development_mode_message_type' => 'maintenance',
            'development_mode_title' => 'Private test maintenance',
            'development_mode_message' => 'The test storefront is temporarily unavailable.',
            'development_mode_show_admin_login' => '1',
            'development_mode_login_button_text' => 'Admin Login',
            'favicon' => 'asset/front-end/img/branding/test-favicon.png',
        ];
        foreach ($settings as $key => $value) {
            $this->putSetting($key, $value);
        }
        Cache::forget('site-settings');
    }

    private function putSetting($key, $value)
    {
        DB::table('site_settings')->updateOrInsert(['setting_key' => $key], [
            'setting_value' => $value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
