<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductCodeHistory extends Model
{
    protected $table = 'product_code_histories';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(ProductCodeRegenerationBatch::class, 'batch_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function configuration()
    {
        return $this->belongsTo(ProductCodeConfiguration::class, 'configuration_id');
    }
}
