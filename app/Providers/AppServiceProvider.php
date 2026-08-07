<?php

namespace App\Providers;

use App\Services\StorefrontThemeService;
use App\Services\StorefrontNavbarService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
//        \Schema::DefaultStringLength(191);
        $siteSettings = collect();
        try {
            if (Schema::hasTable('site_settings')) {
                $siteSettings = Cache::remember('site-settings', now()->addHours(6), function () {
                    return DB::table('site_settings')->pluck('setting_value', 'setting_key');
                });
            }
        } catch (\Throwable $e) {
            // Installation and migration commands must still run before the database exists.
        }
        $brandName = $siteSettings->get('site_name') ?: config('app.default_name', 'Ecommerce');
        $brandNameFontSize = (int)($siteSettings->get('site_name_font_size') ?: 23);
        $brandNameFontSize = max(14, min(32, $brandNameFontSize));
        $brandTaglineFontSize = (int)($siteSettings->get('site_tagline_font_size') ?: 12);
        $brandTaglineFontSize = max(8, min(24, $brandTaglineFontSize));
        $brandLogoResizeWidth = (int)($siteSettings->get('logo_resize_width') ?: 600);
        $brandLogoResizeWidth = max(120, min(2400, $brandLogoResizeWidth));
        $brandLogoResizeHeight = (int)($siteSettings->get('logo_resize_height') ?: 200);
        $brandLogoResizeHeight = max(40, min(1200, $brandLogoResizeHeight));
        $brandLogoDisplayWidth = max(120, min(240, (int) round($brandLogoResizeWidth * 220 / 600)));
        $brandLogoDisplayHeight = max(40, min(82, (int) round($brandLogoResizeHeight * 73 / 200)));
        $brandLogoMobileWidth = max(90, min(150, (int) round($brandLogoResizeWidth * 150 / 600)));
        $brandLogoMobileHeight = max(30, min(54, (int) round($brandLogoResizeHeight * 50 / 200)));
        $storefrontThemeService = app(StorefrontThemeService::class);
        $storefrontTheme = $storefrontThemeService->fromSettings($siteSettings);
        $storefrontThemeCss = $storefrontThemeService->cssVariables($storefrontTheme);
        $brandLogoHeader = $storefrontThemeService->resolvedLogoPath($storefrontTheme, $siteSettings->get('site_logo') ?: null);
        config(['app.name' => $brandName]);
        View::share('siteSettings', $siteSettings);
        View::share('brandName', $brandName);
        View::share('brandLogo', $siteSettings->get('site_logo') ?: null);
        View::share('brandLogoHeader', $brandLogoHeader ?: ($siteSettings->get('site_logo') ?: null));
        View::share('brandFavicon', $siteSettings->get('favicon') ?: null);
        View::share('hasCustomBrandLogo', (bool)$siteSettings->get('site_logo'));
        View::share('hasCustomBrandFavicon', (bool)$siteSettings->get('favicon'));
        View::share('brandNameFontSize', $brandNameFontSize);
        View::share('brandTaglineFontSize', $brandTaglineFontSize);
        View::share('brandLogoDisplayWidth', $brandLogoDisplayWidth);
        View::share('brandLogoDisplayHeight', $brandLogoDisplayHeight);
        View::share('brandLogoMobileWidth', $brandLogoMobileWidth);
        View::share('brandLogoMobileHeight', $brandLogoMobileHeight);
        View::share('storefrontTheme', $storefrontTheme);
        View::share('storefrontThemeCss', $storefrontThemeCss);
        View::composer('partials.topbar', function ($view) {
            $view->with('topBar', app(\App\Services\TopBarService::class)->data());
        });
        View::composer('partials.mega-menu', function ($view) {
            $view->with('navbar', app(StorefrontNavbarService::class)->storefront());
        });
        View::composer(['admin.components.admin-header','admin.components.main-menu'], function ($view) {
            $counts = ['inventory'=>0,'orders'=>0,'messages'=>0,'notifications'=>0];
            $currentSettings = Cache::remember('site-settings', now()->addHours(6), function () {
                return DB::table('site_settings')->pluck('setting_value', 'setting_key');
            });
            if (Schema::hasTable('product') && Schema::hasColumn('product','stock_tracking')) $counts['inventory'] = DB::table('product')->whereNull('deleted_at')->where('stock_tracking',1)->where('stock_quantity','<=',5)->count();
            if (Schema::hasTable('orders')) $counts['orders'] = DB::table('orders')->whereIn('status',['pending','confirmed','processing'])->count();
            if (Schema::hasTable('support_requests')) {
                $counts['messages'] = DB::table('support_requests')->whereIn('status',['new','in_progress'])->count();
                $counts['messages'] += DB::table('product_reviews')->where('is_approved',0)->count();
                $counts['messages'] += DB::table('product_questions')->where('is_approved',0)->count();
            }
            if (Schema::hasTable('store_notifications')) $counts['notifications']=DB::table('store_notifications')->where('recipient_type','admin')->whereNull('read_at')->count();
            $view->with('adminHeaderCounts',$counts);
            $view->with('developmentModeActive', (string)$currentSettings->get('development_mode_enabled', '0') === '1');
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
