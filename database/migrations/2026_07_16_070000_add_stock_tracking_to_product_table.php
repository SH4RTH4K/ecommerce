<?php
use Illuminate\Support\Facades\Schema; use Illuminate\Database\Schema\Blueprint; use Illuminate\Database\Migrations\Migration;
class AddStockTrackingToProductTable extends Migration { public function up(){Schema::table('product',function(Blueprint $table){$table->boolean('stock_tracking')->default(false)->after('stock_quantity');});} public function down(){Schema::table('product',function(Blueprint $table){$table->dropColumn('stock_tracking');});} }
