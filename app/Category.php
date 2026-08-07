<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use SoftDeletes;

    protected $table = 'category';
    protected $primaryKey = 'category_id';
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::saved(function () { Cache::forget('mega-menu-tree'); Cache::forget('storefront-navbar-tree'); Cache::forget('xml-sitemap'); });
        static::deleted(function () { Cache::forget('mega-menu-tree'); Cache::forget('storefront-navbar-tree'); Cache::forget('xml-sitemap'); });
        static::restored(function () { Cache::forget('mega-menu-tree'); Cache::forget('storefront-navbar-tree'); Cache::forget('xml-sitemap'); });
    }

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class, 'category_id', 'category_id')
            ->where('publication_status', 1)
            ->orderBy('display_order')
            ->orderBy('sub_category_name');
    }

    public function navbarSubCategories()
    {
        return $this->hasMany(SubCategory::class, 'category_id', 'category_id')
            ->where('publication_status', 1)
            ->where('show_in_navbar', 1)
            ->orderByRaw('CASE WHEN navbar_order IS NULL OR navbar_order = 0 THEN 999999 ELSE navbar_order END')
            ->orderBy('sub_category_name');
    }

    public function navbarManageSubCategories()
    {
        return $this->hasMany(SubCategory::class, 'category_id', 'category_id')
            ->orderByRaw('CASE WHEN navbar_order IS NULL OR navbar_order = 0 THEN 999999 ELSE navbar_order END')
            ->orderBy('sub_category_name');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'category_id');
    }
}
