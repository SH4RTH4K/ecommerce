<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class TopAnnouncement extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = ['is_active'=>'boolean','show_type_badge'=>'boolean','open_in_new_tab'=>'boolean','show_on_desktop'=>'boolean','show_on_mobile'=>'boolean','starts_at'=>'datetime','expires_at'=>'datetime'];

    protected static function booted()
    {
        static::deleted(function () {
            Cache::forget('site-top-bar');
        });

        static::restored(function () {
            Cache::forget('site-top-bar');
        });
    }

    public function scopeCurrentlyVisible($query)
    {
        return $query->where('is_active', 1)
            ->where(function ($q) { $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()); })
            ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()); });
    }

    public function scopeForTopBar($query)
    {
        return $query->whereIn('display_location', ['top_bar','top_bar_and_login','all_public_pages']);
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('priority')->orderBy('display_order')->latest('created_at');
    }
}
