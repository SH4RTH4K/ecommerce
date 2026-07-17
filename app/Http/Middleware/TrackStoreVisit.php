<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrackStoreVisit
{
    public function handle($request, Closure $next)
    {
        $response=$next($request);
        $excluded=$request->session()->has('admin_id') || !$request->isMethod('get') || $request->ajax() || $response->getStatusCode()>=400 || $request->is('admin*','dashboard','manage-*','add-*','edit-*','inventory*','catalog-attributes*','delivery-zones*','coupons*','customer-inbox*','service-claims*','stock-alerts*','payment-methods*','abandoned-carts*','sales-reports*','admin-notifications*','site-customization*','login','register','password/*');
        if($excluded || !Schema::hasTable('visitor_sessions')) return $response;
        try {
            $key=$request->session()->get('visitor_key');
            if(!$key){$key=bin2hex(random_bytes(24));$request->session()->put('visitor_key',$key);}
            $now=now();$visit=DB::table('visitor_sessions')->where('visitor_key',$key)->first();$path='/'.ltrim($request->path(),'/');
            $data=['user_id'=>auth()->id(),'ip_hash'=>hash_hmac('sha256',(string)$request->ip(),config('app.key')),'current_path'=>$path,'user_agent'=>substr((string)$request->userAgent(),0,500),'last_seen_at'=>$now,'updated_at'=>$now];
            if($visit){DB::table('visitor_sessions')->where('id',$visit->id)->update(array_merge($data,['pageviews'=>DB::raw('pageviews + 1')]));$id=$visit->id;}
            else{$data['visitor_key']=$key;$data['landing_path']=$path;$data['pageviews']=1;$data['first_seen_at']=$now;$data['created_at']=$now;$id=DB::table('visitor_sessions')->insertGetId($data);}
            DB::table('page_visits')->insert(['visitor_session_id'=>$id,'user_id'=>auth()->id(),'path'=>$path,'page_title'=>null,'referrer'=>substr((string)$request->headers->get('referer'),0,1000),'visited_at'=>$now]);
        } catch (\Throwable $e) {}
        return $response;
    }
}
