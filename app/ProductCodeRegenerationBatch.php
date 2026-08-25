<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductCodeRegenerationBatch extends Model
{
    protected $table = 'product_code_regeneration_batches';
    protected $guarded = [];
    protected $casts = ['preserve_sequence' => 'boolean', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
}
