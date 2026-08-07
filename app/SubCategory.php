<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class SubCategory extends Model
{
    use SoftDeletes;

    protected $table = 'sub_category';
    protected $primaryKey = 'sub_category_id';
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::saved(function () { Cache::forget('mega-menu-tree'); Cache::forget('storefront-navbar-tree'); });
        static::deleted(function () { Cache::forget('mega-menu-tree'); Cache::forget('storefront-navbar-tree'); });
        static::restored(function () { Cache::forget('mega-menu-tree'); Cache::forget('storefront-navbar-tree'); });
    }
}
