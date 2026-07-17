<?php

namespace App\Providers;

use App\Category;
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
        $brandName = $siteSettings->get('site_name') ?: config('app.name', 'Ecommerce');
        config(['app.name' => $brandName]);
        View::share('siteSettings', $siteSettings);
        View::share('brandName', $brandName);
        View::share('brandLogo', $siteSettings->get('site_logo') ?: 'asset/front-end/img/ecommerce-logo.png');
        View::share('brandFavicon', $siteSettings->get('favicon') ?: 'favicon.ico');
        View::composer('partials.mega-menu', function ($view) {
            $categoryTree = Cache::remember('mega-menu-tree', now()->addHours(6), function () {
                return Category::with('subCategories')
                    ->withCount(['products as published_products_count' => function ($query) {
                        $query->where('publication_status', 1);
                    }])
                    ->where('publication_status', 1)
                    ->orderBy('category_name')
                    ->get();
            });

            $view->with('categoryTree', $categoryTree);
        });
        View::composer(['admin.components.admin-header','admin.components.main-menu'], function ($view) {
            $counts = ['inventory'=>0,'orders'=>0,'messages'=>0,'notifications'=>0];
            if (Schema::hasTable('product') && Schema::hasColumn('product','stock_tracking')) $counts['inventory'] = DB::table('product')->where('stock_tracking',1)->where('stock_quantity','<=',5)->count();
            if (Schema::hasTable('orders')) $counts['orders'] = DB::table('orders')->whereIn('status',['pending','confirmed','processing'])->count();
            if (Schema::hasTable('support_requests')) {
                $counts['messages'] = DB::table('support_requests')->whereIn('status',['new','in_progress'])->count();
                $counts['messages'] += DB::table('product_reviews')->where('is_approved',0)->count();
                $counts['messages'] += DB::table('product_questions')->where('is_approved',0)->count();
            }
            if (Schema::hasTable('store_notifications')) $counts['notifications']=DB::table('store_notifications')->where('recipient_type','admin')->whereNull('read_at')->count();
            $view->with('adminHeaderCounts',$counts);
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
