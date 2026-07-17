<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHomepageFieldsToProductTable extends Migration
{
    public function up()
    {
        Schema::table('product', function (Blueprint $table) {
            $table->decimal('regular_price', 12, 2)->default(0)->after('offer_price');
            // top_product is the existing featured flag; do not duplicate it.
            $table->boolean('is_new_arrival')->default(false)->after('top_product');
        });
    }

    public function down()
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn(['regular_price', 'is_new_arrival']);
        });
    }
}
