<?php

namespace App\Services;

use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CartRecoveryService
{
    public function capture(Request $request)
    {
        $cart = (array) $request->session()->get('cart', []);
        $userId = auth()->id();
        $email = $userId ? auth()->user()->email : $request->session()->get('cart_email');
        $id = $request->session()->get('abandoned_cart_id');
        if (!$cart) {
            if ($id) DB::table('abandoned_carts')->where('id', $id)->update(['status'=>'converted','updated_at'=>now()]);
            return;
        }
        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
        $subtotal = collect($cart)->sum(function ($qty, $productId) use ($products) {
            return $products->has($productId) ? $products[$productId]->selling_price * $qty : 0;
        });
        $data = ['user_id'=>$userId,'email'=>$email,'items'=>json_encode($cart),'subtotal'=>$subtotal,'status'=>'active','last_activity_at'=>now(),'updated_at'=>now()];
        if ($id && DB::table('abandoned_carts')->where('id', $id)->exists()) {
            DB::table('abandoned_carts')->where('id', $id)->update($data);
        } else {
            $data['recovery_token'] = bin2hex(random_bytes(24));
            $data['created_at'] = now();
            $id = DB::table('abandoned_carts')->insertGetId($data);
            $request->session()->put('abandoned_cart_id', $id);
        }
    }

    public function remind($id)
    {
        $cart = DB::table('abandoned_carts')->where('id', $id)->where('status', 'active')->first();
        if (!$cart || !$cart->email) return 0;
        $url = url('/recover-cart/'.$cart->recovery_token);
        $status = 'not_configured';
        $error = null;
        if (config('mail.username')) {
            try {
                Mail::raw('You left items worth ৳'.number_format($cart->subtotal).' in your '.config('app.name').' cart. Restore your cart: '.$url, function ($mail) use ($cart) {
                    $mail->to($cart->email)->subject('Your '.config('app.name').' cart is waiting');
                });
                $status = 'sent';
            } catch (\Throwable $e) {
                $status = 'failed';
                $error = substr($e->getMessage(), 0, 1000);
            }
        }
        DB::table('abandoned_carts')->where('id', $id)->update(['status'=>'reminded','reminded_at'=>now(),'email_status'=>$status,'email_error'=>$error,'updated_at'=>now()]);
        if ($cart->user_id) DB::table('store_notifications')->insert(['recipient_type'=>'customer','user_id'=>$cart->user_id,'email'=>$cart->email,'title'=>'Your shopping cart is waiting','message'=>'You still have items worth ৳'.number_format($cart->subtotal).' in your cart.','action_url'=>$url,'email_status'=>$status,'email_error'=>$error,'created_at'=>now(),'updated_at'=>now()]);
        return 1;
    }
}
