<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\StorefrontThemeService;
use Tests\TestCase;

class SiteCustomizationTest extends TestCase
{
    private function settingsAdmin()
    {
        return DB::table('tbl_admin as a')->join('admin_roles as r','r.id','=','a.role_id')
            ->where('a.is_active',1)->where('r.permissions','like','%"settings"%')->select('a.*')->first();
    }

    private function imageUpload(string $relativePath, ?string $originalName = null): UploadedFile
    {
        $source = public_path($relativePath);
        $tempPath = tempnam(sys_get_temp_dir(), 'site_upload_');
        copy($source, $tempPath);
        return new UploadedFile($tempPath, $originalName ?: basename($source), null, null, true);
    }

    private function pngUploadWithDimensions(int $width, int $height, string $originalName): array
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8C/2QAAAAASUVORK5CYII=');
        $png = substr_replace($png, pack('N', $width), 16, 4);
        $png = substr_replace($png, pack('N', $height), 20, 4);
        $tempPath = tempnam(sys_get_temp_dir(), 'site_png_');
        file_put_contents($tempPath, $png);
        return [new UploadedFile($tempPath, $originalName, 'image/png', null, true), $tempPath];
    }

    private function imageDimensions(string $relativePath): array
    {
        $size = @getimagesize(public_path($relativePath));
        if ($size === false) {
            $this->fail('Unable to read image dimensions for '.$relativePath);
        }

        return [(int)$size[0], (int)$size[1]];
    }

    private function supportsImageResizing(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatefrompng') && function_exists('imagepng');
    }

    private function assertStoredImageDimensions(string $storedPath, string $sourcePath, ?array $resizedDimensions = null): void
    {
        $expected = $resizedDimensions && $this->supportsImageResizing()
            ? $resizedDimensions
            : $this->imageDimensions($sourcePath);

        $this->assertSame($expected, $this->imageDimensions($storedPath));
    }

    private function adminSession(): array
    {
        $admin = $this->settingsAdmin();
        if (! $admin) {
            return [];
        }

        return ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name];
    }

    private function websiteSettingsPayload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'Test Store',
            'site_tagline' => 'Trusted technology store',
            'site_name_font_size' => '23',
            'site_tagline_font_size' => '12',
            'notice_text' => 'Free delivery on selected products this week.',
            'phone' => '+880 1700-000000',
            'support_phone' => '+880 1700-000001',
            'whatsapp_number' => '+880 1700-000002',
            'support_email' => 'support@example.com',
            'shop_address' => 'Dhaka, Bangladesh',
            'business_hours' => 'Saturday-Thursday, 10:00 AM-8:00 PM',
            'facebook_url' => 'https://facebook.com/test-store',
            'instagram_url' => 'https://instagram.com/test-store',
            'youtube_url' => 'https://youtube.com/@test-store',
            'linkedin_url' => 'https://linkedin.com/company/test-store',
            'twitter_url' => 'https://x.com/test-store',
            'google_analytics_id' => 'G-ABCDEFG123',
            'google_site_verification' => 'verification-token',
            'default_meta_title' => 'Test Store | Tech',
            'default_meta_description' => 'Shop dependable technology products with clear pricing and local support.',
            'meta_keywords' => 'computers, laptops, accessories',
            'robots_directive' => 'index,follow',
            'footer_description' => 'Trusted retailer of technology products.',
            'copyright_text' => '© {year} Test Store. All rights reserved.',
            'hero_side_title' => 'Fast delivery',
            'hero_side_text' => 'Delivered the same day in Dhaka.',
            'development_mode_enabled' => '0',
            'development_mode_message_type' => 'maintenance',
            'development_mode_title' => 'Website Under Development',
            'development_mode_message' => 'We are currently improving our website. Please check back again soon.',
            'development_mode_additional_message' => '',
            'development_mode_availability_text' => '',
            'development_mode_show_admin_login' => '1',
            'development_mode_login_button_text' => 'Admin Login',
            'logo_resize_enabled' => '1',
            'logo_resize_width' => '600',
            'logo_resize_height' => '200',
            'favicon_resize_enabled' => '1',
            'favicon_resize_width' => '512',
            'favicon_resize_height' => '512',
            'startech_source_import_enabled' => '1',
            'homepage_featured_products_limit' => '10',
            'homepage_featured_products_per_row' => '5',
            'homepage_new_arrivals_limit' => '10',
            'homepage_new_arrivals_per_row' => '5',
        ], $overrides);
    }

    private function resetWebsiteSettingsPayload(array $overrides = []): array
    {
        return array_merge($this->websiteSettingsPayload([
            'site_name' => config('app.default_name', 'Ecommerce'),
            'site_tagline' => '',
            'site_name_font_size' => '23',
            'site_tagline_font_size' => '12',
            'notice_text' => '',
            'phone' => '',
            'support_phone' => '',
            'whatsapp_number' => '',
            'support_email' => '',
            'shop_address' => '',
            'business_hours' => '',
            'facebook_url' => '',
            'instagram_url' => '',
            'youtube_url' => '',
            'linkedin_url' => '',
            'twitter_url' => '',
            'google_analytics_id' => '',
            'google_site_verification' => '',
            'default_meta_title' => '',
            'default_meta_description' => '',
            'meta_keywords' => '',
            'robots_directive' => 'index,follow',
            'footer_description' => '',
            'copyright_text' => '© {year} '.config('app.default_name', 'Ecommerce').'. All rights reserved.',
            'hero_side_title' => '',
            'hero_side_text' => '',
            'development_mode_enabled' => '0',
            'development_mode_message_type' => 'maintenance',
            'development_mode_title' => 'Website Under Development',
            'development_mode_message' => 'We are currently improving our website. Please check back again soon.',
            'development_mode_additional_message' => '',
            'development_mode_availability_text' => '',
            'development_mode_show_admin_login' => '1',
            'development_mode_login_button_text' => 'Admin Login',
            'logo_resize_enabled' => '1',
            'logo_resize_width' => '600',
            'logo_resize_height' => '200',
            'favicon_resize_enabled' => '1',
            'favicon_resize_width' => '512',
            'favicon_resize_height' => '512',
            'startech_source_import_enabled' => '1',
            'reset_to_default' => '1',
        ]), $overrides);
    }

    private function storefrontThemePayload(array $overrides = []): array
    {
        return array_merge(app(StorefrontThemeService::class)->defaults(), $overrides);
    }

    public function testModernSettingsWorkspaceRendersAllBusinessSections()
    {
        $admin=$this->settingsAdmin();
        if(!$admin)return $this->assertTrue(true);
        $this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])
            ->get('/site-customization')->assertStatus(200)
            ->assertSee('Store setup')->assertSee('Business identity')
            ->assertSee('Contact &amp; location',false)->assertSee('Theme &amp; colors',false)->assertSee('Search &amp; sharing',false)
            ->assertSee('Section Typography')->assertSee('Header Text Size')
            ->assertSee('How this tab works',false)
            ->assertSee('Public page copy',false)
            ->assertSee('Live About page preview',false)
            ->assertSee('data-page-preview="terms"',false)
            ->assertSee('Changes become public immediately')
            ->assertSee('brand-logo-upload',false)->assertSee('brand-favicon-upload',false)
            ->assertSee('Recommended output: 600 × 200 px (3:1)',false)
            ->assertSee('Recommended output: 512 × 512 px (square)',false)
            ->assertSee('Maximum upload: 5 MB')->assertSee('Maximum upload: 2 MB')
            ->assertSee('logo_resize_width',false)->assertSee('favicon_resize_width',false)
            ->assertSee('data-image-details',false)
            ->assertSee('Website name font size')->assertSee('Bengali Website names are usually clearest at 22–28 px.',false)
            ->assertSee('Tagline font size')->assertSee('Bengali text is usually clearest at 13–16 px.',false)
            ->assertSee('data-remove-input="logo"',false)->assertSee('data-remove-input="favicon"',false);
    }

    public function testSettingsBusinessRulesRejectMissingIdentityAndInvalidPhone()
    {
        $admin=$this->settingsAdmin();
        if(!$admin)return $this->assertTrue(true);
        $this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])
            ->from('/site-customization')->post('/site-settings',['site_name'=>'','phone'=>'call-me','site_name_font_size'=>'33','site_tagline_font_size'=>'25'])
            ->assertRedirect('/site-customization')->assertSessionHasErrors(['site_name','phone','site_name_font_size','site_tagline_font_size']);
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
                    'site_name_font_size'=>'26',
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
            $this->assertDatabaseHas('site_settings',['setting_key'=>'site_name_font_size','setting_value'=>'26']);
            $this->assertDatabaseHas('site_settings',['setting_key'=>'site_tagline_font_size','setting_value'=>'16']);
            $this->get('/')->assertOk()->assertSee('lt-brand-tagline',false)->assertSee('Trusted technology store')
                ->assertSee('--brand-name-font-size:26px',false)
                ->assertSee('--brand-tagline-font-size:16px',false)
                ->assertSee('css/brand-tagline.css',false)
                ->assertSee('lt-startech-menu',false)
                ->assertSee('lt-category-nav',false)
                ->assertSee('lt-menu-empty',false)
                ->assertSee('lt-nav-cta',false)
                ->assertSee('PC Builder',false)
                ->assertDontSee('All Categories',false)
                ->assertDontSee('Browse by department',false);
            $this->app['session']->flush();
            $this->get('/admin/login')->assertOk()->assertSee('admin-login-tagline',false)->assertSee('Trusted technology store');
        } finally { DB::rollBack(); Cache::forget('site-settings'); }
    }

    public function testStorefrontThemeCanBeSavedAndRenderedWithSectionOverrides()
    {
        $admin = $this->settingsAdmin();
        if (! $admin) {
            return $this->assertTrue(true);
        }

        DB::beginTransaction();
        try {
            DB::table('site_settings')->where('setting_key', 'development_mode_enabled')->update(['setting_value' => '0']);
            Cache::forget('site-settings');

            $theme = $this->storefrontThemePayload([
                'preset' => 'blue-orange',
                'global_primary' => '#123456',
                'global_secondary' => '#0f2233',
                'global_accent' => '#f5821f',
                'header_use_global' => '0',
                'header_background' => '#112233',
                'header_text' => '#f8fbff',
                'header_font_family' => 'bengali',
                'header_font_size' => '18',
                'key_features_font_family' => 'georgia',
                'key_features_font_size' => '17',
                'key_features_text_color' => '#345678',
                'key_features_heading_color' => '#123456',
                'specification_font_family' => 'tahoma',
                'specification_font_size' => '16',
                'specification_text_color' => '#182736',
                'specification_label_color' => '#405060',
                'specification_group_color' => '#506070',
                'specification_heading_color' => '#607080',
                'detail_code_font_family' => 'mono',
                'detail_code_font_size' => '14',
                'detail_code_font_weight' => '700',
                'detail_code_letter_spacing' => '0.04',
                'detail_code_text_transform' => 'none',
                'detail_code_text_color' => '#123456',
                'detail_code_background' => '#e8f0f6',
                'detail_code_border_color' => '#789abc',
                'detail_code_border_width' => '2',
                'detail_code_border_radius' => '12',
                'detail_code_padding_y' => '7',
                'detail_code_padding_x' => '12',
                'footer_font_family' => 'georgia',
                'footer_font_size' => '16',
            ]);

            $response = $this->withSession(['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name])
                ->post('/site-settings', array_merge($this->websiteSettingsPayload([
                    'site_name' => 'Theme Demo Store',
                    'development_mode_enabled' => '0',
                ]), [
                    'storefront_theme' => $theme,
                ]));

            $response->assertRedirect('/site-customization')->assertSessionHas('message');

            $storedTheme = DB::table('site_settings')->where('setting_key', 'storefront_theme')->value('setting_value');
            $this->assertNotEmpty($storedTheme);

            $decodedTheme = json_decode($storedTheme, true);
            $this->assertSame('blue-orange', $decodedTheme['preset']);
            $this->assertSame('#123456', $decodedTheme['global_primary']);
            $this->assertSame('#0f2233', $decodedTheme['global_secondary']);
            $this->assertSame(0, $decodedTheme['header_use_global']);
            $this->assertSame('#112233', $decodedTheme['header_background']);
            $this->assertSame('#f8fbff', $decodedTheme['header_text']);
            $this->assertSame('bengali', $decodedTheme['header_font_family']);
            $this->assertSame('18', $decodedTheme['header_font_size']);
            $this->assertSame('georgia', $decodedTheme['key_features_font_family']);
            $this->assertSame('17', $decodedTheme['key_features_font_size']);
            $this->assertSame('#345678', $decodedTheme['key_features_text_color']);
            $this->assertSame('#123456', $decodedTheme['key_features_heading_color']);
            $this->assertSame('tahoma', $decodedTheme['specification_font_family']);
            $this->assertSame('16', $decodedTheme['specification_font_size']);
            $this->assertSame('#182736', $decodedTheme['specification_text_color']);
            $this->assertSame('#405060', $decodedTheme['specification_label_color']);
            $this->assertSame('#506070', $decodedTheme['specification_group_color']);
            $this->assertSame('#607080', $decodedTheme['specification_heading_color']);
            $this->assertSame('mono', $decodedTheme['detail_code_font_family']);
            $this->assertSame('14', $decodedTheme['detail_code_font_size']);
            $this->assertSame('700', $decodedTheme['detail_code_font_weight']);
            $this->assertSame('#123456', $decodedTheme['detail_code_text_color']);
            $this->assertSame('#e8f0f6', $decodedTheme['detail_code_background']);
            $this->assertSame('12', $decodedTheme['detail_code_border_radius']);
            $this->assertSame('georgia', $decodedTheme['footer_font_family']);
            $this->assertSame('16', $decodedTheme['footer_font_size']);

            Cache::forget('site-settings');
            $this->get('/')->assertOk()
                ->assertSee('--theme-nav-bg: #123456', false)
                ->assertSee('--theme-header-bg: #112233', false)
                ->assertSee('--theme-header-font-family: "Noto Sans Bengali"', false)
                ->assertDontSee('--theme-header-font-family: &quot;Noto Sans Bengali&quot;', false)
                ->assertSee('--theme-header-font-size: 18px', false)
                ->assertSee('--theme-key-features-font-family: Georgia', false)
                ->assertSee('"Noto Serif Bengali", "Noto Sans Bengali", serif', false)
                ->assertSee('--theme-key-features-font-size: 17px', false)
                ->assertSee('--theme-key-features-text: #345678', false)
                ->assertSee('--theme-key-features-heading: #123456', false)
                ->assertSee('--theme-specification-font-family: Tahoma', false)
                ->assertSee('--theme-specification-font-size: 16px', false)
                ->assertSee('--theme-specification-text: #182736', false)
                ->assertSee('--theme-specification-label: #405060', false)
                ->assertSee('--theme-specification-group: #506070', false)
                ->assertSee('--theme-specification-heading: #607080', false)
                ->assertSee('--theme-detail-code-font-family: "Courier New"', false)
                ->assertSee('--theme-detail-code-font-size: 14px', false)
                ->assertSee('--theme-detail-code-font-weight: 700', false)
                ->assertSee('--theme-detail-code-letter-spacing: 0.04em', false)
                ->assertSee('--theme-detail-code-text-transform: none', false)
                ->assertSee('--theme-detail-code-text: #123456', false)
                ->assertSee('--theme-detail-code-background: #e8f0f6', false)
                ->assertSee('--theme-detail-code-border: #789abc', false)
                ->assertSee('--theme-detail-code-border-width: 2px', false)
                ->assertSee('--theme-detail-code-radius: 12px', false)
                ->assertSee('--theme-detail-code-padding-y: 7px', false)
                ->assertSee('--theme-detail-code-padding-x: 12px', false)
                ->assertSee('--theme-footer-bg: #0f2233', false)
                ->assertSee('--theme-footer-font-size: 16px', false)
                ->assertSee('--theme-button-primary-bg: #123456', false)
                ->assertSee('lt-startech-menu', false);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
        }
    }

    public function testInvalidStorefrontThemeColorIsRejected()
    {
        $admin = $this->settingsAdmin();
        if (! $admin) {
            return $this->assertTrue(true);
        }

        DB::beginTransaction();
        try {
            DB::table('site_settings')->where('setting_key', 'development_mode_enabled')->update(['setting_value' => '0']);
            Cache::forget('site-settings');
            $before = DB::table('site_settings')->where('setting_key', 'storefront_theme')->value('setting_value');

            $response = $this->withSession(['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name])
                ->from('/site-customization')
                ->post('/site-settings', array_merge($this->websiteSettingsPayload([
                    'site_name' => 'Theme Validation Store',
                    'development_mode_enabled' => '0',
                ]), [
                    'storefront_theme' => $this->storefrontThemePayload([
                        'global_primary' => 'not-a-color',
                    ]),
                ]));

            $response->assertRedirect('/site-customization')->assertSessionHasErrors(['storefront_theme.global_primary']);
            $after = DB::table('site_settings')->where('setting_key', 'storefront_theme')->value('setting_value');
            $this->assertSame($before, $after);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
        }
    }

    public function testEveryGeneratedThemeVariableIsConsumedByStorefrontStyles()
    {
        $themeService = app(StorefrontThemeService::class);
        $styles = implode("\n", [
            file_get_contents(public_path('css/ecommerce-home.css')),
            file_get_contents(public_path('css/brand-tagline.css')),
            file_get_contents(public_path('css/top-bar.css')),
            file_get_contents(public_path('css/storefront-theme.css')),
        ]);

        foreach (array_keys($themeService->cssVariables($themeService->defaults())) as $variable) {
            $this->assertNotFalse(
                strpos($styles, 'var('.$variable),
                $variable.' is generated but is not consumed by a storefront stylesheet.'
            );
        }
    }

    public function testDerivedThemeValuesMatchTheStorefrontPreviewContract()
    {
        $themeService = app(StorefrontThemeService::class);
        $theme = array_merge($themeService->defaults(), [
            'global_primary' => '#123456',
            'global_danger' => '#aa2244',
            'navigation_use_global' => 1,
            'cards_use_global' => 1,
            'buttons_use_global' => 1,
            'search_use_global' => 0,
            'search_focus_border' => '#112233',
            'forms_use_global' => 0,
            'form_focus_ring' => 'rgba(1,2,3,.4)',
        ]);

        $variables = $themeService->cssVariables($theme);

        $this->assertSame('#2e4c6a', $variables['--theme-nav-hover-bg']);
        $this->assertSame('0 10px 30px rgba(18,52,86,0.1)', $variables['--theme-card-hover-shadow']);
        $this->assertSame('#961e3c', $variables['--theme-button-danger-hover-bg']);
        $this->assertSame('rgba(17,34,51,0.15)', $variables['--theme-search-focus-ring']);
        $this->assertSame('rgba(1,2,3,.4)', $variables['--theme-form-focus-ring']);
        $this->assertSame('#25445d', $variables['--theme-footer-border']);
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
                    'logo'=>$this->imageUpload('asset/front-end/img/ecommerce-logo.png', 'brand-logo.png'),
                    'favicon'=>$this->imageUpload('asset/front-end/img/branding/favicon-736a0b0a2889.png', 'browser-icon.png'),
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
            $this->assertStoredImageDimensions($storedPaths[0], 'asset/front-end/img/ecommerce-logo.png', [600,200]);
            $this->assertStoredImageDimensions($storedPaths[1], 'asset/front-end/img/branding/favicon-736a0b0a2889.png', [512,512]);
            $this->get('/')->assertOk()
                ->assertSee('--brand-logo-width:220px',false)
                ->assertSee('--brand-logo-height:73px',false)
                ->assertSee('--brand-logo-mobile-width:150px',false)
                ->assertSee('--brand-logo-mobile-height:50px',false);
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

    public function testSmallerLogoResizeSettingsReduceTheRenderedHeaderLogo()
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
                    'site_name'=>'Compact Logo Store',
                    'logo'=>$this->imageUpload('asset/front-end/img/ecommerce-logo.png', 'compact-logo.png'),
                    'logo_resize_enabled'=>'1',
                    'logo_resize_width'=>'300',
                    'logo_resize_height'=>'100',
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
            $this->assertStoredImageDimensions($storedPath, 'asset/front-end/img/ecommerce-logo.png', [300,100]);

            $this->get('/')->assertOk()
                ->assertSee('--brand-logo-width:120px',false)
                ->assertSee('--brand-logo-height:40px',false)
                ->assertSee('--brand-logo-mobile-width:90px',false)
                ->assertSee('--brand-logo-mobile-height:30px',false);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
            if($storedPath&&is_file(public_path($storedPath)))unlink(public_path($storedPath));
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
                    'logo'=>$this->imageUpload('asset/front-end/img/ecommerce-logo.png', 'original-logo.png'),
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
            $this->assertStoredImageDimensions($storedPath, 'asset/front-end/img/ecommerce-logo.png');
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
            $bengaliName='ই-কমার্স স্টোর';

            $upload=$this->withSession(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name])
                ->post('/site-settings',[
                    'site_name'=>$bengaliName,
                    'site_name_font_size'=>'26',
                    'site_tagline'=>$tagline,
                    'site_tagline_font_size'=>'16',
                    'logo'=>$this->imageUpload('asset/front-end/img/ecommerce-logo.png', 'removal-logo.png'),
                    'favicon'=>$this->imageUpload('asset/front-end/img/branding/favicon-736a0b0a2889.png', 'removal-icon.png'),
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
                    'site_name'=>$bengaliName,
                    'site_name_font_size'=>'26',
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
                ->assertSee('class="lt-brand-name is-bengali"',false)->assertSee($bengaliName)
                ->assertSee('lt-footer-brand-name is-bengali',false)
                ->assertSee('--brand-name-font-size:26px',false)
                ->assertSee($tagline)->assertSee('lang="bn"',false)
                ->assertSee('--brand-tagline-font-size:16px',false)
                ->assertDontSee('rel="icon"',false)
                ->assertDontSee($storedPaths[0],false)->assertDontSee($storedPaths[1],false);
            $this->app['session']->flush();
            $this->get('/admin/login')->assertOk()->assertSee($bengaliName)
                ->assertSee('admin-login-name is-bengali',false)->assertSee('--brand-name-font-size:26px',false)
                ->assertSee($tagline)->assertSee('lang="bn"',false)->assertDontSee('rel="icon"',false);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
            foreach($storedPaths as $path)if(is_file(public_path($path)))unlink(public_path($path));
        }
    }

    public function testResetWebsiteSettingsClearsCustomizedValuesRemovesManagedAssetsAndDropsProgressToZero()
    {
        $admin = $this->settingsAdmin();
        if (! $admin) {
            return $this->assertTrue(true);
        }

        $session = ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name];
        $storedPaths = [];
        DB::beginTransaction();
        try {
            DB::table('site_settings')->where('setting_key', 'development_mode_enabled')->update(['setting_value' => '0']);
            Cache::forget('site-settings');

            $customResponse = $this->withSession($session)
                ->post('/site-settings', array_merge($this->websiteSettingsPayload([
                    'site_name' => 'Reset Demo Store',
                    'site_tagline' => 'Reset demo tagline',
                    'phone' => '+880 1700-222222',
                    'support_email' => 'reset@example.com',
                    'shop_address' => '123 Reset Road, Dhaka',
                    'default_meta_description' => 'Custom search description for reset demo.',
                    'development_mode_enabled' => '0',
                ]), [
                    'logo' => $this->imageUpload('asset/front-end/img/ecommerce-logo.png', 'reset-logo.png'),
                    'favicon' => $this->imageUpload('asset/front-end/img/branding/favicon-736a0b0a2889.png', 'reset-favicon.png'),
                    'seo_image' => $this->imageUpload('asset/front-end/img/ecommerce-logo.png', 'reset-seo.png'),
                    'storefront_theme' => $this->storefrontThemePayload([
                        'global_primary' => '#224466',
                        'global_secondary' => '#0f2233',
                        'header_use_global' => '0',
                        'header_background' => '#112233',
                    ]),
                ]));

            $customResponse->assertRedirect('/site-customization')->assertSessionHas('message');

            $storedTheme = DB::table('site_settings')->where('setting_key', 'storefront_theme')->value('setting_value');
            $this->assertNotEmpty($storedTheme);

            $storedPaths = [
                DB::table('site_settings')->where('setting_key', 'site_logo')->value('setting_value'),
                DB::table('site_settings')->where('setting_key', 'favicon')->value('setting_value'),
                DB::table('site_settings')->where('setting_key', 'default_og_image')->value('setting_value'),
            ];
            foreach ($storedPaths as $storedPath) {
                $this->assertNotEmpty($storedPath);
                $this->assertFileExists(public_path($storedPath));
            }

            $resetResponse = $this->withSession($session)
                ->post('/site-settings', $this->resetWebsiteSettingsPayload());

            $resetResponse->assertRedirect('/site-customization')->assertSessionHas('message', 'Website settings were reset successfully.');

            foreach ([
                'site_name',
                'site_tagline',
                'phone',
                'support_email',
                'shop_address',
                'default_meta_description',
                'site_logo',
                'favicon',
                'default_og_image',
                'storefront_theme',
                'development_mode_enabled',
                'startech_source_import_enabled',
            ] as $settingKey) {
                $this->assertDatabaseMissing('site_settings', ['setting_key' => $settingKey]);
            }

            $this->get('/site-customization')
                ->assertOk()
                ->assertSee('Store setup')
                ->assertSee('0%')
                ->assertSee(config('app.default_name', 'Ecommerce'));

            $this->get('/')
                ->assertOk()
                ->assertSee(config('app.default_name', 'Ecommerce'));

            foreach ($storedPaths as $storedPath) {
                $this->assertFileDoesNotExist(public_path($storedPath));
            }
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
            foreach ($storedPaths as $storedPath) {
                if ($storedPath && is_file(public_path($storedPath))) {
                    unlink(public_path($storedPath));
                }
            }
        }
    }

    public function testBundledBrandingAssetsRemainProtectedFromCleanup()
    {
        $admin = $this->settingsAdmin();
        if (! $admin) {
            return $this->assertTrue(true);
        }

        $protectedPath = 'asset/front-end/img/branding/favicon-736a0b0a2889.png';
        DB::beginTransaction();
        try {
            DB::table('site_settings')->updateOrInsert(['setting_key' => 'default_og_image'], [
                'setting_value' => $protectedPath,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('site_settings')->where('setting_key', 'development_mode_enabled')->update(['setting_value' => '0']);
            Cache::forget('site-settings');

            $this->withSession(['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name])
                ->post('/site-settings', $this->resetWebsiteSettingsPayload())
                ->assertRedirect('/site-customization')
                ->assertSessionHas('message', 'Website settings were reset successfully.');

            $this->assertFileExists(public_path($protectedPath));
            $this->assertDatabaseMissing('site_settings', ['setting_key' => 'default_og_image']);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
        }
    }

    public function testReplacingAnUploadedLogoRemovesTheOldUnusedFile()
    {
        $admin = $this->settingsAdmin();
        if (! $admin) {
            return $this->assertTrue(true);
        }

        $session = ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name];
        $storedPaths = [];
        $firstLogo = null;
        $secondLogo = null;
        DB::beginTransaction();
        try {
            DB::table('site_settings')->where('setting_key', 'development_mode_enabled')->update(['setting_value' => '0']);
            Cache::forget('site-settings');

            $this->withSession($session)->post('/site-settings', array_merge($this->websiteSettingsPayload([
                'site_name' => 'Replacement Store',
                'development_mode_enabled' => '0',
            ]), [
                'logo' => $this->imageUpload('asset/front-end/img/ecommerce-logo.png', 'replacement-logo-1.png'),
            ]))->assertRedirect('/site-customization')->assertSessionHasNoErrors();

            $firstLogo = DB::table('site_settings')->where('setting_key', 'site_logo')->value('setting_value');
            $storedPaths[] = $firstLogo;
            $this->assertNotEmpty($firstLogo);
            $this->assertFileExists(public_path($firstLogo));

            $this->withSession($session)->post('/site-settings', array_merge($this->websiteSettingsPayload([
                'site_name' => 'Replacement Store',
                'development_mode_enabled' => '0',
            ]), [
                'logo' => $this->imageUpload('asset/front-end/img/ecommerce-logo.png', 'replacement-logo-2.png'),
            ]))->assertRedirect('/site-customization')->assertSessionHasNoErrors();

            $secondLogo = DB::table('site_settings')->where('setting_key', 'site_logo')->value('setting_value');
            $storedPaths[] = $secondLogo;
            $this->assertNotEmpty($secondLogo);
            $this->assertNotSame($firstLogo, $secondLogo);
            $this->assertFileExists(public_path($secondLogo));
            $this->assertFileDoesNotExist(public_path($firstLogo));
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
            foreach ($storedPaths as $path) {
                if ($path && is_file(public_path($path))) {
                    unlink(public_path($path));
                }
            }
        }
    }

    public function testDatabaseFailureDoesNotDeleteTheExistingManagedLogo()
    {
        $admin = $this->settingsAdmin();
        if (! $admin) {
            return $this->assertTrue(true);
        }

        $session = ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name];
        $existingLogo = null;
        DB::beginTransaction();
        try {
            DB::table('site_settings')->where('setting_key', 'development_mode_enabled')->update(['setting_value' => '0']);
            Cache::forget('site-settings');

            $this->withSession($session)->post('/site-settings', $this->websiteSettingsPayload([
                'site_name' => 'Failure Safe Store',
                'development_mode_enabled' => '0',
                'logo' => $this->imageUpload('asset/front-end/img/ecommerce-logo.png', 'failure-safe-logo.png'),
            ]))->assertRedirect('/site-customization')->assertSessionHasNoErrors();

            $existingLogo = DB::table('site_settings')->where('setting_key', 'site_logo')->value('setting_value');
            $this->assertNotEmpty($existingLogo);
            $this->assertFileExists(public_path($existingLogo));

            $response = $this->withSession($session)
                ->post('/site-settings', $this->websiteSettingsPayload([
                    'site_name' => 'Failure Safe Store',
                    'development_mode_enabled' => '0',
                    'simulate_db_failure' => '1',
                    'logo' => $this->imageUpload('asset/front-end/img/ecommerce-logo.png', 'failure-safe-logo-2.png'),
                ]));

            $response->assertRedirect('/site-customization#identity')->assertSessionHasErrors('settings');

            $this->assertFileExists(public_path($existingLogo));
            $this->assertDatabaseHas('site_settings', ['setting_key' => 'site_logo', 'setting_value' => $existingLogo]);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
            if ($existingLogo && is_file(public_path($existingLogo))) {
                unlink(public_path($existingLogo));
            }
        }
    }

    public function testMissingManagedFileDoesNotBreakReset()
    {
        $admin = $this->settingsAdmin();
        if (! $admin) {
            return $this->assertTrue(true);
        }

        $session = ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name];
        $logoPath = null;
        DB::beginTransaction();
        try {
            DB::table('site_settings')->where('setting_key', 'development_mode_enabled')->update(['setting_value' => '0']);
            Cache::forget('site-settings');

            $this->withSession($session)->post('/site-settings', $this->websiteSettingsPayload([
                'site_name' => 'Missing File Store',
                'development_mode_enabled' => '0',
                'logo' => $this->imageUpload('asset/front-end/img/ecommerce-logo.png', 'missing-file-logo.png'),
            ]))->assertRedirect('/site-customization')->assertSessionHasNoErrors();

            $logoPath = DB::table('site_settings')->where('setting_key', 'site_logo')->value('setting_value');
            $this->assertNotEmpty($logoPath);
            $this->assertFileExists(public_path($logoPath));
            unlink(public_path($logoPath));

            $this->withSession($session)->post('/site-settings', $this->resetWebsiteSettingsPayload())
                ->assertRedirect('/site-customization')
                ->assertSessionHas('message', 'Website settings were reset successfully.');

            $this->assertDatabaseMissing('site_settings', ['setting_key' => 'site_logo']);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
            if ($logoPath && is_file(public_path($logoPath))) {
                unlink(public_path($logoPath));
            }
        }
    }

    public function testSharedManagedAssetIsNotDeletedWhenAnotherSettingStillReferencesIt()
    {
        $admin = $this->settingsAdmin();
        if (! $admin) {
            return $this->assertTrue(true);
        }

        $session = ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name];
        $sharedPath = null;
        DB::beginTransaction();
        try {
            DB::table('site_settings')->where('setting_key', 'development_mode_enabled')->update(['setting_value' => '0']);
            Cache::forget('site-settings');

            $this->withSession($session)->post('/site-settings', $this->websiteSettingsPayload([
                'site_name' => 'Shared Asset Store',
                'development_mode_enabled' => '0',
                'logo' => $this->imageUpload('asset/front-end/img/ecommerce-logo.png', 'shared-asset-logo.png'),
            ]))->assertRedirect('/site-customization')->assertSessionHasNoErrors();

            $sharedPath = DB::table('site_settings')->where('setting_key', 'site_logo')->value('setting_value');
            $this->assertNotEmpty($sharedPath);
            $this->assertFileExists(public_path($sharedPath));

            DB::table('site_settings')->updateOrInsert(['setting_key' => 'default_og_image'], [
                'setting_value' => $sharedPath,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Cache::forget('site-settings');

            $this->withSession($session)->post('/site-settings', $this->websiteSettingsPayload([
                'site_name' => 'Shared Asset Store',
                'development_mode_enabled' => '0',
                'remove_logo' => '1',
            ]))->assertRedirect('/site-customization')->assertSessionHasNoErrors();

            $this->assertDatabaseMissing('site_settings', ['setting_key' => 'site_logo']);
            $this->assertDatabaseHas('site_settings', ['setting_key' => 'default_og_image', 'setting_value' => $sharedPath]);
            $this->assertFileExists(public_path($sharedPath));
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
            if ($sharedPath && is_file(public_path($sharedPath))) {
                unlink(public_path($sharedPath));
            }
        }
    }

    public function testBrandResizeDimensionsAndSourcePixelLimitsAreValidated()
    {
        $admin=$this->settingsAdmin();
        if(!$admin)return $this->assertTrue(true);
        $payload=[
            'site_name'=>'Resize Validation Store',
            'logo'=>$this->pngUploadWithDimensions(6001,10,'oversized-source.png')[0],
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

    public function testStarTechSourceImportVisibilityCanBeToggledInWebsiteSettings()
    {
        $admin = $this->settingsAdmin();
        if (! $admin) {
            return $this->assertTrue(true);
        }

        DB::beginTransaction();
        try {
            DB::table('site_settings')->where('setting_key', 'development_mode_enabled')->update(['setting_value' => '0']);
            Cache::forget('site-settings');

            $this->withSession(['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name])
                ->post('/site-settings', [
                    'site_name' => 'Import Toggle Store',
                    'phone' => '+880 1700-000000',
                    'development_mode_enabled' => '0',
                    'development_mode_message_type' => 'maintenance',
                    'development_mode_title' => 'Website Under Development',
                    'development_mode_message' => 'We are currently improving our website. Please check back again soon.',
                    'development_mode_show_admin_login' => '1',
                    'startech_source_import_enabled' => '0',
                ])->assertRedirect('/site-customization')->assertSessionHas('message');

            Cache::forget('site-settings');
            \View::share('siteSettings', DB::table('site_settings')->pluck('setting_value', 'setting_key'));

            $this->assertDatabaseHas('site_settings', [
                'setting_key' => 'startech_source_import_enabled',
                'setting_value' => '0',
            ]);

            foreach (['/catalog-imports', '/catalog-hierarchy', '/manage-category', '/manage-subCategory', '/manage-manufacturer', '/manage-product', '/catalog-attributes'] as $path) {
                $this->withSession(['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name])
                    ->get($path)
                    ->assertOk()
                    ->assertSee('Star Tech source import is disabled', false)
                    ->assertSee('Open setting', false)
                    ->assertDontSee('Fetch only', false);
            }
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
        }
    }

    public function testAboutUsAndTermsPagesCanBeCustomizedFromSiteSettings()
    {
        $admin = $this->settingsAdmin();
        if (! $admin) {
            return $this->assertTrue(true);
        }

        DB::beginTransaction();
        try {
            DB::table('site_settings')->where('setting_key', 'development_mode_enabled')->update(['setting_value' => '0']);
            Cache::forget('site-settings');

            $payload = array_merge($this->websiteSettingsPayload([
                'site_name' => 'Lucent Tech BD',
                'development_mode_enabled' => '0',
            ]), [
                'about_us_hero_kicker' => 'About Lucent Tech',
                'about_us_hero_title' => 'Technology, service, and support that feels personal.',
                'about_us_hero_text' => '<strong>We tailor</strong> every recommendation for the customer in front of us.',
                'about_us_story_text_1' => 'We help people choose reliable products and explain the tradeoffs <em>clearly</em>.',
                'about_us_story_text_2' => 'That approach keeps the store easy to trust and easy to grow.',
                'about_us_capabilities_items' => "Custom builds\nBusiness sourcing\nAfter-sales support",
                'about_us_cta_button_text' => 'Reach our team',
                'terms_hero_text' => 'Please review these <strong>conditions</strong> before purchasing, receiving, or submitting a product for service.',
                'terms_hero_title' => 'Warranty, service, and store terms',
                'terms_coverage_text' => 'Coverage always follows the manufacturer policy noted on the invoice. <a href="'.url('/contact-us').'">Ask our team</a> if you need help.',
                'terms_exclusions_items' => "Water damage.\nPhysical damage.\nUnauthorized repair.",
                'terms_service_items' => "Bring the invoice.\nAllow inspection.\nWait for supplier confirmation.",
                'terms_help_text' => 'Call us if you need help understanding service eligibility.',
                'terms_help_button_text' => 'Talk to support',
            ]);

            $this->withSession(['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name])
                ->post('/site-settings', $payload)
                ->assertRedirect('/site-customization')
                ->assertSessionHas('message');

            Cache::forget('site-settings');

            $this->get('/about-us')
                ->assertOk()
                ->assertSee('About Lucent Tech')
                ->assertSee('Technology, service, and support that feels personal.')
                ->assertSee('<strong>We tailor</strong> every recommendation for the customer in front of us.', false)
                ->assertSee('<em>clearly</em>', false)
                ->assertSee('Custom builds')
                ->assertSee('Reach our team');

            $this->get('/terms&conditions')
                ->assertOk()
                ->assertSee('Warranty, service, and store terms')
                ->assertSee('<strong>conditions</strong>', false)
                ->assertSee('Coverage always follows the manufacturer policy noted on the invoice.')
                ->assertSee('Ask our team')
                ->assertSee('Water damage.')
                ->assertSee('Talk to support');
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
        }
    }

    public function testSitemapIncludesTheTermsPage(): void
    {
        Cache::forget('xml-sitemap');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(url('/about-us'))
            ->assertSee(url('/terms&conditions'))
            ->assertSee(url('/contact-us'));
    }
}
