<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
        if (!$this->shouldReport($exception)) return;
        try {
            if (!Schema::hasTable('system_events')) return;
            $request=app()->runningInConsole()?null:request();
            $fingerprint=hash('sha256',get_class($exception).'|'.$exception->getMessage().'|'.$exception->getFile().'|'.$exception->getLine());
            $existing=DB::table('system_events')->where('event_type','application_error')->where('fingerprint',$fingerprint)->whereNull('resolved_at')->first();
            if($existing) DB::table('system_events')->where('id',$existing->id)->update(['occurrence_count'=>DB::raw('occurrence_count + 1'),'last_occurred_at'=>now(),'updated_at'=>now()]);
            else DB::table('system_events')->insert(['event_type'=>'application_error','severity'=>'error','fingerprint'=>$fingerprint,'title'=>class_basename($exception),'message'=>substr($exception->getMessage(),0,5000),'path'=>$request?'/'.ltrim($request->path(),'/'):null,'method'=>$request?$request->method():null,'ip_hash'=>$request?hash_hmac('sha256',(string)$request->ip(),config('app.key')):null,'context'=>json_encode(['file'=>$exception->getFile(),'line'=>$exception->getLine()]),'occurrence_count'=>1,'last_occurred_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        } catch (\Throwable $ignored) {}
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        return parent::render($request, $exception);
    }
}
