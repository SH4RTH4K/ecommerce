<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function brands()
    {
        return $this->hasMany(Manufacturer::class, 'company_id');
    }
}
