<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
    public function product() { return $this->belongsTo(Product::class); }
}
