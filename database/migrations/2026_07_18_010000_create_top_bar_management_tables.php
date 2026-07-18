<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTopBarManagementTables extends Migration
{
    public function up()
    {
        Schema::create('top_announcements', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 150)->nullable();
            $table->string('message', 500);
            $table->string('announcement_type', 30)->default('general');
            $table->string('display_location', 30)->default('top_bar');
            $table->string('display_mode', 20)->default('static');
            $table->string('link_url', 500)->nullable();
            $table->string('link_text', 100)->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->unsignedTinyInteger('priority')->default(2);
            $table->unsignedInteger('display_order')->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_desktop')->default(true);
            $table->boolean('show_on_mobile')->default(true);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
            $table->index(['is_active', 'starts_at', 'expires_at']);
            $table->index(['priority', 'display_order']);
        });

        Schema::create('site_contact_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('contact_type', 30);
            $table->string('label', 100);
            $table->string('value', 255);
            $table->string('link_url', 500)->nullable();
            $table->string('icon', 50)->nullable();
            $table->text('default_message')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_desktop')->default(true);
            $table->boolean('show_on_mobile')->default(true);
            $table->boolean('open_in_new_tab')->default(false);
            $table->timestamps();
            $table->index(['is_active', 'display_order']);
            $table->index(['contact_type', 'is_primary']);
        });

        $defaults = [
            'top_bar_enabled' => '1', 'top_bar_mobile_enabled' => '1',
            'top_bar_background_color' => '#073451', 'top_bar_text_color' => '#ffffff',
            'top_bar_link_color' => '#ffffff', 'top_bar_height' => '36',
            'top_bar_sticky' => '0', 'top_bar_show_announcement' => '1',
            'top_bar_show_contacts' => '1', 'top_bar_show_support_link' => '1',
            'announcement_rotation_interval' => '5000', 'support_link_enabled' => '0',
        ];
        foreach ($defaults as $key => $value) {
            DB::table('site_settings')->updateOrInsert(['setting_key' => $key], [
                'setting_value' => $value, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('site_contact_items');
        Schema::dropIfExists('top_announcements');
        DB::table('site_settings')->whereIn('setting_key', [
            'top_bar_enabled','top_bar_mobile_enabled','top_bar_background_color','top_bar_text_color',
            'top_bar_link_color','top_bar_height','top_bar_sticky','top_bar_show_announcement',
            'top_bar_show_contacts','top_bar_show_support_link','announcement_rotation_interval',
            'support_link_enabled','support_link_label','support_link_type','support_link_url',
            'support_link_open_new_tab','support_link_icon',
        ])->delete();
    }
}
