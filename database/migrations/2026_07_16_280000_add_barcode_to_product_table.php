<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBarcodeToProductTable extends Migration
{
    public function up()
    {
        Schema::table('product', function (Blueprint $table) {
            $table->string('barcode', 64)->nullable()->unique()->after('sku');
        });
    }

    public function down()
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropUnique('product_barcode_unique');
            $table->dropColumn('barcode');
        });
    }
}
