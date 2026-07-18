<?php

namespace Tests\Feature;

use App\PaymentMethod;
use App\Services\PaymentMethodAvailabilityService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentMethodsModernizationTest extends TestCase
{
    private function admin()
    {
        return DB::table('tbl_admin as a')
            ->join('admin_roles as r', 'r.id', '=', 'a.role_id')
            ->where('a.is_active', 1)
            ->where('r.permissions', 'like', '%"settings"%')
            ->select('a.*')
            ->first();
    }

    private function adminSession()
    {
        $admin = $this->admin();
        $this->assertNotNull($admin, 'A settings administrator is required for this lifecycle test.');

        return ['admin_id' => $admin->admin_id, 'admin_name' => $admin->admin_name];
    }

    public function testBangladeshPaymentAdminRendersWithoutSecrets()
    {
        $response = $this->withSession($this->adminSession())->get('/payment-methods');
        $response
            ->assertStatus(200)
            ->assertSee('Payment Methods')
            ->assertSee('Cash on Delivery')
            ->assertSee('bKash Manual')
            ->assertSee('Configuration issues')
            ->assertSee('Recent payment transactions')
            ->assertSee('Choose method')
            ->assertSee('Method setup')
            ->assertSee('Checkout rules')
            ->assertSee('Review &amp; activate', false)
            ->assertSee('data-methods="mobile_financial_service"', false)
            ->assertSee('data-methods="payment_gateway card_payment"', false)
            ->assertDontSee('SUPER_SECRET_VALUE');
        $this->assertSame(1, substr_count($response->getContent(), 'class="pm-form pm-adaptive-form"'));
    }

    public function testServerCalculatesFeesAndEligibility()
    {
        DB::beginTransaction();
        try {
            $method = PaymentMethod::create([
                'name' => 'Test bKash', 'code' => 'test_bkash_'.strtolower(str_random(5)), 'type' => 'mobile',
                'method_type' => 'mobile_financial_service', 'provider' => 'bKash', 'integration_mode' => 'manual',
                'environment' => 'live', 'customer_label' => 'Pay with bKash', 'show_at_checkout' => 1,
                'is_active' => 1, 'is_archived' => 0, 'minimum_order_amount' => 500, 'maximum_order_amount' => 50000,
                'charge_enabled' => 1, 'charge_type' => 'percentage', 'charge_value' => 1.5,
                'charge_payer' => 'customer', 'display_order' => 99,
            ]);
            $service = app(PaymentMethodAvailabilityService::class);
            $this->assertNull($service->error($method, 10000));
            $this->assertSame(150.0, $service->charge($method, 10000));
            $this->assertStringContainsString('at least', $service->error($method, 100));
            $this->assertStringContainsString('৳', $service->error($method, 100));
            $method->environment = 'sandbox';
            $method->allow_sandbox_at_checkout = false;
            $this->assertStringContainsString('test mode', $service->error($method, 10000));
            $method->environment = 'live';
            $method->integration_mode = 'hosted_checkout';
            $method->connection_status = 'configuration_required';
            $this->assertStringContainsString('verified gateway adapter', $service->error($method, 10000));
        } finally {
            DB::rollBack();
        }
    }

    public function testManualPaymentCompletesCheckoutAndCanOnlyBeVerifiedOnce()
    {
        DB::beginTransaction();
        try {
            DB::table('site_settings')->where('setting_key', 'development_mode_enabled')->update(['setting_value' => '0']);
            Cache::forget('site-settings');

            $product = DB::table('product')->where('publication_status', 1)->first();
            if (!$product) {
                $productId = DB::table('product')->insertGetId([
                    'product_id' => 'PAY-CYCLE-'.strtoupper(str_random(6)), 'sku' => 'PAY-CYCLE-'.strtoupper(str_random(6)),
                    'category_id' => '1', 'sub_category' => '1', 'manufacturer_id' => '1', 'product_model' => 'Lifecycle',
                    'product_name' => 'Payment Lifecycle Product', 'Product_description' => 'Automated lifecycle fixture',
                    'offer_price' => 1000, 'regular_price' => 1000, 'purchase_price' => 700, 'product_condition' => 'In Stock',
                    'stock_quantity' => 20, 'stock_tracking' => 1, 'product_image' => 'asset/front-end/img/no-image.png',
                    'publication_status' => 1, 'top_product' => 0, 'is_new_arrival' => 0, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $product = DB::table('product')->where('id', $productId)->first();
            }
            $zone = DB::table('delivery_zones')->where('is_active', 1)->first();
            $location = DB::table('inventory_locations')->where('is_default', 1)->where('is_active', 1)->first();
            $this->assertNotNull($product);
            $this->assertNotNull($zone);
            $this->assertNotNull($location);

            DB::table('product')->where('id', $product->id)->update([
                'product_condition' => 'In Stock', 'stock_tracking' => 1, 'stock_quantity' => 20,
            ]);
            DB::table('product_location_stock')->updateOrInsert(
                ['location_id' => $location->id, 'product_id' => $product->id],
                ['quantity' => 20, 'created_at' => now(), 'updated_at' => now()]
            );

            $method = PaymentMethod::create([
                'name' => 'Lifecycle bKash', 'code' => 'lifecycle_'.strtolower(str_random(8)), 'type' => 'mobile',
                'method_type' => 'mobile_financial_service', 'provider' => 'bKash', 'integration_mode' => 'manual',
                'environment' => 'live', 'customer_label' => 'Lifecycle bKash', 'merchant_number' => '01700000000',
                'account_type' => 'merchant', 'payment_verification_mode' => 'manual', 'require_transaction_id' => 1,
                'require_sender_number' => 1, 'show_at_checkout' => 1, 'is_active' => 1, 'is_archived' => 0,
                'charge_enabled' => 1, 'charge_type' => 'fixed', 'charge_value' => 25, 'charge_payer' => 'customer',
                'display_order' => 999,
            ]);
            $providerReference = 'TXN'.strtoupper(str_random(12));
            $session = ['cart' => [$product->id => 1]];

            $response = $this->withSession($session)->from('/checkout')->post('/checkout', [
                'customer_name' => 'Lifecycle Customer', 'phone' => '01710000000', 'email' => 'cycle@example.com',
                'address' => 'Dhaka, Bangladesh', 'city' => 'Dhaka', 'delivery_zone_id' => $zone->id,
                'payment_method_id' => $method->id, 'payment_transaction_id' => $providerReference,
                'payment_sender_number' => '01710000000',
            ]);

            $order = DB::table('orders')->where('payment_method_id', $method->id)->latest('id')->first();
            $this->assertNotNull($order);
            $response->assertRedirect(route('checkout.success', $order->id));
            $this->assertSame('awaiting_verification', $order->payment_status);
            $this->assertSame(25.0, (float) $order->payment_charge);
            $this->assertSame((float) $order->subtotal + (float) $order->delivery_charge + 25.0, (float) $order->total);

            $transaction = DB::table('payment_transactions')->where('order_id', $order->id)->first();
            $this->assertNotNull($transaction);
            $this->assertSame($providerReference, $transaction->provider_reference);
            $this->assertSame('awaiting_verification', $transaction->status);

            $adminSession = $this->adminSession();
            $this->withSession($adminSession)->from('/payment-methods')->post('/payment-transactions/'.$transaction->id.'/verify', [
                'decision' => 'verified',
            ])->assertRedirect('/payment-methods')->assertSessionHas('message');
            $this->assertSame('verified', DB::table('orders')->where('id', $order->id)->value('payment_status'));
            $this->assertSame('verified', DB::table('payment_transactions')->where('id', $transaction->id)->value('status'));

            $this->withSession($adminSession)->post('/payment-transactions/'.$transaction->id.'/verify', [
                'decision' => 'rejected', 'reason' => 'Duplicate review attempt',
            ])->assertStatus(422);
        } finally {
            DB::rollBack();
            Cache::forget('site-settings');
        }
    }

    public function testAdministratorCanCreatePreviewDuplicateToggleAndDeleteAMethodSecurely()
    {
        $this->withoutExceptionHandling();
        DB::beginTransaction();
        try {
            $session = $this->adminSession();
            $code = 'admin_cycle_'.strtolower(str_random(8));
            $payload = [
                'name' => 'Admin Cycle Method', 'code' => $code, 'method_type' => 'manual_payment',
                'provider' => 'Custom', 'integration_mode' => 'offline', 'environment' => 'sandbox',
                'customer_label' => 'Admin Cycle Payment', 'charge_type' => 'fixed', 'charge_value' => 0,
                'charge_payer' => 'merchant', 'payment_verification_mode' => 'manual', 'display_order' => 500,
                'show_at_checkout' => 1, 'credential_key' => 'api_secret', 'credential_value' => 'CYCLE_SECRET_VALUE',
            ];
            $this->withSession($session)->post('/payment-methods', $payload)
                ->assertRedirect('/payment-methods')->assertSessionHas('message');

            $method = PaymentMethod::where('code', $code)->firstOrFail();
            $this->assertNotSame('CYCLE_SECRET_VALUE', $method->credentials);
            $this->assertSame('CYCLE_SECRET_VALUE', json_decode(Crypt::decryptString($method->credentials), true)['api_secret']);
            $other = PaymentMethod::create([
                'name' => 'Unchanged Method', 'code' => 'unchanged_'.strtolower(str_random(8)), 'type' => 'offline',
                'method_type' => 'manual_payment', 'integration_mode' => 'offline', 'environment' => 'live',
                'customer_label' => 'Do Not Change', 'show_at_checkout' => 1, 'is_active' => 0, 'display_order' => 501,
            ]);
            $this->withSession($session)->get('/payment-methods/'.$method->id.'/edit')
                ->assertOk()->assertSee('Edit Admin Cycle Payment')->assertSee('Save this payment method');
            $update = array_merge($payload, [
                'id' => $method->id, 'customer_label' => 'Updated Individual Method', 'short_instruction' => '',
                'credential_value' => '', 'is_active' => 1, 'show_at_checkout' => 1,
            ]);
            $this->withSession($session)->post('/payment-methods', $update)
                ->assertRedirect('/payment-methods')->assertSessionHas('message');
            $this->assertSame('Updated Individual Method', $method->fresh()->customer_label);
            $this->assertSame('Do Not Change', $other->fresh()->customer_label);
            $this->withSession($session)->get('/payment-methods/'.$method->id.'/preview')
                ->assertOk()->assertJsonMissing(['credentials' => 'CYCLE_SECRET_VALUE'])->assertJsonFragment(['name' => 'Updated Individual Method']);

            $this->withSession($session)->post('/payment-methods/'.$method->id.'/duplicate')
                ->assertRedirect()->assertSessionHas('message');
            $copy = PaymentMethod::where('code', 'like', $code.'_copy_%')->firstOrFail();
            $this->assertFalse($copy->is_active);
            $this->assertSame('sandbox', $copy->environment);
            $this->assertNull($copy->credentials);

            $this->withSession($session)->post('/payment-methods/'.$copy->id.'/toggle')
                ->assertRedirect()->assertSessionHas('message');
            $this->assertTrue($copy->fresh()->is_active);
            $this->withSession($session)->delete('/payment-methods/'.$copy->id)
                ->assertRedirect()->assertSessionHas('message');
            $this->assertNull(PaymentMethod::find($copy->id));
        } finally {
            DB::rollBack();
        }
    }
}
