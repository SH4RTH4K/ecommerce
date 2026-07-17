<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class StockAlertService
{
    public function process($productId)
    {
        $product = DB::table('product')->where('id', $productId)->first();
        if (!$product || $product->product_condition !== 'In Stock') return 0;
        $alerts = DB::table('stock_alerts')->where('product_id', $productId)->where('status', 'waiting')->get();
        foreach ($alerts as $alert) {
            $emailStatus = 'not_configured';
            $error = null;
            if (config('mail.username')) {
                try {
                    Mail::raw($product->product_name.' is available again at '.config('app.name').'. Current price: ৳'.number_format($product->selling_price).'. View it here: '.url('/product-details/'.$product->id), function ($mail) use ($alert, $product) {
                        $mail->to($alert->email)->subject('Back in stock: '.$product->product_name);
                    });
                    $emailStatus = 'sent';
                } catch (\Throwable $e) {
                    $emailStatus = 'failed';
                    $error = substr($e->getMessage(), 0, 1000);
                }
            }
            DB::table('stock_alerts')->where('id', $alert->id)->update(['status'=>'notified','notified_at'=>now(),'email_status'=>$emailStatus,'email_error'=>$error,'updated_at'=>now()]);
            if ($alert->user_id) DB::table('store_notifications')->insert(['recipient_type'=>'customer','user_id'=>$alert->user_id,'email'=>$alert->email,'title'=>'Back in stock: '.$product->product_name,'message'=>'This product is available again for ৳'.number_format($product->selling_price).'.','action_url'=>url('/product-details/'.$product->id),'email_status'=>$emailStatus,'email_error'=>$error,'created_at'=>now(),'updated_at'=>now()]);
        }
        return $alerts->count();
    }
}
