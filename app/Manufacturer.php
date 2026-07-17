<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    protected $table = 'manufacturer';
    protected $primaryKey = 'manufacturer_id';
    protected $guarded = [];
}
