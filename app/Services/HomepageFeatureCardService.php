<?php

namespace App\Services;

use App\Category;
use App\HomepageFeatureCard;
use App\Manufacturer;
use App\Product;
use App\SubCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomepageFeatureCardService
{
    public const CONFIG_KEY = 'homepage_feature_card_config';

    public function defaults(): array
    {
        return ['layout'=>'STACKED','max_visible_cards'=>2,'card_gap'=>18,'equal_height'=>true,'slider_autoplay'=>true,'slider_interval'=>5,'slider_arrows'=>true,'slider_dots'=>true,'pause_on_hover'=>true];
    }

    public function config(): array
    {
        return Cache::remember('homepage-feature-card-config', now()->addHours(6), function () {
            $raw = DB::table('site_settings')->where('setting_key', self::CONFIG_KEY)->value('setting_value');
            $data = json_decode((string) $raw, true);
            return $this->normalizeConfig(is_array($data) ? $data : []);
        });
    }

    public function normalizeConfig(array $input): array
    {
        $config = array_replace($this->defaults(), $input);
        $config['layout'] = in_array(strtoupper((string) $config['layout']), ['STACKED','GRID','SLIDER'], true) ? strtoupper((string) $config['layout']) : 'STACKED';
        $config['max_visible_cards'] = strtoupper((string) $config['max_visible_cards']) === 'AUTO' ? 'AUTO' : max(1, min(5, (int) $config['max_visible_cards']));
        $config['card_gap'] = max(0, min(40, (int) $config['card_gap']));
        $config['slider_interval'] = max(3, min(30, (int) $config['slider_interval']));
        foreach (['equal_height','slider_autoplay','slider_arrows','slider_dots','pause_on_hover'] as $key) $config[$key] = filter_var($config[$key], FILTER_VALIDATE_BOOLEAN);
        return $config;
    }

    public function storefront(): array
    {
        $cards = Cache::remember('homepage-feature-cards', now()->addMinutes(10), function () {
            return HomepageFeatureCard::with(['category','subcategory','product','manufacturer'])
                ->visible()->orderBy('sort_order')->orderBy('id')->get()
                ->filter(fn ($card) => $card->isEntityValid())->values();
        });
        $config = $this->config();
        $limit = $config['max_visible_cards'] === 'AUTO' ? $cards->count() : (int) $config['max_visible_cards'];
        return ['cards' => $cards->take($limit)->values(), 'config' => $config];
    }

    public function adminData(): array
    {
        return [
            'cards' => HomepageFeatureCard::with(['category','subcategory','product','manufacturer'])->orderBy('sort_order')->orderBy('id')->get(),
            'categories' => Category::where('publication_status',1)->orderBy('category_name')->get(['category_id','category_name']),
            'subcategories' => SubCategory::where('publication_status',1)->orderBy('sub_category_name')->get(['sub_category_id','category_id','sub_category_name']),
            'products' => Product::where('publication_status',1)->orderBy('product_name')->get(['id','product_name','product_image','regular_price','offer_price']),
            'manufacturers' => Manufacturer::where('publication_status',1)->orderBy('manufacturer_name')->get(['manufacturer_id','manufacturer_name']),
            'config' => $this->config(),
        ];
    }

    public function clear(): void
    {
        Cache::forget('homepage-feature-cards'); Cache::forget('homepage-feature-card-config'); Cache::forget('site-settings');
    }
}
