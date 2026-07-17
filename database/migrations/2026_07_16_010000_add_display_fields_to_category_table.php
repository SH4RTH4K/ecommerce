<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDisplayFieldsToCategoryTable extends Migration
{
    public function up()
    {
        Schema::table('category', function (Blueprint $table) {
            $table->string('icon_class', 50)->default('fa-folder-open')->after('category_description');
            $table->boolean('is_featured')->default(true)->after('icon_class');
            $table->unsignedSmallInteger('display_order')->default(0)->after('is_featured');
        });

        $icons = [
            'audio' => 'fa-music', 'bluetooth' => 'fa-signal', 'cable' => 'fa-link',
            'casing' => 'fa-archive', 'connector' => 'fa-link', 'cpu cooler' => 'fa-refresh',
            'desktop pc' => 'fa-desktop', 'dvd writer' => 'fa-dot-circle-o', 'game pad' => 'fa-gamepad',
            'graphics card' => 'fa-picture-o', 'hdd' => 'fa-hdd-o', 'headphone' => 'fa-headphones',
            'ip camera' => 'fa-video-camera', 'keyboard' => 'fa-keyboard-o', 'laptop' => 'fa-laptop',
            'monitor' => 'fa-desktop', 'mouse' => 'fa-mouse-pointer', 'printer' => 'fa-print',
            'router' => 'fa-signal', 'smart watch' => 'fa-clock-o', 'speaker' => 'fa-volume-up',
            'ups' => 'fa-bolt', 'webcam' => 'fa-camera', 'wifi receiver' => 'fa-signal',
        ];

        DB::table('category')->update(['display_order' => 999]);

        $order = 1;
        foreach ($icons as $name => $icon) {
            DB::table('category')->whereRaw('LOWER(category_name) = ?', [$name])->update([
                'icon_class' => $icon,
                'display_order' => $order++,
            ]);
        }
    }

    public function down()
    {
        Schema::table('category', function (Blueprint $table) {
            $table->dropColumn(['icon_class', 'is_featured', 'display_order']);
        });
    }
}
