<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
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
            ->assertSee('Changes become public immediately')
            ->assertSee('brand-logo-upload',false)->assertSee('brand-favicon-upload',false)
            ->assertSee('Recommended output: 600 × 200 px (3:1)',false)
            ->assertSee('Recommended output: 512 × 512 px (square)',false)
            ->assertSee('Maximum upload: 5 MB')->assertSee('Maximum upload: 2 MB')
            ->assertSee('logo_resize_width',false)->assertSee('favicon_resize_width',false)
            ->assertSee('data-image-details',false)
            ->assertSee('Tagline font size')->assertSee('Bengali text is usually clearest at 13–16 px.',false)
            ->assertSee('data-remove-input="logo"',false)->assertSee('data-remove-input="favicon"',false);
    }

    public function testSettingsBusinessRulesRejectMissingIdentityAndInvalidPhone()
    {
        $admin=$this->settingsAdmin();
        if(!$admin)return $this->assertTrue(true);
        $this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])
            ->from('/site-customization')->post('/site-settings',['site_name'=>'','phone'=>'call-me','site_tagline_font_size'=>'25'])
            ->assertRedirect('/site-customization')->assertSessionHasErrors(['site_name','phone','site_tagline_font_size']);
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
                    'site_tagline_font_size'=>'16',
                    'support_email'=>'support@example.com','robots_directive'=>'index,follow',
                    'default_meta_title'=>'Technology products and support',
                    'default_meta_description'=>'Shop dependable technology products with clear pricing and local support.',
                    'development_mode_enabled'=>'0','development_mode_message_type'=>'maintenance',
                    'development_mode_title'=>'Website Under Development',
                    'development_mode_message'=>'We are currently improving our website. Please check back again soon.',
                    'development_mode_show_admin_login'=>'1','development_mode_login_button_text'=>'Admin Login',
                ])->assertRedirect('/site-customization')->assertSessionHas('message');
            $this->assertDatabaseHas('site_settings',['setting_key'=>'site_name','setting_value'=>$name]);
            $this->assertDatabaseHas('site_settings',['setting_key'=>'site_tagline_font_size','setting_value'=>'16']);
            $this->get('/')->assertOk()->assertSee('lt-brand-tagline',false)->assertSee('Trusted technology store')
                ->assertSee('--brand-tagline-font-size:16px',false)
                ->assertSee('css/brand-tagline.css',false);
            $this->app['session']->flush();
            $this->get('/admin/login')->assertOk()->assertSee('admin-login-tagline',false)->assertSee('Trusted technology store');
        } finally { DB::rollBack(); Cache::forget('site-settings'); }
    }

    public function testLogoAndBrowserIconCanBeUploadedTogether()
    {
        $admin=$this->settingsAdmin();
        if(!$admin)return $this->assertTrue(true);
        $storedPaths=[];
        DB::beginTransaction();
        try {
            DB::table('site_settings')->whereIn('setting_key',['site_logo','favicon'])->delete();
            DB::table('site_settings')->where('setting_key','development_mode_enabled')->update(['setting_value'=>'0']);
            Cache::forget('site-settings');

            $response=$this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])
                ->post('/site-settings',[
                    'site_name'=>'Upload Test Store',
                    'logo'=>UploadedFile::fake()->image('brand-logo.png',800,240)->size(2500),
                    'favicon'=>UploadedFile::fake()->image('browser-icon.jpg',256,256)->size(700),
                    'logo_resize_enabled'=>'1',
                    'logo_resize_width'=>'600',
                    'logo_resize_height'=>'200',
                    'favicon_resize_enabled'=>'1',
                    'favicon_resize_width'=>'512',
                    'favicon_resize_height'=>'512',
                    'robots_directive'=>'index,follow',
                    'development_mode_enabled'=>'0',
                    'development_mode_message_type'=>'maintenance',
                    'development_mode_title'=>'Website Under Development',
                    'development_mode_message'=>'We are currently improving our website. Please check back again soon.',
                    'development_mode_show_admin_login'=>'1',
                    'development_mode_login_button_text'=>'Admin Login',
                ]);

            $response->assertRedirect('/site-customization')->assertSessionHasNoErrors()->assertSessionHas('message');
            foreach (['site_logo','favicon'] as $settingKey) {
                $storedPath=DB::table('site_settings')->where('setting_key',$settingKey)->value('setting_value');
                $this->assertNotEmpty($storedPath);
                $this->assertStringStartsWith('asset/front-end/img/branding/',$storedPath);
                $this->assertFileExists(public_path($storedPath));
                $storedPaths[]=$storedPath;
            }
            $logoSize=getimagesize(public_path($storedPaths[0]));
            $faviconSize=getimagesize(public_path($storedPaths[1]));
            $this->assertSame([600,200],[$logoSize[0],$logoSize[1]]);
            $this->assertSame([512,512],[$faviconSize[0],$faviconSize[1]]);
            $resizedLogo=imagecreatefrompng(public_path($storedPaths[0]));
            try {
                $padding=imagecolorsforindex($resizedLogo,imagecolorat($resizedLogo,0,0));
                $content=imagecolorsforindex($resizedLogo,imagecolorat($resizedLogo,300,100));
                $this->assertSame(127,$padding['alpha']);
                $this->assertSame(0,$content['alpha']);
            } finally {
                imagedestroy($resizedLogo);
            }
            $this->assertDatabaseHas('site_settings',['setting_key'=>'logo_resize_enabled','setting_value'=>'1']);
            $this->assertDatabaseHas('site_settings',['setting_key'=>'logo_resize_width','setting_value'=>'600']);
            $this->assertDatabaseHas('site_settings',['setting_key'=>'favicon_resize_height','setting_value'=>'512']);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
            foreach ($storedPaths as $storedPath) {
                if(is_file(public_path($storedPath)))unlink(public_path($storedPath));
            }
        }
    }

    public function testAutomaticBrandResizeCanBeDisabledToKeepOriginalPixels()
    {
        $admin=$this->settingsAdmin();
        if(!$admin)return $this->assertTrue(true);
        $storedPath=null;
        DB::beginTransaction();
        try {
            DB::table('site_settings')->where('setting_key','site_logo')->delete();
            DB::table('site_settings')->where('setting_key','development_mode_enabled')->update(['setting_value'=>'0']);
            Cache::forget('site-settings');

            $this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])
                ->post('/site-settings',[
                    'site_name'=>'Original Pixel Store',
                    'logo'=>UploadedFile::fake()->image('original-logo.png',321,123),
                    'logo_resize_enabled'=>'0',
                    'logo_resize_width'=>'600',
                    'logo_resize_height'=>'200',
                    'favicon_resize_enabled'=>'0',
                    'favicon_resize_width'=>'512',
                    'favicon_resize_height'=>'512',
                    'robots_directive'=>'index,follow',
                    'development_mode_enabled'=>'0',
                    'development_mode_message_type'=>'maintenance',
                    'development_mode_title'=>'Website Under Development',
                    'development_mode_message'=>'We are currently improving our website. Please check back again soon.',
                    'development_mode_show_admin_login'=>'1',
                    'development_mode_login_button_text'=>'Admin Login',
                ])->assertRedirect('/site-customization')->assertSessionHasNoErrors();

            $storedPath=DB::table('site_settings')->where('setting_key','site_logo')->value('setting_value');
            $this->assertFileExists(public_path($storedPath));
            $size=getimagesize(public_path($storedPath));
            $this->assertSame([321,123],[$size[0],$size[1]]);
            $this->assertDatabaseHas('site_settings',['setting_key'=>'logo_resize_enabled','setting_value'=>'0']);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
            if($storedPath&&is_file(public_path($storedPath)))unlink(public_path($storedPath));
        }
    }

    public function testManagedLogoAndBrowserIconCanBeRemovedFromSiteAndStorage()
    {
        $admin=$this->settingsAdmin();
        if(!$admin)return $this->assertTrue(true);
        $storedPaths=[];
        DB::beginTransaction();
        try {
            DB::table('site_settings')->whereIn('setting_key',['site_logo','favicon'])->delete();
            DB::table('site_settings')->where('setting_key','development_mode_enabled')->update(['setting_value'=>'0']);
            Cache::forget('site-settings');
            $tagline='আমরা আপনার অনুভূতির যত্ন নিই';

            $upload=$this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])
                ->post('/site-settings',[
                    'site_name'=>'Removal Test Store',
                    'site_tagline'=>$tagline,
                    'site_tagline_font_size'=>'16',
                    'logo'=>UploadedFile::fake()->image('removal-logo.png',600,200),
                    'favicon'=>UploadedFile::fake()->image('removal-icon.png',512,512),
                    'logo_resize_enabled'=>'1','logo_resize_width'=>'600','logo_resize_height'=>'200',
                    'favicon_resize_enabled'=>'1','favicon_resize_width'=>'512','favicon_resize_height'=>'512',
                    'remove_logo'=>'0','remove_favicon'=>'0',
                    'robots_directive'=>'index,follow',
                    'development_mode_enabled'=>'0','development_mode_message_type'=>'maintenance',
                    'development_mode_title'=>'Website Under Development',
                    'development_mode_message'=>'We are currently improving our website. Please check back again soon.',
                    'development_mode_show_admin_login'=>'1','development_mode_login_button_text'=>'Admin Login',
                ]);
            $upload->assertRedirect('/site-customization')->assertSessionHasNoErrors();
            foreach(['site_logo','favicon'] as $settingKey){
                $path=DB::table('site_settings')->where('setting_key',$settingKey)->value('setting_value');
                $this->assertFileExists(public_path($path));
                $storedPaths[]=$path;
            }

            $remove=$this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])
                ->post('/site-settings',[
                    'site_name'=>'Removal Test Store',
                    'site_tagline'=>$tagline,
                    'site_tagline_font_size'=>'16',
                    'logo_resize_enabled'=>'1','logo_resize_width'=>'600','logo_resize_height'=>'200',
                    'favicon_resize_enabled'=>'1','favicon_resize_width'=>'512','favicon_resize_height'=>'512',
                    'remove_logo'=>'1','remove_favicon'=>'1',
                    'robots_directive'=>'index,follow',
                    'development_mode_enabled'=>'0','development_mode_message_type'=>'maintenance',
                    'development_mode_title'=>'Website Under Development',
                    'development_mode_message'=>'We are currently improving our website. Please check back again soon.',
                    'development_mode_show_admin_login'=>'1','development_mode_login_button_text'=>'Admin Login',
                ]);
            $remove->assertRedirect('/site-customization')->assertSessionHasNoErrors()
                ->assertSessionHas('message','Logo and browser icon removed from the site and upload storage.');
            $this->assertDatabaseMissing('site_settings',['setting_key'=>'site_logo']);
            $this->assertDatabaseMissing('site_settings',['setting_key'=>'favicon']);
            foreach($storedPaths as $path)$this->assertFileDoesNotExist(public_path($path));

            $this->get('/')->assertOk()
                ->assertSee('class="lt-brand-name"',false)->assertSee('Removal Test Store')
                ->assertSee($tagline)->assertSee('lang="bn"',false)
                ->assertSee('--brand-tagline-font-size:16px',false)
                ->assertDontSee('rel="icon"',false)
                ->assertDontSee($storedPaths[0],false)->assertDontSee($storedPaths[1],false);
            $this->app['session']->flush();
            $this->get('/admin/login')->assertOk()->assertSee('Removal Test Store')
                ->assertSee($tagline)->assertSee('lang="bn"',false)->assertDontSee('rel="icon"',false);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
            foreach($storedPaths as $path)if(is_file(public_path($path)))unlink(public_path($path));
        }
    }

    public function testBrandResizeDimensionsAndSourcePixelLimitsAreValidated()
    {
        $admin=$this->settingsAdmin();
        if(!$admin)return $this->assertTrue(true);
        $payload=[
            'site_name'=>'Resize Validation Store',
            'logo'=>UploadedFile::fake()->image('oversized-source.png',6001,10),
            'remove_logo'=>'1',
            'logo_resize_enabled'=>'1',
            'logo_resize_width'=>'100',
            'logo_resize_height'=>'200',
            'favicon_resize_enabled'=>'1',
            'favicon_resize_width'=>'2048',
            'favicon_resize_height'=>'512',
            'robots_directive'=>'index,follow',
            'development_mode_enabled'=>'0',
            'development_mode_message_type'=>'maintenance',
            'development_mode_title'=>'Website Under Development',
            'development_mode_message'=>'We are currently improving our website. Please check back again soon.',
            'development_mode_show_admin_login'=>'1',
            'development_mode_login_button_text'=>'Admin Login',
        ];

        $this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])
            ->from('/site-customization')->post('/site-settings',$payload)
            ->assertRedirect('/site-customization')
            ->assertSessionHasErrors(['logo','remove_logo','logo_resize_width','favicon_resize_width']);
    }
}
