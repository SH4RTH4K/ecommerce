<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderNotifier
{
    public function admin($order, $title, $message)
    {
        return $this->create('admin',null,$order,$title,$message,url('/manage-orders/'.$order->id),config('mail.username'));
    }

    public function customer($order, $title, $message)
    {
        return $this->create('customer',$order->user_id,$order,$title,$message,$order->user_id?url('/my-orders/'.$order->id):null,$order->email);
    }

    private function create($type,$userId,$order,$title,$message,$url,$email)
    {
        $id=DB::table('store_notifications')->insertGetId(['recipient_type'=>$type,'user_id'=>$userId,'order_id'=>$order->id,'email'=>$email,'title'=>$title,'message'=>$message,'action_url'=>$url,'email_status'=>'not_configured','created_at'=>now(),'updated_at'=>now()]);
        if(!$email || !config('mail.username')) return $id;
        try {
            Mail::raw($message,function($mail) use($email,$title){$mail->to($email)->subject($title);});
            DB::table('store_notifications')->where('id',$id)->update(['email_status'=>'sent','updated_at'=>now()]);
        } catch (\Throwable $e) {
            DB::table('store_notifications')->where('id',$id)->update(['email_status'=>'failed','email_error'=>substr($e->getMessage(),0,1000),'updated_at'=>now()]);
        }
        return $id;
    }
}
