<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class HomepageFeatureCard extends Model
{
    use SoftDeletes;

    protected $table = 'homepage_feature_cards';
    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean', 'open_in_new_tab' => 'boolean', 'use_product_image' => 'boolean',
        'use_product_name' => 'boolean', 'use_product_price' => 'boolean',
        'publish_from' => 'datetime', 'publish_until' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => self::clearCache());
        static::deleted(fn () => self::clearCache());
        static::restored(fn () => self::clearCache());
    }

    public static function clearCache(): void
    {
        Cache::forget('homepage-feature-cards');
        Cache::forget('site-settings');
    }

    public function category() { return $this->belongsTo(Category::class, 'category_id', 'category_id'); }
    public function subcategory() { return $this->belongsTo(SubCategory::class, 'sub_category_id', 'sub_category_id'); }
    public function product() { return $this->belongsTo(Product::class, 'product_id', 'id'); }
    public function manufacturer() { return $this->belongsTo(Manufacturer::class, 'manufacturer_id', 'manufacturer_id'); }

    public function scopeVisible($query)
    {
        return $query->where('is_active', 1)
            ->where(function ($q) { $q->whereNull('publish_from')->orWhere('publish_from', '<=', now()); })
            ->where(function ($q) { $q->whereNull('publish_until')->orWhere('publish_until', '>=', now()); });
    }

    public function resolvedLink(): ?string
    {
        return match ($this->link_type) {
            'CATEGORY' => $this->category ? route('store.category.show', $this->category->category_id) : null,
            'SUBCATEGORY' => $this->subcategory ? url('/product-by-sub-category/'.$this->subcategory->sub_category_id) : null,
            'PRODUCT' => $this->product ? route('store.product.show', $this->product->id) : null,
            'BRAND' => $this->manufacturer ? url('/all-manufacturer-by-id/'.$this->manufacturer->manufacturer_id) : null,
            'CONTACT_PAGE' => url('/contact-us'),
            'SHOP_PRODUCTS' => url('/#products'),
            'ANCHOR', 'CUSTOM_URL', 'INTERNAL_PAGE' => $this->safeUrl($this->custom_url),
            default => null,
        };
    }

    public function resolvedImage(): ?string
    {
        if ($this->image_path) return ltrim($this->image_path, '/');
        if ($this->use_product_image && $this->product && $this->product->product_image) return ltrim($this->product->product_image, '/');
        return null;
    }

    public function resolvedAlt(): string
    {
        return trim((string) ($this->image_alt ?: ($this->product->product_name ?? $this->title ?? $this->name)));
    }

    public function isEntityValid(): bool
    {
        return match ($this->link_type) {
            'CATEGORY' => (bool) ($this->category && (int) $this->category->publication_status === 1),
            'SUBCATEGORY' => (bool) ($this->subcategory && (int) $this->subcategory->publication_status === 1),
            'PRODUCT' => (bool) ($this->product && (int) $this->product->publication_status === 1),
            'BRAND' => (bool) ($this->manufacturer && (int) $this->manufacturer->publication_status === 1),
            default => true,
        };
    }

    private function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') return null;
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) return $url;
        return filter_var($url, FILTER_VALIDATE_URL) && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? $url : null;
    }
}
