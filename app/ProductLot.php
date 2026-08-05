<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductLot extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = ['manufactured_at' => 'date', 'expires_at' => 'date'];
    public function product() { return $this->belongsTo(Product::class); }
}
