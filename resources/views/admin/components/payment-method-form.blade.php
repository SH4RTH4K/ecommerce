@php
 $m=$method;
 $pv=function($key,$default='')use($m){return old($key,$m?$m->$key:$default);};
 $on=function($key,$default=false)use($m){return old($key,$m?(bool)$m->$key:$default);};
 $rules=(array)($m?$m->availability_rules:[]);
@endphp
<style>
.pm-wizard-nav{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:16px 0}.pm-step-button{padding:11px 8px;border:1px solid #d7e3e9;border-radius:9px;color:#607580;background:#f7fafb;text-align:left;font-weight:800}.pm-step-button span{display:inline-grid;place-items:center;width:23px;height:23px;margin-right:6px;border-radius:50%;color:#fff;background:#8aa0ac}.pm-step-button.is-active{border-color:#1682a8;color:#135f7b;background:#eaf7fb}.pm-step-button.is-active span{background:#1682a8}.pm-method-choice{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}.pm-method-choice label{position:relative;padding:13px 10px;border:1px solid #d7e3e9;border-radius:9px;background:#fff;cursor:pointer}.pm-method-choice input{position:absolute;opacity:0}.pm-method-choice label:has(input:checked){border-color:#1682a8;box-shadow:0 0 0 3px rgba(22,130,168,.1);background:#eef9fc}.pm-method-choice strong,.pm-method-choice small{display:block}.pm-method-choice small{margin-top:3px;color:#758791;font-weight:400}.pm-wizard-actions{display:flex;justify-content:space-between;gap:10px;padding-top:16px;margin-top:17px;border-top:1px solid #e1e9ed}.pm-conditional[hidden],.pm-wizard-step[hidden]{display:none!important}.pm-context{padding:10px 12px;margin:10px 0 15px;border-left:4px solid #1682a8;border-radius:5px;color:#496675;background:#f0f8fb}.pm-advanced{padding:12px;border:1px solid #dce6eb;border-radius:9px;background:#f8fafb}.pm-advanced summary{cursor:pointer;color:#176f91;font-weight:800}@media(max-width:800px){.pm-wizard-nav,.pm-method-choice{grid-template-columns:1fr 1fr}}@media(max-width:480px){.pm-wizard-nav,.pm-method-choice{grid-template-columns:1fr}.pm-step-button{display:none}.pm-step-button.is-active{display:block}}
</style>
<form class="pm-form pm-adaptive-form" method="post" enctype="multipart/form-data" action="{{ route('payment-methods.store') }}">
 {{ csrf_field() }}
 @if($m)<input type="hidden" name="id" value="{{ $m->id }}">@endif
 <nav class="pm-wizard-nav" aria-label="Payment method setup steps">
  <button type="button" class="pm-step-button is-active" data-go-step="1"><span>1</span> Choose method</button>
  <button type="button" class="pm-step-button" data-go-step="2"><span>2</span> Method setup</button>
  <button type="button" class="pm-step-button" data-go-step="3"><span>3</span> Checkout rules</button>
  <button type="button" class="pm-step-button" data-go-step="4"><span>4</span> Review &amp; activate</button>
 </nav>

 <section class="pm-wizard-step" data-step="1">
  <div class="pm-section"><h3>What kind of payment is this?</h3><p>Select one option. The next step will show only the settings that method needs.</p>
   <div class="pm-method-choice">
    @foreach([
     'cash_on_delivery'=>['Cash on Delivery','Collect after delivery'],
     'mobile_financial_service'=>['Mobile Financial Service','bKash, Nagad, Rocket, Upay'],
     'bank_transfer'=>['Bank Transfer','Customer transfers manually'],
     'payment_gateway'=>['Online Gateway','SSLCOMMERZ or another gateway'],
     'card_payment'=>['Card Payment','Card gateway and optional EMI'],
     'qr_payment'=>['QR Payment','Bangla QR or provider QR'],
     'manual_payment'=>['Other Manual','Reference or proof based'],
     'custom'=>['Custom Method','A business-specific option']
    ] as $v=>$info)
     <label><input type="radio" name="method_type" value="{{ $v }}" {{ $pv('method_type','manual_payment')===$v?'checked':'' }}><strong>{{ $info[0] }}</strong><small>{{ $info[1] }}</small></label>
    @endforeach
   </div>
  </div>
  <div class="pm-section"><h3>Basic identity</h3><p>These fields are common to every payment method.</p><div class="pm-grid">
   <div class="pm-field"><label>Internal name *</label><input name="name" maxlength="150" required value="{{ $pv('name') }}" placeholder="Example: bKash Manual"></div>
   <div class="pm-field"><label>Internal code *</label><input name="code" pattern="[a-z0-9_-]+" maxlength="50" required value="{{ $pv('code') }}" placeholder="bkash_manual"><small class="pm-help">Lowercase letters, numbers, underscore or hyphen. Do not change after orders exist.</small></div>
   <div class="pm-field"><label>Customer checkout label *</label><input name="customer_label" maxlength="150" required value="{{ $pv('customer_label',$pv('name')) }}" placeholder="Pay with bKash"></div>
   <div class="pm-field"><label>Provider</label><input name="provider" list="payment-providers" maxlength="100" value="{{ $pv('provider') }}" placeholder="Select or enter provider"><datalist id="payment-providers"><option>bKash</option><option>Nagad</option><option>Rocket</option><option>Upay</option><option>SSLCOMMERZ</option><option>TakaPay</option><option>Bank</option><option>Cash</option></datalist></div>
   <div class="pm-field"><label>Icon class</label><input name="icon" value="{{ $pv('icon') }}" placeholder="icon-credit-card"></div>
   <div class="pm-field"><label>Provider logo</label><input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"><small class="pm-help">PNG/JPG/WebP up to 1MB.</small></div>
   <div class="pm-field full"><label>Internal description</label><textarea name="description" maxlength="2000">{{ $pv('description') }}</textarea></div>
  </div></div>
 </section>

 <section class="pm-wizard-step" data-step="2" hidden>
  <div class="pm-context" data-method-guidance></div>
  <div class="pm-section"><h3>How will this method process payment?</h3><div class="pm-grid">
   <div class="pm-field"><label>Integration mode *</label><select name="integration_mode">@foreach(['offline'=>'Offline collection','manual'=>'Manual customer payment','hosted_checkout'=>'Hosted checkout page','redirect'=>'Provider redirect','api'=>'Direct API'] as $v=>$l)<option value="{{ $v }}" {{ $pv('integration_mode','offline')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
   <div class="pm-field pm-conditional" data-methods="mobile_financial_service payment_gateway card_payment qr_payment" data-always-submit><label>Environment *</label><select name="environment">@foreach(['sandbox'=>'Sandbox / Test','live'=>'Live'] as $v=>$l)<option value="{{ $v }}" {{ $pv('environment','live')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
  </div></div>

  <div class="pm-section pm-conditional" data-methods="mobile_financial_service"><h3>MFS merchant information</h3><p>For manual bKash, Nagad, Rocket or Upay collection.</p><div class="pm-grid">
   <div class="pm-field"><label>Merchant/payment number *</label><input name="merchant_number" value="{{ $pv('merchant_number') }}" placeholder="+8801XXXXXXXXX"></div>
   <div class="pm-field"><label>Account type</label><select name="account_type"><option value="">Select account type</option>@foreach(['merchant'=>'Merchant','personal'=>'Personal (not recommended)','agent'=>'Agent','other'=>'Other'] as $v=>$l)<option value="{{ $v }}" {{ $pv('account_type')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
  </div></div>

  <div class="pm-section pm-conditional" data-methods="bank_transfer"><h3>Bank transfer information</h3><p>Include the bank, account name and number, branch, and routing number where applicable.</p><div class="pm-grid"><div class="pm-field full"><label>Bank account details *</label><textarea name="bank_details" maxlength="4000" placeholder="Bank name&#10;Account name&#10;Account number&#10;Branch and routing number">{{ $pv('bank_details') }}</textarea></div></div></div>

  <div class="pm-section pm-conditional" data-methods="qr_payment"><h3>QR payment information</h3><div class="pm-grid">
   <div class="pm-field"><label>QR type *</label><select name="qr_type"><option value="">Select QR type</option>@foreach(['bangla_qr'=>'Bangla QR','static'=>'Static provider QR','provider_generated'=>'Provider generated QR','gateway_redirect'=>'Gateway redirect','custom'=>'Custom QR'] as $v=>$l)<option value="{{ $v }}" {{ $pv('qr_type')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
   <div class="pm-field"><label>QR presentation</label><div class="pm-context">Upload the approved merchant QR using <strong>Provider logo</strong> in Step 1. It will be displayed as this method’s checkout image.</div></div>
  </div></div>

  <div class="pm-section pm-conditional" data-methods="payment_gateway card_payment"><h3>Gateway connection</h3><p>Start in Sandbox. Live activation requires an approved merchant account and verified provider adapter.</p><div class="pm-grid">
   <div class="pm-field"><label>Credential name</label><select name="credential_key">@foreach(['merchant_id','store_id','api_key','api_secret','username','password','client_id','client_secret','app_key','app_secret','public_key','private_key_reference','webhook_secret'] as $k)<option value="{{ $k }}">{{ ucwords(str_replace('_',' ',$k)) }}</option>@endforeach</select></div>
   <div class="pm-field"><label>New/replacement secret</label><input type="password" name="credential_value" autocomplete="new-password"><small class="pm-help">{{ $m&&$m->credentials?'Credential configured; leave blank to keep it':'No database credential configured' }}</small></div>
   @foreach(['success_callback_url'=>'Success callback','failure_callback_url'=>'Failure callback','cancel_callback_url'=>'Cancellation callback','webhook_url'=>'IPN / webhook URL'] as $k=>$l)<div class="pm-field"><label>{{ $l }}</label><input name="{{ $k }}" maxlength="500" value="{{ $pv($k) }}" placeholder="/payment/callback or https://..."></div>@endforeach
  </div></div>

  <div class="pm-section pm-conditional" data-methods="mobile_financial_service bank_transfer qr_payment manual_payment custom" data-always-submit><h3>Manual verification requirements</h3><div class="pm-grid"><div class="pm-field"><label>Verification</label><select name="payment_verification_mode"><option value="manual" {{ $pv('payment_verification_mode')==='manual'?'selected':'' }}>Administrator verification required</option><option value="automatic" {{ $pv('payment_verification_mode','automatic')==='automatic'?'selected':'' }}>Automatic through verified integration</option></select></div></div><div class="pm-checks">
   @foreach(['require_transaction_id'=>'Require Transaction ID/reference','require_sender_number'=>'Require sender number','require_payment_screenshot'=>'Require payment proof'] as $k=>$l)<label><input type="hidden" name="{{ $k }}" value="0"><input type="checkbox" name="{{ $k }}" value="1" {{ $on($k)?'checked':'' }}> {{ $l }}</label>@endforeach
  </div></div>
 </section>

 <section class="pm-wizard-step" data-step="3" hidden>
  <div class="pm-section"><h3>Customer instructions</h3><p>Explain exactly what the customer should do. Never request a PIN, OTP, CVV, or password.</p><div class="pm-grid">
   <div class="pm-field full"><label>Short checkout instruction</label><textarea name="short_instruction" maxlength="500">{{ $pv('short_instruction',$pv('instructions')) }}</textarea></div>
   <div class="pm-field full"><label>Detailed instruction</label><textarea name="detailed_instruction" maxlength="4000">{{ $pv('detailed_instruction') }}</textarea></div>
  </div></div>
  <div class="pm-section"><h3>Order amount and customer charge</h3><div class="pm-grid">
   <div class="pm-field"><label>Minimum order (৳)</label><input type="number" min="0" step="0.01" name="minimum_order_amount" value="{{ $pv('minimum_order_amount') }}"></div>
   <div class="pm-field"><label>Maximum order (৳)</label><input type="number" min="0" step="0.01" name="maximum_order_amount" value="{{ $pv('maximum_order_amount') }}"></div>
   <div class="pm-field"><label>Charge type</label><select name="charge_type">@foreach(['fixed'=>'Fixed amount','percentage'=>'Percentage','fixed_plus_percentage'=>'Fixed plus percentage'] as $v=>$l)<option value="{{ $v }}" {{ $pv('charge_type','fixed')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
   <div class="pm-field"><label>Charge value</label><input type="number" min="0" step="0.01" name="charge_value" value="{{ $pv('charge_value',0) }}"></div>
   <div class="pm-field"><label>Charge payer</label><select name="charge_payer">@foreach(['customer'=>'Customer','merchant'=>'Merchant','included'=>'Included in price'] as $v=>$l)<option value="{{ $v }}" {{ $pv('charge_payer','customer')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
   <div class="pm-field"><label>Minimum charge</label><input type="number" min="0" step="0.01" name="minimum_charge" value="{{ $pv('minimum_charge') }}"></div>
   <div class="pm-field"><label>Maximum charge</label><input type="number" min="0" step="0.01" name="maximum_charge" value="{{ $pv('maximum_charge') }}"></div>
   <div class="pm-field"><label>Free above amount</label><input type="number" min="0" step="0.01" name="free_above_amount" value="{{ $pv('free_above_amount') }}"></div>
  </div><div class="pm-checks"><label><input type="hidden" name="charge_enabled" value="0"><input type="checkbox" name="charge_enabled" value="1" {{ $on('charge_enabled')?'checked':'' }}> Apply transaction charge</label><label><input type="hidden" name="display_charge_at_checkout" value="0"><input type="checkbox" name="display_charge_at_checkout" value="1" {{ $on('display_charge_at_checkout',true)?'checked':'' }}> Show charge before confirmation</label></div></div>
  <div class="pm-section"><h3>Availability</h3><div class="pm-grid">
   <div class="pm-field"><label>Available from</label><input type="datetime-local" name="available_from" value="{{ $m&&$m->available_from?$m->available_from->format('Y-m-d\TH:i'):'' }}"></div>
   <div class="pm-field"><label>Available until</label><input type="datetime-local" name="available_until" value="{{ $m&&$m->available_until?$m->available_until->format('Y-m-d\TH:i'):'' }}"></div>
   <div class="pm-field"><label>Allowed delivery zones</label><select name="allowed_shipping_zones[]" multiple>@foreach($zones as $z)<option value="{{ $z->id }}" {{ in_array($z->id,(array)($rules['allowed_shipping_zones']??[]))?'selected':'' }}>{{ $z->name }}</option>@endforeach</select><small class="pm-help">Leave empty to allow every active zone.</small></div>
   <div class="pm-field"><label>Blocked delivery zones</label><select name="blocked_shipping_zones[]" multiple>@foreach($zones as $z)<option value="{{ $z->id }}" {{ in_array($z->id,(array)($rules['blocked_shipping_zones']??[]))?'selected':'' }}>{{ $z->name }}</option>@endforeach</select></div>
  </div></div>
 </section>

 <section class="pm-wizard-step" data-step="4" hidden>
  <div class="pm-section"><h3>Review and activation</h3><p>New methods should remain disabled until their checkout instructions and payment flow are tested.</p><div class="pm-grid"><div class="pm-field"><label>Display order</label><input type="number" min="0" name="display_order" value="{{ $pv('display_order',0) }}" required></div></div>
   <div class="pm-checks">
    @foreach(['is_active'=>'Enabled','show_at_checkout'=>'Show at checkout','allow_sandbox_at_checkout'=>'Allow Sandbox at customer checkout','supports_emi'=>'Supports EMI'] as $k=>$l)<label class="{{ $k==='allow_sandbox_at_checkout'?'pm-conditional':'' }}" {!! $k==='allow_sandbox_at_checkout'?'data-methods="mobile_financial_service payment_gateway card_payment qr_payment"':'' !!}><input type="hidden" name="{{ $k }}" value="0"><input type="checkbox" name="{{ $k }}" value="1" {{ $on($k,$k==='show_at_checkout')?'checked':'' }}> {{ $l }}</label>@endforeach
   </div>
   <div class="pm-alert" data-review-summary></div>
  </div>
 </section>

 <div class="pm-wizard-actions"><button class="btn" type="button" data-previous-step hidden><i class="icon-arrow-left"></i> Previous</button><span></span><button class="btn btn-primary" type="button" data-next-step>Continue <i class="icon-arrow-right"></i></button><button class="btn btn-success" type="submit" data-save-method hidden><i class="icon-save"></i> {{ $m?'Save this payment method':'Create payment method' }}</button></div>
</form>
<script>
(function(form){
 if(!form)return;
 var step=1,typeInputs=form.querySelectorAll('[name="method_type"]'),mode=form.querySelector('[name="integration_mode"]');
 var guidance={cash_on_delivery:'Collect payment after delivery. Gateway credentials and manual payment proof are not needed.',mobile_financial_service:'Choose Manual for customer-submitted bKash/Nagad/Rocket/Upay payments, or API only when a verified provider adapter exists.',bank_transfer:'Show the receiving bank account and require a reference or proof for administrator verification.',payment_gateway:'Use Sandbox until session creation, callbacks, IPN/webhook validation, amount validation and idempotency are implemented.',card_payment:'Card data must stay on the verified gateway. Do not collect or store card numbers, CVV, PIN or OTP.',qr_payment:'Use a legitimate merchant QR. Customers should verify the merchant identity before authorizing payment.',manual_payment:'Configure only the reference and proof fields your business actually needs.',custom:'Use this only when the standard payment types do not describe the business flow.'};
 var defaults={cash_on_delivery:'offline',mobile_financial_service:'manual',bank_transfer:'manual',payment_gateway:'hosted_checkout',card_payment:'hosted_checkout',qr_payment:'manual',manual_payment:'manual',custom:'offline'};
 function type(){var checked=form.querySelector('[name="method_type"]:checked');return checked?checked.value:'manual_payment'}
 function refreshConditional(){var current=type();form.querySelectorAll('[data-methods]').forEach(function(box){var show=box.dataset.methods.split(' ').indexOf(current)>-1;box.hidden=!show;if(!box.hasAttribute('data-always-submit'))box.querySelectorAll('input,select,textarea').forEach(function(field){field.disabled=!show})});var merchant=form.querySelector('[name="merchant_number"]'),bank=form.querySelector('[name="bank_details"]'),qr=form.querySelector('[name="qr_type"]');if(merchant)merchant.required=current==='mobile_financial_service'&&mode.value==='manual';if(bank)bank.required=current==='bank_transfer';if(qr)qr.required=current==='qr_payment';var note=form.querySelector('[data-method-guidance]');if(note)note.textContent=guidance[current];}
 function showStep(number){step=Math.max(1,Math.min(4,number));form.querySelectorAll('[data-step]').forEach(function(panel){panel.hidden=Number(panel.dataset.step)!==step});form.querySelectorAll('[data-go-step]').forEach(function(button){button.classList.toggle('is-active',Number(button.dataset.goStep)===step)});form.querySelector('[data-previous-step]').hidden=step===1;form.querySelector('[data-next-step]').hidden=step===4;form.querySelector('[data-save-method]').hidden=step!==4;if(step===4){var label=form.querySelector('[name="customer_label"]').value||form.querySelector('[name="name"]').value||'This method';var env=form.querySelector('[name="environment"]');form.querySelector('[data-review-summary]').textContent=label+' will be saved as '+(form.querySelector('[name="is_active"]:checked')?'Enabled':'Disabled')+(env&&!env.disabled?' in '+env.value.toUpperCase()+' mode':'')+'.';}form.scrollIntoView({behavior:'smooth',block:'start'})}
 function currentStepIsValid(){var fields=form.querySelector('[data-step="'+step+'"]').querySelectorAll('input,select,textarea');for(var i=0;i<fields.length;i++){if(!fields[i].disabled&&!fields[i].checkValidity()){fields[i].reportValidity();return false}}return true}
 typeInputs.forEach(function(input){input.addEventListener('change',function(){mode.value=defaults[type()];refreshConditional()})});
 mode.addEventListener('change',refreshConditional);
 form.querySelectorAll('[data-go-step]').forEach(function(button){button.addEventListener('click',function(){showStep(Number(button.dataset.goStep))})});
 form.querySelector('[data-next-step]').addEventListener('click',function(){if(!currentStepIsValid())return;showStep(step+1)});
 form.querySelector('[data-previous-step]').addEventListener('click',function(){showStep(step-1)});
 refreshConditional();showStep({{ $errors->any()?1:1 }});
})(document.currentScript.previousElementSibling);
</script>
