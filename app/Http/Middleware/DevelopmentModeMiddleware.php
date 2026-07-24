<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DevelopmentModeMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            if (!Schema::hasTable('site_settings')) {
                return $next($request);
            }

            $settings = Cache::remember('site-settings', now()->addHours(6), function () {
                return DB::table('site_settings')->pluck('setting_value', 'setting_key');
            });

            if (!$this->enabled($settings->get('development_mode_enabled'))) {
                return $next($request);
            }

            if ($this->isRequiredAsset($request) || $request->routeIs('admin.login', 'admin.login.submit', 'admin.legacy-index')) {
                return $next($request);
            }

            if ($this->isActiveAdministrator($request)) {
                return $next($request);
            }

            $types = [
                'development' => ['label' => 'Development in Progress', 'icon' => 'code'],
                'maintenance' => ['label' => 'Scheduled Maintenance', 'icon' => 'tools'],
                'coming_soon' => ['label' => 'Coming Soon', 'icon' => 'rocket'],
                'system_upgrade' => ['label' => 'System Upgrade', 'icon' => 'upgrade'],
                'emergency' => ['label' => 'Temporary Service Interruption', 'icon' => 'warning'],
                'custom' => ['label' => 'Custom Message', 'icon' => 'message'],
            ];
            $type = $settings->get('development_mode_message_type', 'maintenance');
            if (!isset($types[$type])) {
                $type = 'maintenance';
            }

            return response()->view('development-mode', [
                'mode' => [
                    'type' => $type,
                    'badge' => $types[$type]['label'],
                    'icon' => $types[$type]['icon'],
                    'title' => $settings->get('development_mode_title') ?: 'Website Under Development',
                    'message' => $settings->get('development_mode_message') ?: 'We are currently improving our website. Please check back again soon.',
                    'additional_message' => $settings->get('development_mode_additional_message'),
                    'availability_text' => $settings->get('development_mode_availability_text'),
                    'show_admin_login' => $this->enabled($settings->get('development_mode_show_admin_login', '1')),
                    'login_button_text' => $settings->get('development_mode_login_button_text') ?: 'Admin Login',
                    'site_name' => $settings->get('site_name') ?: config('app.name', 'Ecommerce'),
                    'logo' => $settings->get('site_logo'),
                    'favicon' => $settings->get('favicon'),
                    'copyright' => $settings->get('copyright_text'),
                ],
            ], 503)->header('Retry-After', '3600');
        } catch (\Throwable $exception) {
            Log::warning('Development Mode check failed open.', ['exception' => $exception->getMessage()]);
            return $next($request);
        }
    }

    private function enabled($value)
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }

    private function isActiveAdministrator($request)
    {
        $adminId = $request->session()->get('admin_id');
        if (!$adminId) {
            return false;
        }

        return DB::table('tbl_admin')->where('admin_id', $adminId)->where('is_active', 1)->exists();
    }

    private function isRequiredAsset($request)
    {
        return $request->is(
            'asset/*', 'css/*', 'js/*', 'svg/*', 'storage/*', 'fonts/*',
            'favicon.ico', 'robots.txt'
        );
    }
}
