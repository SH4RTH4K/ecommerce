<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductCodeSequence extends Model
{
    protected $table = 'product_code_sequences';

    protected $guarded = [];

    protected $casts = [
        'last_number' => 'integer',
    ];

    public function configuration()
    {
        return $this->belongsTo(ProductCodeConfiguration::class, 'configuration_id');
    }
}
