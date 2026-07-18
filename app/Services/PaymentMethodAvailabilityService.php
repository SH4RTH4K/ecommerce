<?php

namespace App\Services;

use App\PaymentMethod;
use Carbon\Carbon;

class PaymentMethodAvailabilityService
{
    public function error(PaymentMethod $method, $total, $zoneId = null, $adminPreview = false)
    {
        if (!$method->is_active) return 'This payment method is temporarily unavailable.';
        if (!$method->show_at_checkout) return 'This payment method is not shown at checkout.';
        if ($method->environment === 'sandbox' && !$method->allow_sandbox_at_checkout && !$adminPreview) return 'This payment method is in test mode.';
        if ($method->isOnline() && $method->connection_status !== 'connected') return 'This online payment method is not connected to a verified gateway adapter.';
        if ($method->minimum_order_amount !== null && $total < $method->minimum_order_amount) return 'Available for orders of at least ৳'.number_format($method->minimum_order_amount).'.';
        if ($method->maximum_order_amount !== null && $total > $method->maximum_order_amount) return 'Available for orders up to ৳'.number_format($method->maximum_order_amount).'.';

        $now = Carbon::now(config('app.timezone', 'Asia/Dhaka'));
        if ($method->available_from && $now->lt($method->available_from)) return 'This payment method is not available yet.';
        if ($method->available_until && $now->gt($method->available_until)) return 'This payment method is no longer available.';

        $rules = (array) $method->availability_rules;
        $blocked = array_map('intval', (array) ($rules['blocked_shipping_zones'] ?? []));
        $allowed = array_map('intval', (array) ($rules['allowed_shipping_zones'] ?? []));
        if ($zoneId && in_array((int) $zoneId, $blocked, true)) return 'This payment method is not available for your delivery zone.';
        if ($zoneId && $allowed && !in_array((int) $zoneId, $allowed, true)) return 'This payment method is not available for your delivery zone.';

        return null;
    }

    public function charge(PaymentMethod $method, $total)
    {
        if (!$method->charge_enabled || $method->charge_payer !== 'customer' || ($method->free_above_amount !== null && $total >= $method->free_above_amount)) return 0;

        if ($method->charge_type === 'percentage') $fee = $total * $method->charge_value / 100;
        elseif ($method->charge_type === 'fixed_plus_percentage') $fee = (float) $method->minimum_charge + $total * $method->charge_value / 100;
        else $fee = $method->charge_value;

        if ($method->minimum_charge !== null) $fee = max($fee, $method->minimum_charge);
        if ($method->maximum_charge !== null) $fee = min($fee, $method->maximum_charge);

        return round(max(0, $fee), 2);
    }

    public function health(PaymentMethod $method)
    {
        $issues = [];
        if (!$method->show_at_checkout) $issues[] = 'Hidden from checkout';
        if ($method->environment === 'sandbox' && !$method->allow_sandbox_at_checkout) $issues[] = 'Sandbox hidden from customers';
        if ($method->isOnline() && $method->connection_status !== 'connected') $issues[] = 'Gateway connection not verified';
        if ($method->method_type === 'mobile_financial_service' && $method->integration_mode === 'manual' && !$method->merchant_number) $issues[] = 'Merchant number missing';
        if ($method->method_type === 'bank_transfer' && !$method->bank_details) $issues[] = 'Bank details missing';

        return $issues;
    }
}
