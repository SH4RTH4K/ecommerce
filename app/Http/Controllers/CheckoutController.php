<?php
namespace App\Http\Controllers;

use App\Product;
use App\DeliveryZone;
use App\Coupon;
use App\PaymentMethod;
use App\EmiPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use App\Services\OrderNotifier;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $items=$this->items($request);
        if($items->isEmpty()) return redirect()->route('cart.index')->with('error','Add a product before checkout.');
        $zones=DeliveryZone::where('is_active',1)->orderBy('display_order')->get();
        if($zones->isEmpty()) return redirect()->route('cart.index')->with('error','Delivery is temporarily unavailable. Please contact support.');
        $subtotal=$items->sum('subtotal');
        $paymentMethods=PaymentMethod::with('emiPlans')->where('is_active',1)->orderBy('display_order')->get();
        if($paymentMethods->isEmpty()) return redirect()->route('cart.index')->with('error','Payment is temporarily unavailable. Please contact support.');
        return view('front-end.pages.checkout',compact('items','zones','subtotal','paymentMethods'));
    }

    public function store(Request $request)
    {
        $this->validate($request,['customer_name'=>'required|max:120','phone'=>'required|max:30','address'=>'required|max:1000','email'=>'nullable|email|max:150','payment_method_id'=>'required|integer|exists:payment_methods,id','emi_plan_id'=>'nullable|integer|exists:emi_plans,id','delivery_zone_id'=>'required|integer|exists:delivery_zones,id']);
        $items=$this->items($request);
        if($items->isEmpty()) return redirect()->route('cart.index');
        $subtotal=$items->sum('subtotal');
        $zone=DeliveryZone::where('is_active',1)->findOrFail($request->delivery_zone_id);
        $deliveryCharge=$zone->chargeFor($subtotal);
        $coupon=null; $discount=0;
        if(trim((string)$request->coupon_code)!=='') {
            $coupon=Coupon::where('code',strtoupper(trim($request->coupon_code)))->first();
            if(!$coupon) return redirect()->back()->withInput()->withErrors(['coupon_code'=>'Coupon code is invalid.']);
            if($error=$coupon->availabilityError($subtotal)) return redirect()->back()->withInput()->withErrors(['coupon_code'=>$error]);
            $discount=$coupon->discountFor($subtotal);
        }
        $paymentMethod=PaymentMethod::where('is_active',1)->findOrFail($request->payment_method_id);$emiPlan=null;$emiMonthly=null;
        $orderTotal=$subtotal-$discount+$deliveryCharge;
        if($paymentMethod->supports_emi){if(!$request->emi_plan_id)return redirect()->back()->withInput()->withErrors(['emi_plan_id'=>'Select an EMI plan.']);$emiPlan=EmiPlan::where('id',$request->emi_plan_id)->where('payment_method_id',$paymentMethod->id)->where('is_active',1)->first();if(!$emiPlan)return redirect()->back()->withInput()->withErrors(['emi_plan_id'=>'Selected EMI plan is unavailable.']);if($orderTotal<$emiPlan->minimum_order)return redirect()->back()->withInput()->withErrors(['emi_plan_id'=>'This EMI plan requires a minimum total of ৳'.number_format($emiPlan->minimum_order).'.']);$emiMonthly=$emiPlan->monthlyAmount($orderTotal);}

        try {
            $id=DB::transaction(function() use($request,$items,$subtotal,$zone,$deliveryCharge,$coupon,$discount,$paymentMethod,$emiPlan,$emiMonthly) {
                foreach($items as $item) {
                    $stock=DB::table('product')->where('id',$item['product']->id)->lockForUpdate()->first();
                    if($stock->product_condition !== 'In Stock') throw new RuntimeException($stock->product_name.' is currently out of stock.');
                    if($stock->stock_tracking && $stock->stock_quantity < $item['quantity']) throw new RuntimeException($stock->product_name.' has only '.$stock->stock_quantity.' unit(s) available.');
                    if($stock->stock_tracking) {
                        $location=DB::table('inventory_locations')->where('is_default',1)->where('is_active',1)->first();
                        if(!$location) throw new RuntimeException('Online fulfillment is temporarily unavailable.');
                        $locationStock=DB::table('product_location_stock')->where('location_id',$location->id)->where('product_id',$stock->id)->lockForUpdate()->first();
                        if(!$locationStock || $locationStock->quantity < $item['quantity']) throw new RuntimeException($stock->product_name.' has insufficient stock in the online warehouse.');
                    }
                }
                if($coupon) {
                    $lockedCoupon=Coupon::where('id',$coupon->id)->lockForUpdate()->first();
                    if($error=$lockedCoupon->availabilityError($subtotal)) throw new RuntimeException($error);
                }
                $id=DB::table('orders')->insertGetId(['order_number'=>'LBD-'.date('ymd').'-'.strtoupper(str_random(6)),'user_id'=>auth()->id(),'customer_name'=>$request->customer_name,'phone'=>$request->phone,'email'=>$request->email,'address'=>$request->address,'city'=>$request->city,'delivery_zone_id'=>$zone->id,'delivery_zone_name'=>$zone->name,'coupon_id'=>$coupon?$coupon->id:null,'coupon_code'=>$coupon?$coupon->code:null,'notes'=>$request->notes,'subtotal'=>$subtotal,'discount'=>$discount,'delivery_charge'=>$deliveryCharge,'total'=>$subtotal-$discount+$deliveryCharge,'payment_method'=>$paymentMethod->code,'payment_method_id'=>$paymentMethod->id,'emi_plan_id'=>$emiPlan?$emiPlan->id:null,'emi_months'=>$emiPlan?$emiPlan->months:null,'emi_monthly_amount'=>$emiMonthly,'status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
                if($coupon) DB::table('coupons')->where('id',$coupon->id)->increment('used_count');
                foreach($items as $item) {
                    $product=$item['product'];
                    DB::table('order_items')->insert(['order_id'=>$id,'product_id'=>$product->id,'product_name'=>$product->product_name,'sku'=>$product->sku,'offer_price'=>$product->selling_price,'unit_purchase_price'=>$product->purchase_price,'quantity'=>$item['quantity'],'subtotal'=>$item['subtotal'],'profit'=>($product->selling_price-$product->purchase_price)*$item['quantity'],'created_at'=>now(),'updated_at'=>now()]);
                    if($product->stock_tracking) {
                        DB::table('product_location_stock')->where('location_id',$location->id)->where('product_id',$product->id)->decrement('quantity',$item['quantity']);
                        DB::table('product')->where('id',$product->id)->decrement('stock_quantity',$item['quantity']);
                        DB::table('product')->where('id',$product->id)->where('stock_quantity',0)->update(['product_condition'=>'Out Of Stock']);
                    }
                }
                return $id;
            });
        } catch (RuntimeException $exception) {
            return redirect()->route('cart.index')->with('error',$exception->getMessage());
        }

        $placedOrder=DB::table('orders')->where('id',$id)->first();
        $notifier=app(OrderNotifier::class);
        $notifier->admin($placedOrder,'New order '.$placedOrder->order_number,$placedOrder->customer_name.' placed an order worth ৳'.number_format($placedOrder->total).'.');
        $notifier->customer($placedOrder,'Order received: '.$placedOrder->order_number,'Thank you. We received your order and will contact you before delivery.');
        app(\App\Services\WebhookService::class)->dispatch('order.created',['order_id'=>$placedOrder->id,'order_number'=>$placedOrder->order_number,'total'=>(float)$placedOrder->total,'status'=>$placedOrder->status]);

        $request->session()->forget('cart');
        app(\App\Services\CartRecoveryService::class)->capture($request);
        $request->session()->put('last_order_id',$id);
        return redirect()->route('checkout.success',$id);
    }

    public function success(Request $request,$id)
    {
        $order=DB::table('orders')->where('id',$id)->first();
        abort_unless($order,404);
        $allowed=(int)$request->session()->get('last_order_id')===(int)$id || (auth()->check() && (int)$order->user_id===(int)auth()->id());
        abort_unless($allowed,404);
        return view('front-end.pages.order-success',compact('order'));
    }

    public function checkCoupon(Request $request)
    {
        $items=$this->items($request); $subtotal=$items->sum('subtotal');
        if($items->isEmpty()) return response()->json(['valid'=>false,'message'=>'Your cart is empty.'],422);
        $coupon=Coupon::where('code',strtoupper(trim((string)$request->coupon_code)))->first();
        if(!$coupon) return response()->json(['valid'=>false,'message'=>'Coupon code is invalid.'],422);
        if($error=$coupon->availabilityError($subtotal)) return response()->json(['valid'=>false,'message'=>$error],422);
        return response()->json(['valid'=>true,'code'=>$coupon->code,'discount'=>$coupon->discountFor($subtotal),'message'=>'Coupon applied successfully.']);
    }

    private function items(Request $request)
    {
        $cart=(array)$request->session()->get('cart',[]);
        $products=Product::whereIn('id',array_keys($cart))->get()->keyBy('id');
        return collect($cart)->map(function($quantity,$id) use($products) {
            if(!$products->has($id)) return null;
            $product=$products[$id];
            return ['product'=>$product,'quantity'=>$quantity,'subtotal'=>$product->selling_price*$quantity];
        })->filter();
    }
}
