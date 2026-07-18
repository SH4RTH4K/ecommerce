<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendBannersForLinkedPromotions extends Migration
{
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('banner_type', 30)->default('custom')->after('id');
            $table->unsignedInteger('product_id')->nullable()->after('banner_type');
            $table->unsignedInteger('category_id')->nullable()->after('product_id');
            $table->string('button_text', 100)->nullable()->after('subtitle');
            $table->string('mobile_image')->nullable()->after('image_path');
            $table->boolean('use_product_image')->default(false)->after('mobile_image');
            $table->string('image_position', 30)->default('center')->after('use_product_image');
            $table->boolean('show_overlay')->default(true)->after('image_position');
            $table->timestamp('starts_at')->nullable()->after('display_order');
            $table->timestamp('expires_at')->nullable()->after('starts_at');
            $table->boolean('open_in_new_tab')->default(false)->after('expires_at');
            $table->index(['is_active', 'starts_at', 'expires_at'], 'banners_visibility_schedule_index');
            $table->index('product_id');
            $table->index('category_id');
        });
    }

    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropIndex('banners_visibility_schedule_index');
            $table->dropIndex(['product_id']);
            $table->dropIndex(['category_id']);
            $table->dropColumn(['banner_type','product_id','category_id','button_text','mobile_image','use_product_image','image_position','show_overlay','starts_at','expires_at','open_in_new_tab']);
        });
    }
}
