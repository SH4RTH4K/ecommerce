<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderReturnsAndCreditNotes extends Migration
{
    public function up()
    {
        Schema::create('order_returns',function(Blueprint $t){
            $t->increments('id');$t->string('return_number',30)->unique();$t->unsignedInteger('order_id');$t->unsignedInteger('user_id')->nullable();
            $t->string('customer_name');$t->string('email')->nullable();$t->string('phone',30);$t->string('reason',50);$t->text('details');
            $t->string('status',30)->default('requested');$t->decimal('requested_amount',12,2)->default(0);$t->decimal('approved_amount',12,2)->nullable();
            $t->text('admin_note')->nullable();$t->string('processed_by')->nullable();$t->timestamp('approved_at')->nullable();$t->timestamp('received_at')->nullable();$t->timestamp('completed_at')->nullable();$t->timestamps();
            $t->index(['order_id','status']);$t->index(['user_id','created_at']);
        });
        Schema::create('order_return_items',function(Blueprint $t){
            $t->increments('id');$t->unsignedInteger('order_return_id');$t->unsignedInteger('order_item_id');$t->unsignedInteger('product_id')->nullable();
            $t->string('product_name');$t->string('sku')->nullable();$t->decimal('unit_price',12,2);$t->unsignedInteger('quantity');$t->decimal('amount',12,2);
            $t->boolean('restock')->default(true);$t->timestamp('inventory_restored_at')->nullable();$t->timestamps();
            $t->unique(['order_return_id','order_item_id']);
        });
        Schema::create('refunds',function(Blueprint $t){
            $t->increments('id');$t->string('refund_number',30)->unique();$t->unsignedInteger('order_return_id');$t->unsignedInteger('order_id');
            $t->decimal('amount',12,2);$t->string('method',40);$t->string('transaction_reference')->nullable();$t->string('status',20)->default('completed');
            $t->text('note')->nullable();$t->string('processed_by')->nullable();$t->timestamp('refunded_at');$t->timestamps();$t->unique('order_return_id');
        });
        Schema::create('credit_notes',function(Blueprint $t){
            $t->increments('id');$t->string('credit_note_number',30)->unique();$t->unsignedInteger('order_return_id');$t->unsignedInteger('refund_id');$t->unsignedInteger('order_id');
            $t->decimal('amount',12,2);$t->timestamp('issued_at');$t->string('issued_by')->nullable();$t->timestamps();$t->unique('order_return_id');
        });
    }
    public function down(){Schema::dropIfExists('credit_notes');Schema::dropIfExists('refunds');Schema::dropIfExists('order_return_items');Schema::dropIfExists('order_returns');}
}
