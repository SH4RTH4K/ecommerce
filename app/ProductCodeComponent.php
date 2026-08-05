<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductCodeComponent extends Model
{
    protected $table = 'product_code_components';

    protected $guarded = [];

    protected $casts = [
        'format_options' => 'array',
        'is_required' => 'boolean',
    ];

    public function configuration()
    {
        return $this->belongsTo(ProductCodeConfiguration::class, 'configuration_id');
    }
}
