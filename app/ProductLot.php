<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductLot extends Model
{
    protected $guarded = [];
    protected $casts = ['manufactured_at' => 'date', 'expires_at' => 'date'];
    public function product() { return $this->belongsTo(Product::class); }
}
