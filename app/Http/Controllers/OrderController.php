<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class OrderController extends Controller {
 public function __construct(){ $this->middleware('auth'); }
 public function index(){ $orders=DB::table('orders')->where('user_id',auth()->id())->latest()->paginate(10);return view('account.orders',compact('orders')); }
 public function show($id){ $order=DB::table('orders')->where('id',$id)->where('user_id',auth()->id())->first();abort_unless($order,404);$items=DB::table('order_items')->where('order_id',$id)->get();return view('account.order-details',compact('order','items')); }
}
