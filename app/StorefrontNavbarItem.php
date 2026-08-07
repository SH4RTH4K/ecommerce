<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StorefrontNavbarItem extends Model
{
    protected $table = 'storefront_navbar_items';

    protected $fillable = [
        'category_id',
        'display_name',
        'show_in_navbar',
        'priority',
        'show_subcategories',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'priority' => 'integer',
        'show_in_navbar' => 'boolean',
        'show_subcategories' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function label()
    {
        return trim((string) ($this->display_name ?: optional($this->category)->category_name));
    }

}
