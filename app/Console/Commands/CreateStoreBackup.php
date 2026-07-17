<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\DatabaseBackupService;

class CreateStoreBackup extends Command
{
    protected $signature='store:backup';
    protected $description='Create a compressed Ecommerce database backup';

    public function handle(DatabaseBackupService $service)
    {
        $started=microtime(true);$runId=null;
        if(Schema::hasTable('scheduled_task_runs'))$runId=DB::table('scheduled_task_runs')->insertGetId(['task_name'=>'store:backup','status'=>'running','started_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        try {$id=$service->create('scheduler');$message='Database backup completed: '.$id;$this->info($message);if($runId)DB::table('scheduled_task_runs')->where('id',$runId)->update(['status'=>'completed','finished_at'=>now(),'duration_ms'=>(int)((microtime(true)-$started)*1000),'output'=>$message,'updated_at'=>now()]);return 0;}
        catch(\Throwable $e){$this->error($e->getMessage());if($runId)DB::table('scheduled_task_runs')->where('id',$runId)->update(['status'=>'failed','finished_at'=>now(),'duration_ms'=>(int)((microtime(true)-$started)*1000),'output'=>substr($e->getMessage(),0,5000),'updated_at'=>now()]);return 1;}
    }
}
