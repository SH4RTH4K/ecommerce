<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    protected $table = 'product';

    protected $guarded = [];

    protected $casts = [
        'publication_status' => 'boolean',
        'top_product' => 'boolean',
        'is_new_arrival' => 'boolean',
        'key_features' => 'array',
        'specifications' => 'array',
        'gallery_images' => 'array',
        'stock_tracking' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::saved(function () { Cache::forget('mega-menu-tree'); Cache::forget('xml-sitemap'); });
        static::deleted(function () { Cache::forget('mega-menu-tree'); Cache::forget('xml-sitemap'); });
    }

    public function getDiscountPercentAttribute()
    {
        if (!$this->has_offer) {
            return null;
        }

        return (int) round((($this->regular_price - $this->offer_price) / $this->regular_price) * 100);
    }

    public function getHasOfferAttribute()
    {
        return $this->offer_price !== null
            && (float) $this->offer_price >= 0
            && (float) $this->offer_price < (float) $this->regular_price;
    }

    public function getSellingPriceAttribute()
    {
        return $this->has_offer ? (float) $this->offer_price : (float) $this->regular_price;
    }

    public static function sellingPriceSql($prefix = '')
    {
        $prefix = $prefix ? rtrim($prefix, '.').'.' : '';
        return 'CASE WHEN '.$prefix.'offer_price IS NOT NULL AND '.$prefix.'offer_price < '.$prefix.'regular_price THEN '.$prefix.'offer_price ELSE '.$prefix.'regular_price END';
    }

    public function getImageUrlAttribute()
    {
        $path = ltrim((string) $this->product_image, '/');

        if ($path && (file_exists(public_path($path)) || file_exists(base_path($path)))) {
            return asset($path);
        }

        return asset('asset/front-end/img/home/pic 1.jpg');
    }

    public function getAllImagesAttribute()
    {
        return collect(array_merge([$this->image_url], (array) $this->gallery_images))->filter()->unique()->values();
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id', 'manufacturer_id');
    }

    public function series()
    {
        return $this->belongsTo(ProductSeries::class, 'product_series_id');
    }

    public function attributeValues()
    {
        return $this->hasMany(ProductAttributeValue::class, 'product_id')->with('attribute');
    }
}
