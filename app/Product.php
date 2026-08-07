<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

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
        'prescription_required' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::saved(function () { Cache::forget('mega-menu-tree'); Cache::forget('xml-sitemap'); });
        static::deleted(function () { Cache::forget('mega-menu-tree'); Cache::forget('xml-sitemap'); });
        static::restored(function () { Cache::forget('mega-menu-tree'); Cache::forget('xml-sitemap'); });
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

    public function getProductDescriptionAttribute($value)
    {
        if ($value !== null && $value !== '') {
            return $value;
        }

        return $this->attributes['Product_description'] ?? null;
    }

    public function setProductDescriptionAttribute($value): void
    {
        $this->attributes['Product_description'] = $value;
    }

    public function getProductCodeAttribute($value): ?string
    {
        $value = trim((string) $value);
        if ($value !== '') {
            return $value;
        }

        $sku = trim((string) ($this->attributes['sku'] ?? ''));
        if ($sku !== '') {
            return $sku;
        }

        $legacy = trim((string) ($this->attributes['product_id'] ?? ''));

        return $legacy !== '' ? $legacy : null;
    }

    public function getSkuAttribute($value): ?string
    {
        $value = trim((string) $value);
        if ($value !== '') {
            return $value;
        }

        $productCode = trim((string) ($this->attributes['product_code'] ?? ''));
        if ($productCode !== '') {
            return $productCode;
        }

        $legacy = trim((string) ($this->attributes['product_id'] ?? ''));

        return $legacy !== '' ? $legacy : null;
    }

    public function setProductCodeAttribute($value): void
    {
        $normalized = normalize_product_code($value, 100);
        $this->attributes['product_code'] = $normalized;
        $this->attributes['sku'] = $normalized;
    }

    public function setSkuAttribute($value): void
    {
        $normalized = normalize_product_code($value, 100);
        $this->attributes['sku'] = $normalized;
        if (! array_key_exists('product_code', $this->attributes) || trim((string) $this->attributes['product_code']) === '') {
            $this->attributes['product_code'] = $normalized;
        }
    }

    public function getWarrantyDisplayAttribute(): ?string
    {
        $warranty = trim((string) ($this->warranty ?? ''));
        if ($warranty !== '') {
            return $warranty;
        }

        return 'No Warranty';
    }

    private function extractWarrantyFromSpecifications(array $specifications): ?string
    {
        foreach ($specifications as $key => $value) {
            if (is_array($value)) {
                $nested = $this->extractWarrantyFromSpecifications($value);
                if ($nested !== null) {
                    return $nested;
                }
            }

            if (is_string($value) && Str::contains(Str::lower((string) $key), 'warranty')) {
                $value = trim($value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
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

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id', 'manufacturer_id');
    }

    public function series()
    {
        return $this->belongsTo(ProductSeries::class, 'product_series_id');
    }

    public function branch()
    {
        return $this->belongsTo(InventoryLocation::class, 'branch_id');
    }

    public function attributeValues()
    {
        return $this->hasMany(ProductAttributeValue::class, 'product_id')->with('attribute');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function lots()
    {
        return $this->hasMany(ProductLot::class, 'product_id');
    }
}
