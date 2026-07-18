<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductSeries extends Model
{
    protected $table = 'product_series';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function brand()
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id', 'manufacturer_id');
    }
}
