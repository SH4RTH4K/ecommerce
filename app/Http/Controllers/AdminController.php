<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        if($request->session()->has('admin_id')) return redirect()->route('admin.dashboard');
        return view('admin.admin-pages.admin-login');
    }

    public function login(Request $request)
    {
        $this->validate($request,['username'=>'required|max:100','password'=>'required|max:255']);
        $admin=DB::table('tbl_admin')->where('admin_name',$request->username)->first();
        if($admin && isset($admin->is_active) && !$admin->is_active) {$this->securityEvent($request,'warning','Disabled administrator login attempt',$request->username);return redirect('/login?account=admin')->withInput($request->only('username'))->with('exception','This administrator account is disabled.');}
        $legacy=$admin && strlen($admin->admin_password)===32;
        $valid=$admin && ($legacy ? hash_equals((string)$admin->admin_password,md5($request->password)) : Hash::check($request->password,$admin->admin_password));
        if(!$valid) {$this->securityEvent($request,'warning','Failed administrator login',$request->username);return redirect('/login?account=admin')->withInput($request->only('username'))->with('exception','Username or password is invalid.');}
        if($legacy) DB::table('tbl_admin')->where('admin_id',$admin->admin_id)->update(['admin_password'=>Hash::make($request->password)]);
        $request->session()->regenerate(true);
        $request->session()->put(['admin_id'=>$admin->admin_id,'admin_name'=>$admin->admin_name]);
        $this->securityEvent($request,'info','Successful administrator login',$admin->admin_name);
        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_id','admin_name']);
        $request->session()->regenerate();
        return redirect()->route('admin.login')->with('message','You have signed out.');
    }

    private function securityEvent(Request $request,$severity,$title,$actor)
    {
        try {if(!\Schema::hasTable('system_events'))return;DB::table('system_events')->insert(['event_type'=>'admin_security','severity'=>$severity,'title'=>$title,'message'=>'Administrator authentication event.','path'=>'/admin/login','method'=>'POST','actor'=>$actor,'ip_hash'=>hash_hmac('sha256',(string)$request->ip(),config('app.key')),'context'=>json_encode(['user_agent'=>substr((string)$request->userAgent(),0,500)]),'occurrence_count'=>1,'last_occurred_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);}catch(\Throwable $e){}
    }
}
