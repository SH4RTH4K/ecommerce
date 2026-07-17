<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded = [];
    protected $dates = ['starts_at', 'expires_at'];
    protected $casts = ['is_active' => 'boolean'];

    public function availabilityError($subtotal)
    {
        if (!$this->is_active) return 'This coupon is inactive.';
        if ($this->starts_at && now()->lt($this->starts_at)) return 'This coupon is not available yet.';
        if ($this->expires_at && now()->gt($this->expires_at)) return 'This coupon has expired.';
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return 'This coupon usage limit has been reached.';
        if ($subtotal < $this->minimum_order) return 'Minimum order for this coupon is ৳'.number_format($this->minimum_order).'.';
        return null;
    }

    public function discountFor($subtotal)
    {
        $discount = $this->discount_type === 'percent' ? $subtotal * ((float)$this->discount_value / 100) : (float)$this->discount_value;
        if ($this->maximum_discount) $discount = min($discount, (float)$this->maximum_discount);
        return round(min($discount, $subtotal), 2);
    }
}
