<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    protected $table = 'category';
    protected $primaryKey = 'category_id';
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::saved(function () { Cache::forget('mega-menu-tree'); Cache::forget('xml-sitemap'); });
        static::deleted(function () { Cache::forget('mega-menu-tree'); Cache::forget('xml-sitemap'); });
    }

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class, 'category_id', 'category_id')
            ->where('publication_status', 1);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'category_id');
    }
}
