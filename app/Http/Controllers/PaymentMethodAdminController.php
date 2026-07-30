<?php

namespace App\Http\Controllers;

use App\PaymentMethod;
use App\Services\PaymentMethodAvailabilityService;
use App\Support\PublicUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentMethodAdminController extends Controller
{
    private $types = ['cash_on_delivery','mobile_financial_service','payment_gateway','bank_transfer','qr_payment','card_payment','manual_payment','custom'];
    private $modes = ['api','manual','redirect','hosted_checkout','offline'];

    public function index(Request $request, PaymentMethodAvailabilityService $availability)
    {
        $methods = PaymentMethod::with('emiPlans')->withCount('transactions')->orderBy('display_order')->get();
        $methods->each(function ($method) use ($availability) {
            $method->health_issues = $availability->health($method);
        });
        $transactions = DB::table('payment_transactions as t')
            ->join('payment_methods as m', 'm.id', '=', 't.payment_method_id')
            ->select('t.*', 'm.name as method_name')
            ->latest('t.id')
            ->limit(20)
            ->get();
        $zones = DB::table('delivery_zones')->where('is_active', 1)->orderBy('display_order')->get();

        return view('admin.admin-pages.payment-methods', compact('methods', 'transactions', 'zones'));
    }

    public function edit($id)
    {
        $method = PaymentMethod::with('emiPlans')->findOrFail($id);
        $zones = DB::table('delivery_zones')->where('is_active', 1)->orderBy('display_order')->get();

        return view('admin.admin-pages.payment-method-edit', compact('method', 'zones'));
    }

    public function save(Request $request)
    {
        $id = $request->integer('id') ?: null;
        $method = $id ? PaymentMethod::findOrFail($id) : null;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'code' => ['required','regex:/^[a-z0-9_\-]+$/','max:50',Rule::unique('payment_methods','code')->ignore($id)],
            'method_type' => ['required',Rule::in($this->types)],
            'provider' => 'nullable|string|max:100',
            'integration_mode' => ['required',Rule::in($this->modes)],
            'environment' => 'required|in:sandbox,live',
            'customer_label' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',
            'short_instruction' => 'nullable|string|max:500',
            'detailed_instruction' => 'nullable|string|max:4000',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:1024',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'maximum_order_amount' => 'nullable|numeric|min:0|gte:minimum_order_amount',
            'charge_type' => 'required|in:fixed,percentage,fixed_plus_percentage',
            'charge_value' => 'nullable|numeric|min:0',
            'charge_payer' => 'required|in:customer,merchant,included',
            'minimum_charge' => 'nullable|numeric|min:0',
            'maximum_charge' => 'nullable|numeric|min:0|gte:minimum_charge',
            'free_above_amount' => 'nullable|numeric|min:0',
            'display_order' => 'required|integer|min:0',
            'merchant_number' => 'nullable|string|max:100',
            'account_type' => 'nullable|in:merchant,personal,agent,other',
            'payment_verification_mode' => 'required|in:automatic,manual',
            'bank_details' => 'nullable|string|max:4000',
            'qr_type' => 'nullable|in:static,provider_generated,gateway_redirect,bangla_qr,custom',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date|after_or_equal:available_from',
            'success_callback_url' => 'nullable|string|max:500',
            'failure_callback_url' => 'nullable|string|max:500',
            'cancel_callback_url' => 'nullable|string|max:500',
            'webhook_url' => 'nullable|string|max:500',
        ], [
            'logo.uploaded' => 'The provider logo could not be uploaded. Choose a PNG, JPG, or WebP image no larger than 1 MB.',
            'logo.image' => 'The provider logo must be a valid PNG, JPG, or WebP image.',
            'logo.mimes' => 'The provider logo must be a PNG, JPG, or WebP image.',
            'logo.max' => 'The provider logo may not be larger than 1 MB.',
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach (['success_callback_url','failure_callback_url','cancel_callback_url','webhook_url'] as $key) {
                if ($request->filled($key) && !$this->safeUrl($request->input($key))) {
                    $validator->errors()->add($key, 'Use an internal path or HTTPS URL.');
                }
            }
            if ($request->method_type === 'mobile_financial_service' && $request->integration_mode === 'manual' && !$request->filled('merchant_number')) {
                $validator->errors()->add('merchant_number', 'A merchant number is required for manual MFS.');
            }
            if ($request->method_type === 'bank_transfer' && !$request->filled('bank_details')) {
                $validator->errors()->add('bank_details', 'Bank details are required.');
            }
        });
        $validator->validate();

        $data = $request->only([
            'name','method_type','provider','integration_mode','environment','customer_label','description',
            'short_instruction','detailed_instruction','icon','charge_type','charge_value','charge_payer',
            'minimum_charge','maximum_charge','free_above_amount','merchant_number','account_type',
            'payment_verification_mode','bank_details','qr_type','available_from','available_until',
            'success_callback_url','failure_callback_url','cancel_callback_url','webhook_url',
        ]);
        $data['code'] = str_slug($request->code, '_');
        $data['type'] = $this->legacyType($request->method_type);
        foreach (['minimum_order_amount','maximum_order_amount'] as $key) {
            $data[$key] = $request->filled($key) ? $request->input($key) : null;
        }
        $data['display_order'] = (int)$request->display_order;
        foreach (['supports_emi','is_active','show_at_checkout','allow_sandbox_at_checkout','charge_enabled','display_charge_at_checkout','require_transaction_id','require_sender_number','require_payment_screenshot'] as $key) {
            $data[$key] = $request->boolean($key);
        }
        $data['instructions'] = $data['short_instruction'] ?? null;
        $data['availability_rules'] = [
            'allowed_shipping_zones' => array_values(array_filter((array)$request->input('allowed_shipping_zones', []))),
            'blocked_shipping_zones' => array_values(array_filter((array)$request->input('blocked_shipping_zones', []))),
        ];

        $newLogoPath = null;
        $oldLogoPath = $method ? $method->logo_path : null;
        try {
            if ($request->hasFile('logo')) {
                $newLogoPath = PublicUpload::store($request->file('logo'), 'asset/front-end/img/payments/', 'payment-', ['png','jpg','jpeg','webp']);
                $data['logo_path'] = $newLogoPath;
            }
            if ($request->method_type === 'qr_payment') {
                $data['qr_image_path'] = $newLogoPath ?: ($method ? ($method->qr_image_path ?: $method->logo_path) : null);
            }
            if ($request->filled('credential_value')) {
                $credentials = [];
                if ($method && $method->credentials) {
                    try {
                        $credentials = json_decode(Crypt::decryptString($method->credentials), true) ?: [];
                    } catch (\Throwable $exception) {
                        $credentials = [];
                    }
                }
                $credentials[$request->input('credential_key', 'api_secret')] = $request->credential_value;
                $data['credentials'] = Crypt::encryptString(json_encode($credentials));
                $data['credentials_updated_at'] = now();
            }
            $data['updated_at'] = now();

            if ($method) {
                $method->update($data);
            } else {
                $data['created_at'] = now();
                $method = PaymentMethod::create($data);
            }
        } catch (\Throwable $exception) {
            if ($newLogoPath) $this->removePaymentImageIfUnused($newLogoPath, $method ? $method->id : null);
            report($exception);
            return Redirect::back()->withInput()->withErrors([
                'logo' => 'The payment method image could not be saved. Please try again or check upload storage permissions.',
            ]);
        }

        if ($newLogoPath && $oldLogoPath && $oldLogoPath !== $newLogoPath) {
            $this->removePaymentImageIfUnused($oldLogoPath, $method->id);
        }

        Cache::forget('active_payment_methods');
        $this->audit($id ? 'Payment Method Updated' : 'Payment Method Created', ['code' => $data['code']]);

        return Redirect::to('/payment-methods')->with('message', 'Payment method saved successfully.');
    }

    public function toggle($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->update(['is_active' => !$method->is_active]);
        Cache::forget('active_payment_methods');
        $this->audit('Payment Method Status Updated', ['id' => $method->id, 'active' => $method->is_active]);

        return back()->with('message', $method->is_active ? 'Payment method enabled.' : 'Payment method disabled.');
    }

    public function duplicate($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $copy = $method->replicate(['credentials','credentials_updated_at','connection_status','last_connection_test_at','last_connection_message']);
        $copy->name .= ' Copy';
        $copy->code = $method->code.'_copy_'.strtolower(str_random(4));
        $copy->is_active = false;
        $copy->environment = 'sandbox';
        $copy->save();

        return back()->with('message', 'Payment method duplicated in Sandbox mode.');
    }

    public function delete($id)
    {
        $method = PaymentMethod::findOrFail($id);
        if (DB::table('orders')->where('payment_method_id', $id)->exists() || $method->transactions()->exists()) {
            $method->update(['is_active' => 0, 'show_at_checkout' => 0, 'is_archived' => 1]);
            return back()->with('message', 'Method has transaction history and was archived instead of deleted.');
        }

        $logoPath = $method->logo_path;
        $qrPath = $method->qr_image_path;
        $method->delete();
        $this->removePaymentImageIfUnused($logoPath, $id);
        $this->removePaymentImageIfUnused($qrPath, $id);

        return back()->with('message', 'Payment method deleted.');
    }

    public function reorder(Request $request)
    {
        $this->validate($request, ['order' => 'required|array', 'order.*' => 'integer|exists:payment_methods,id']);
        foreach ($request->order as $index => $id) {
            PaymentMethod::where('id', $id)->update(['display_order' => $index]);
        }
        Cache::forget('active_payment_methods');

        return response()->json(['message' => 'Payment order updated.']);
    }

    public function preview($id, PaymentMethodAvailabilityService $availability)
    {
        $method = PaymentMethod::findOrFail($id);

        return response()->json([
            'name' => $method->publicName(),
            'instruction' => $method->short_instruction,
            'provider' => $method->provider,
            'environment' => $method->environment,
            'charge' => $availability->charge($method, 10000),
            'issues' => $availability->health($method),
        ]);
    }

    public function test($id)
    {
        $method = PaymentMethod::findOrFail($id);
        if (!$method->isOnline()) {
            $method->update([
                'connection_status' => 'not_applicable',
                'last_connection_test_at' => now(),
                'last_connection_message' => 'Manual and offline methods do not require a gateway connection.',
            ]);
            return back()->with('message', 'No gateway connection is required for this manual/offline method.');
        }

        $method->update([
            'connection_status' => 'configuration_required',
            'last_connection_test_at' => now(),
            'last_connection_message' => 'No verified provider adapter is installed.',
        ]);

        return back()->with('exception', 'Integration not configured. Add a verified provider adapter and merchant credentials before live activation.');
    }

    public function verifyTransaction(Request $request, $id)
    {
        $this->validate($request, ['decision' => 'required|in:verified,rejected', 'reason' => 'nullable|string|max:500']);
        DB::transaction(function () use ($request, $id) {
            $transaction = DB::table('payment_transactions')->where('id', $id)->lockForUpdate()->first();
            abort_unless($transaction, 404);
            abort_if(!in_array($transaction->status, ['pending','awaiting_verification'], true), 422, 'This transaction has already been reviewed.');
            DB::table('payment_transactions')->where('id', $id)->update([
                'status' => $request->decision,
                'failure_reason' => $request->decision === 'rejected' ? trim((string)$request->reason) : null,
                'verified_by' => session('admin_id'),
                'verified_at' => now(),
                'updated_at' => now(),
            ]);
            if ($transaction->order_id) {
                DB::table('orders')->where('id', $transaction->order_id)->update([
                    'payment_status' => $request->decision,
                    'updated_at' => now(),
                ]);
            }
        });
        $this->audit('Manual Payment '.$request->decision, ['transaction_id' => $id]);

        return back()->with('message', $request->decision === 'verified' ? 'Payment verified successfully.' : 'Payment rejected.');
    }

    private function legacyType($type)
    {
        return [
            'cash_on_delivery' => 'cash',
            'bank_transfer' => 'bank',
            'card_payment' => 'card',
            'payment_gateway' => 'card',
            'mobile_financial_service' => 'mobile',
        ][$type] ?? 'offline';
    }

    private function safeUrl($url)
    {
        return strpos($url, '/') === 0 && strpos($url, '//') !== 0
            || (filter_var($url, FILTER_VALIDATE_URL) && strtolower(parse_url($url, PHP_URL_SCHEME)) === 'https');
    }

    private function removePaymentImageIfUnused($path, $excludingId = null)
    {
        $path = ltrim(str_replace('\\', '/', (string)$path), '/');
        if (!$path || strpos($path, 'asset/front-end/img/payments/') !== 0) return false;

        $used = PaymentMethod::query()
            ->when($excludingId, function ($query) use ($excludingId) {
                $query->where('id', '<>', $excludingId);
            })
            ->where(function ($query) use ($path) {
                $query->where('logo_path', $path)->orWhere('qr_image_path', $path);
            })
            ->exists();

        return $used ? false : PublicUpload::remove($path);
    }

    private function audit($action, $details)
    {
        if (!\Schema::hasTable('admin_activity_logs')) return;
        DB::table('admin_activity_logs')->insert([
            'admin_id' => session('admin_id'),
            'admin_name' => session('admin_name'),
            'action' => $action,
            'method' => request()->method(),
            'path' => request()->path(),
            'ip_hash' => hash_hmac('sha256', (string)request()->ip(), config('app.key')),
            'details' => json_encode($details),
            'created_at' => now(),
        ]);
    }
}
