<?php

namespace App\Services;

use App\SiteContactItem;
use App\TopAnnouncement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TopBarService
{
    public function data()
    {
        return Cache::remember('site-top-bar', now()->addMinutes(10), function () {
            if (!Schema::hasTable('top_announcements') || !Schema::hasTable('site_contact_items')) return $this->emptyData();
            $settings = Cache::remember('site-settings', now()->addHours(6), function () {
                return DB::table('site_settings')->pluck('setting_value', 'setting_key');
            });
            $announcements = TopAnnouncement::currentlyVisible()->forTopBar()->ordered()->get();
            $contacts = SiteContactItem::active()->ordered()->get()->map(function ($contact) {
                $contact->resolved_url = $contact->resolved_url;
                return $contact;
            });
            $support = $this->supportItem($settings);
            if ($support) $contacts->push($support);
            $mode = optional($announcements->first())->display_mode ?: 'static';
            if (in_array($mode, ['static','scrolling'], true)) $announcements = $announcements->take(1);
            return compact('settings','announcements','contacts','mode');
        });
    }

    public function clear() { Cache::forget('site-top-bar'); }

    private function supportItem($settings)
    {
        if (!$this->on($settings->get('top_bar_show_support_link', '1')) || !$this->on($settings->get('support_link_enabled', '0'))) return null;
        $type = $settings->get('support_link_type', 'contact_page');
        $url = null;
        if ($type === 'contact_page') $url = url('/contact-us');
        elseif ($type === 'email' && $settings->get('support_link_url')) $url = 'mailto:'.$settings->get('support_link_url');
        elseif ($type === 'whatsapp' && $settings->get('support_link_url')) $url = 'https://wa.me/'.preg_replace('/\D/', '', $settings->get('support_link_url'));
        elseif (in_array($type, ['support_page','custom_url'], true)) $url = $settings->get('support_link_url');
        if (!$url || !$settings->get('support_link_label')) return null;
        return (object)['contact_type'=>'support_page','label'=>$settings->get('support_link_label'),'value'=>$settings->get('support_link_label'),'resolved_url'=>$url,'icon'=>$settings->get('support_link_icon') ?: 'fa-life-ring','show_on_desktop'=>true,'show_on_mobile'=>true,'open_in_new_tab'=>$this->on($settings->get('support_link_open_new_tab','0'))];
    }

    private function on($value) { return in_array($value, [true,1,'1','true','on'], true); }
    private function emptyData() { return ['settings'=>collect(), 'announcements'=>collect(), 'contacts'=>collect(), 'mode'=>'static']; }
}
