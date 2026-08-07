<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('homepage_feature_cards')) {
            Schema::create('homepage_feature_cards', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 150);
                $table->string('card_type', 30)->default('TEXT_CTA');
                $table->string('kicker_text', 150)->nullable();
                $table->string('title', 255)->nullable();
                $table->text('description')->nullable();
                $table->string('image_path')->nullable();
                $table->string('image_alt', 255)->nullable();
                $table->string('button_text', 100)->nullable();
                $table->string('link_type', 30)->default('NONE');
                $table->string('custom_url')->nullable();
                $table->unsignedInteger('category_id')->nullable();
                $table->unsignedInteger('sub_category_id')->nullable();
                $table->unsignedInteger('product_id')->nullable();
                $table->unsignedInteger('manufacturer_id')->nullable();
                $table->string('clickable_area', 20)->default('BUTTON_ONLY');
                $table->boolean('open_in_new_tab')->default(false);
                $table->string('color_style', 30)->default('BLUE');
                $table->string('custom_background_color', 20)->nullable();
                $table->string('custom_text_color', 20)->nullable();
                $table->string('custom_button_color', 20)->nullable();
                $table->string('custom_button_text_color', 20)->nullable();
                $table->string('image_fit', 20)->default('COVER');
                $table->string('image_position', 20)->default('CENTER');
                $table->string('text_position', 30)->default('CENTER_LEFT');
                $table->string('overlay_style', 20)->default('NONE');
                $table->unsignedInteger('sort_order')->default(10);
                $table->boolean('is_active')->default(true);
                $table->boolean('use_product_image')->default(true);
                $table->boolean('use_product_name')->default(true);
                $table->boolean('use_product_price')->default(true);
                $table->timestamp('publish_from')->nullable();
                $table->timestamp('publish_until')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['is_active', 'publish_from', 'publish_until'], 'hfc_visibility_idx');
                $table->index(['sort_order', 'is_active'], 'hfc_order_idx');
            });
        }

        $now = now();
        if (DB::table('homepage_feature_cards')->count() === 0 && Schema::hasTable('site_settings')) {
            $settings = DB::table('site_settings')->pluck('setting_value', 'setting_key');
            DB::table('homepage_feature_cards')->insert([
                [
                    'name' => 'Build Your Dream PC', 'card_type' => 'TEXT_CTA',
                    'kicker_text' => $settings->get('hero_side_title') ?: 'Build your dream PC',
                    'title' => $settings->get('hero_side_text') ?: 'Expert guidance. Genuine parts.',
                    'button_text' => $settings->get('hero_side_button_text') ?: 'Get a quotation',
                    'custom_url' => $settings->get('hero_side_url') ?: '/contact-us', 'link_type' => 'CUSTOM_URL',
                    'color_style' => $settings->get('hero_side_style') ?: 'BLUE', 'is_active' => $settings->get('hero_side_enabled', '1') !== '0', 'sort_order' => 10,
                    'created_at' => $now, 'updated_at' => $now,
                ],
                [
                    'name' => 'Fast Nationwide Delivery', 'card_type' => 'TEXT_CTA',
                    'kicker_text' => $settings->get('hero_side_2_kicker') ?: 'Fast nationwide delivery',
                    'title' => $settings->get('hero_side_2_title') ?: 'Technology at your doorstep.',
                    'button_text' => $settings->get('hero_side_2_button_text') ?: 'Shop products',
                    'custom_url' => $settings->get('hero_side_2_url') ?: '#products', 'link_type' => 'ANCHOR',
                    'color_style' => $settings->get('hero_side_2_style') ?: 'ORANGE', 'is_active' => $settings->get('hero_side_2_enabled', '1') !== '0', 'sort_order' => 20,
                    'created_at' => $now, 'updated_at' => $now,
                ],
            ]);
        }

        if (Schema::hasTable('site_settings') && ! DB::table('site_settings')->where('setting_key', 'homepage_feature_card_config')->exists()) {
            DB::table('site_settings')->insert(['setting_key' => 'homepage_feature_card_config', 'setting_value' => json_encode([
                'layout' => 'STACKED', 'max_visible_cards' => 2, 'card_gap' => 18, 'equal_height' => true,
                'slider_autoplay' => true, 'slider_interval' => 5, 'slider_arrows' => true, 'slider_dots' => true, 'pause_on_hover' => true,
            ]), 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_feature_cards');
        if (Schema::hasTable('site_settings')) DB::table('site_settings')->where('setting_key', 'homepage_feature_card_config')->delete();
    }
};
