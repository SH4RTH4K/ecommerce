<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function __construct(){ $this->middleware('auth'); }
    public function index(){ $notifications=DB::table('store_notifications')->where('recipient_type','customer')->where('user_id',auth()->id())->latest()->paginate(20);return view('account.notifications',compact('notifications')); }
    public function read($id){ $notification=DB::table('store_notifications')->where('id',$id)->where('recipient_type','customer')->where('user_id',auth()->id())->first();abort_unless($notification,404);DB::table('store_notifications')->where('id',$id)->update(['read_at'=>now(),'updated_at'=>now()]);return $notification->action_url?redirect($notification->action_url):redirect()->route('account.notifications'); }
    public function readAll(){ DB::table('store_notifications')->where('recipient_type','customer')->where('user_id',auth()->id())->whereNull('read_at')->update(['read_at'=>now(),'updated_at'=>now()]);return redirect()->back(); }
}
