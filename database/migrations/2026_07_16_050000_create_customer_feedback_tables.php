<?php
use Illuminate\Support\Facades\Schema; use Illuminate\Database\Schema\Blueprint; use Illuminate\Database\Migrations\Migration;
class CreateCustomerFeedbackTables extends Migration {
 public function up(){
  Schema::create('product_reviews',function(Blueprint $t){$t->increments('id');$t->unsignedInteger('product_id');$t->unsignedInteger('user_id')->nullable();$t->string('customer_name');$t->string('email')->nullable();$t->unsignedTinyInteger('rating');$t->text('review');$t->boolean('is_approved')->default(false);$t->timestamps();$t->index(['product_id','is_approved']);});
  Schema::create('product_questions',function(Blueprint $t){$t->increments('id');$t->unsignedInteger('product_id');$t->unsignedInteger('user_id')->nullable();$t->string('customer_name');$t->string('email')->nullable();$t->text('question');$t->text('answer')->nullable();$t->boolean('is_approved')->default(false);$t->timestamp('answered_at')->nullable();$t->timestamps();$t->index(['product_id','is_approved']);});
  Schema::create('support_requests',function(Blueprint $t){$t->increments('id');$t->unsignedInteger('user_id')->nullable();$t->string('customer_name');$t->string('email');$t->string('phone',30)->nullable();$t->string('subject');$t->string('order_number')->nullable();$t->text('message');$t->string('status')->default('new');$t->text('admin_note')->nullable();$t->timestamps();});
 }
 public function down(){Schema::dropIfExists('support_requests');Schema::dropIfExists('product_questions');Schema::dropIfExists('product_reviews');}
}
