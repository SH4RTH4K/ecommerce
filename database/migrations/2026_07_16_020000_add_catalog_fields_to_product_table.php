<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCatalogFieldsToProductTable extends Migration
{
    public function up()
    {
        Schema::table('product', function (Blueprint $table) {
            $table->string('sku')->nullable()->unique()->after('product_id');
            $table->unsignedInteger('stock_quantity')->default(0)->after('product_condition');
            $table->text('short_description')->nullable()->after('Product_description');
            $table->text('key_features')->nullable()->after('short_description');
            $table->longText('specifications')->nullable()->after('key_features');
            $table->text('gallery_images')->nullable()->after('product_image');
            $table->string('warranty')->nullable()->after('stock_quantity');
            $table->string('seo_title')->nullable()->after('is_new_arrival');
            $table->text('seo_description')->nullable()->after('seo_title');
        });
    }

    public function down()
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropUnique('product_sku_unique');
            $table->dropColumn(['sku','stock_quantity','short_description','key_features','specifications','gallery_images','warranty','seo_title','seo_description']);
        });
    }
}
