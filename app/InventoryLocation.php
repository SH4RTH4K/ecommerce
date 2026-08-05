<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryLocation extends Model
{
    protected $table = 'inventory_locations';

    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeBranches($query)
    {
        return $query->where('type', 'branch');
    }

    public function getBranchCodeAttribute(): ?string
    {
        return $this->code ?: null;
    }

    public function setBranchCodeAttribute($value): void
    {
        $this->attributes['code'] = normalize_business_code($value, 30);
    }
}
