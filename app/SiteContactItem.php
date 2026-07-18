<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SiteContactItem extends Model
{
    protected $guarded = [];
    protected $casts = ['is_primary'=>'boolean','is_active'=>'boolean','show_on_desktop'=>'boolean','show_on_mobile'=>'boolean','open_in_new_tab'=>'boolean'];

    public function scopeActive($query) { return $query->where('is_active', 1); }
    public function scopeOrdered($query) { return $query->orderByDesc('is_primary')->orderBy('display_order')->orderBy('id'); }

    public function getResolvedUrlAttribute()
    {
        if ($this->contact_type === 'email') return 'mailto:'.trim($this->value);
        if (in_array($this->contact_type, ['phone','mobile','hotline'], true)) return 'tel:'.preg_replace('/[^+0-9]/', '', $this->value);
        if ($this->contact_type === 'whatsapp') {
            $url = 'https://wa.me/'.preg_replace('/\D/', '', $this->value);
            return $this->default_message ? $url.'?text='.rawurlencode($this->default_message) : $url;
        }
        return $this->link_url;
    }
}
