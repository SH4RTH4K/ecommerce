<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product', function (Blueprint $table) {
            $table->increments('id');
            $table->string('product_id');
            $table->string('category_id');
            $table->string('sub_category');
            $table->string('manufacturer_id');
            $table->string('product_model');
            $table->string('product_name');
            $table->text('Product_description');
            $table->decimal('offer_price', 12, 2)->nullable();
            $table->string('product_condition');
            $table->string('product_image');
            $table->tinyInteger('publication_status');
            $table->tinyInteger('top_product');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product');
    }
}
