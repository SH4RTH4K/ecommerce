<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
    public function product() { return $this->belongsTo(Product::class); }
}
