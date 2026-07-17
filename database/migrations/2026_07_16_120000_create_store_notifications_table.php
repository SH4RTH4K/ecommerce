<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoreNotificationsTable extends Migration
{
    public function up(){Schema::create('store_notifications',function(Blueprint $t){$t->increments('id');$t->string('recipient_type',20);$t->unsignedInteger('user_id')->nullable();$t->unsignedInteger('order_id')->nullable();$t->string('email')->nullable();$t->string('title');$t->text('message');$t->string('action_url')->nullable();$t->timestamp('read_at')->nullable();$t->string('email_status',20)->default('not_requested');$t->text('email_error')->nullable();$t->timestamps();$t->index(['recipient_type','read_at']);$t->index(['user_id','read_at']);});}
    public function down(){Schema::dropIfExists('store_notifications');}
}
