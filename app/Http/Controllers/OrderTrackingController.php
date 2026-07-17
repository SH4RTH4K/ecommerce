<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderTrackingController extends Controller
{
    public function form(){ return view('front-end.pages.track-order'); }

    public function track(Request $request)
    {
        $this->validate($request,['order_number'=>'required|max:40','phone'=>'required|max:30']);
        $order=DB::table('orders')->where('order_number',strtoupper(trim($request->order_number)))->first();
        $given=preg_replace('/\D+/','',(string)$request->phone); $stored=$order?preg_replace('/\D+/','',(string)$order->phone):'';
        if(!$order || !$given || !hash_equals($stored,$given)) return redirect()->back()->withInput()->withErrors(['order_number'=>'Order number and phone do not match our records.']);
        $authorized=(array)$request->session()->get('tracked_orders',[]); $authorized[$order->id]=true; $request->session()->put('tracked_orders',$authorized);
        $items=DB::table('order_items')->where('order_id',$order->id)->get();
        return view('front-end.pages.tracked-order',compact('order','items'));
    }

    public function invoice(Request $request,$id)
    {
        $order=DB::table('orders')->where('id',$id)->first(); abort_unless($order,404);
        $tracked=(array)$request->session()->get('tracked_orders',[]);
        $allowed=$request->session()->has('admin_id') || !empty($tracked[$id]) || (int)$request->session()->get('last_order_id')===(int)$id || (auth()->check() && (int)$order->user_id===(int)auth()->id());
        abort_unless($allowed,403);
        $items=DB::table('order_items')->where('order_id',$id)->get();
        return view('front-end.pages.invoice',compact('order','items'));
    }
}
