<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCodeConfiguration extends Model
{
    use SoftDeletes;
    protected $table = 'product_code_configurations';

    protected $guarded = [];

    protected $casts = [
        'auto_generate' => 'boolean',
        'strict_mode' => 'boolean',
        'skip_empty_components' => 'boolean',
        'allow_manual_override' => 'boolean',
        'allow_regeneration' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    public function scopeForType($query, string $codeType)
    {
        return $query->where('code_type', $codeType);
    }

    public function components()
    {
        return $this->hasMany(ProductCodeComponent::class, 'configuration_id')->orderBy('position');
    }

    public function sequences()
    {
        return $this->hasMany(ProductCodeSequence::class, 'configuration_id');
    }

    public function histories()
    {
        return $this->hasMany(ProductCodeHistory::class, 'configuration_id');
    }

    public function changeHistories()
    {
        return $this->hasMany(ProductCodeConfigurationHistory::class, 'configuration_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch()
    {
        return $this->belongsTo(InventoryLocation::class, 'branch_id');
    }
}
