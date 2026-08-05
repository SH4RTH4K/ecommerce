<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manufacturer extends Model
{
    use SoftDeletes;

    protected $table = 'manufacturer';
    protected $primaryKey = 'manufacturer_id';
    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function series()
    {
        return $this->hasMany(ProductSeries::class, 'manufacturer_id', 'manufacturer_id');
    }
}
