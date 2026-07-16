<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use DB;
use Session;

class AdminController extends Controller
{
    public function index(){
        $this->AdminAuthCheck();
        return view('admin.admin-pages.admin-login');
    }

    public function admin_login(Request $request){
        $name=$request->username;
        $password=$request->password;
        $result=DB::table('tbl_admin')
                ->where('admin_name', $name)
                ->where('admin_password', md5($password))
                ->first();
        if($result){
            Session::put('admin_name',$result->admin_name);
            Session::put('admin_id',$result->admin_id);
            return Redirect::to('/dashboard');

        }
        else{
            Session::put('exception','User Name or Password Invalid!');
            return Redirect::to('/xyz');
        }
    }
    
    public function AdminAuthCheck(){
        $admin_id=Session::get('admin_id');
        if($admin_id){
            return Redirect::to('/dashboard')->send();
            
        }
        else{
            return;
        }
    }
}
