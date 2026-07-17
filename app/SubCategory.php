<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SubCategory extends Model
{
    protected $table = 'sub_category';
    protected $primaryKey = 'sub_category_id';
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::saved(function () { Cache::forget('mega-menu-tree'); });
        static::deleted(function () { Cache::forget('mega-menu-tree'); });
    }
}
