<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $guarded = [];
    protected $hidden = ['credentials'];
    protected $casts = [
        'supports_emi' => 'boolean', 'is_active' => 'boolean', 'show_at_checkout' => 'boolean',
        'allow_sandbox_at_checkout' => 'boolean', 'charge_enabled' => 'boolean',
        'display_charge_at_checkout' => 'boolean', 'require_transaction_id' => 'boolean',
        'require_sender_number' => 'boolean', 'require_payment_screenshot' => 'boolean',
        'is_archived' => 'boolean', 'availability_rules' => 'array', 'available_from' => 'datetime',
        'available_until' => 'datetime', 'credentials_updated_at' => 'datetime',
        'last_connection_test_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function (PaymentMethod $method) {
            if ($method->method_type === 'cash_on_delivery') $method->integration_mode = 'offline';
            if ($method->method_type === 'bank_transfer') $method->integration_mode = 'manual';
            if (in_array($method->method_type, ['payment_gateway', 'card_payment'], true) && !in_array($method->integration_mode, ['api', 'redirect', 'hosted_checkout'], true)) {
                $method->integration_mode = 'hosted_checkout';
            }
            if (in_array($method->method_type, ['cash_on_delivery', 'bank_transfer'], true)) $method->environment = 'live';
            if ($method->method_type !== 'mobile_financial_service') {
                $method->merchant_number = null;
                $method->account_type = null;
            }
            if ($method->method_type !== 'bank_transfer') $method->bank_details = null;
            if ($method->method_type !== 'qr_payment') {
                $method->qr_type = null;
                $method->qr_image_path = null;
            }
            if (!in_array($method->method_type, ['payment_gateway', 'card_payment'], true)) {
                $method->success_callback_url = null;
                $method->failure_callback_url = null;
                $method->cancel_callback_url = null;
                $method->webhook_url = null;
            }
            if (!in_array($method->method_type, ['mobile_financial_service', 'bank_transfer', 'qr_payment', 'manual_payment', 'custom'], true)) {
                $method->payment_verification_mode = 'automatic';
                $method->require_transaction_id = false;
                $method->require_sender_number = false;
                $method->require_payment_screenshot = false;
            }
        });
    }

    public function emiPlans(){return $this->hasMany(EmiPlan::class)->where('is_active',1)->orderBy('months');}
    public function transactions(){return $this->hasMany(PaymentTransaction::class);}
    public function publicName(){return $this->customer_label?:$this->name;}
    public function isOnline(){return in_array($this->integration_mode,['api','redirect','hosted_checkout'],true);}
    public function requiresManualVerification(){return $this->payment_verification_mode==='manual'||$this->integration_mode==='manual';}
}
