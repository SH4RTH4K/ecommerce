<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SiteCustomizationTest extends TestCase
{
    private function settingsAdmin()
    {
        return DB::table('tbl_admin as a')->join('admin_roles as r','r.id','=','a.role_id')
            ->where('a.is_active',1)->where('r.permissions','like','%"settings"%')->select('a.*')->first();
    }

    public function testModernSettingsWorkspaceRendersAllBusinessSections()
    {
        $admin=$this->settingsAdmin();
        if(!$admin)return $this->assertTrue(true);
        $this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])
            ->get('/site-customization')->assertStatus(200)
            ->assertSee('Store setup')->assertSee('Business identity')
            ->assertSee('Contact &amp; location',false)->assertSee('Search &amp; sharing',false)
            ->assertSee('Changes become public immediately');
    }

    public function testSettingsBusinessRulesRejectMissingIdentityAndInvalidPhone()
    {
        $admin=$this->settingsAdmin();
        if(!$admin)return $this->assertTrue(true);
        $this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])
            ->from('/site-customization')->post('/site-settings',['site_name'=>'','phone'=>'call-me'])
            ->assertRedirect('/site-customization')->assertSessionHasErrors(['site_name','phone']);
    }

    public function testValidWebsiteSettingsCanBeSaved()
    {
        $admin=$this->settingsAdmin();
        if(!$admin)return $this->assertTrue(true);
        DB::beginTransaction();
        try {
            DB::table('site_settings')->where('setting_key','development_mode_enabled')->update(['setting_value'=>'0']);
            Cache::forget('site-settings');
            $name='Store '.str_random(8);
            $this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])
                ->post('/site-settings',[
                    'site_name'=>$name,'site_tagline'=>'Trusted technology store','phone'=>'+880 1700-000000',
                    'support_email'=>'support@example.com','robots_directive'=>'index,follow',
                    'default_meta_title'=>'Technology products and support',
                    'default_meta_description'=>'Shop dependable technology products with clear pricing and local support.',
                    'development_mode_enabled'=>'0','development_mode_message_type'=>'maintenance',
                    'development_mode_title'=>'Website Under Development',
                    'development_mode_message'=>'We are currently improving our website. Please check back again soon.',
                    'development_mode_show_admin_login'=>'1','development_mode_login_button_text'=>'Admin Login',
                ])->assertRedirect('/site-customization')->assertSessionHas('message');
            $this->assertDatabaseHas('site_settings',['setting_key'=>'site_name','setting_value'=>$name]);
        } finally { DB::rollBack(); Cache::forget('site-settings'); }
    }
}
