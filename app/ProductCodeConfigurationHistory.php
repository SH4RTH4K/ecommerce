<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductCodeConfigurationHistory extends Model
{
    protected $table = 'product_code_configuration_histories';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'changed_at' => 'datetime',
        'old_settings' => 'array',
        'new_settings' => 'array',
    ];

    public function configuration()
    {
        return $this->belongsTo(ProductCodeConfiguration::class, 'configuration_id');
    }
}
