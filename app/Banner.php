<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Banner extends Model
{
    use SoftDeletes;

    protected $table = 'banners';
    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean',
        'use_product_image' => 'boolean',
        'open_in_new_tab' => 'boolean',
        'show_overlay' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::saved(function () { Cache::forget('homepage-banners'); });
        static::deleted(function () { Cache::forget('homepage-banners'); });
        static::restored(function () { Cache::forget('homepage-banners'); });
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function scopeVisible(Builder $query)
    {
        return $query->where('is_active', 1)
            ->where(function ($q) { $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()); })
            ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()); });
    }

    public function getResolvedLinkAttribute()
    {
        $type = $this->banner_type ?: 'custom';
        if ($type === 'product' && $this->product) return route('store.product.show', $this->product->id);
        if ($type === 'category' && $this->category) return route('store.category.show', $this->category->category_id);
        if (in_array($type, ['custom', 'campaign'], true)) return $this->isSafeLink($this->link_url) ? $this->link_url : null;
        return null;
    }

    public function getResolvedDesktopImageAttribute()
    {
        if ($this->use_product_image && $this->product && $this->product->product_image) return ltrim($this->product->product_image, '/');
        return $this->image_path ?: 'asset/front-end/img/home/pic 1.jpg';
    }

    public function getResolvedMobileImageAttribute()
    {
        return $this->mobile_image ?: $this->resolved_desktop_image;
    }

    public function getResolvedAltAttribute()
    {
        if ($this->title) return $this->title;
        if ($this->product) return $this->product->product_name;
        if ($this->category) return $this->category->category_name;
        return config('app.name').' promotion';
    }

    private function isSafeLink($url)
    {
        if (!$url) return false;
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) return true;
        return filter_var($url, FILTER_VALIDATE_URL) && strtolower((string)parse_url($url, PHP_URL_SCHEME)) === 'https';
    }
}
