<?php

class AddIconImageToCategoryTable extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('category', 'icon_image')) {
            \Illuminate\Support\Facades\Schema::table('category', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->string('icon_image', 255)->nullable()->after('icon_class');
            });
        }
    }

    public function down()
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('category', 'icon_image')) {
            \Illuminate\Support\Facades\Schema::table('category', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->dropColumn('icon_image');
            });
        }
    }
}
