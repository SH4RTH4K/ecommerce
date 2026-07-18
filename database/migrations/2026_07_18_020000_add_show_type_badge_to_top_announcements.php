<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddShowTypeBadgeToTopAnnouncements extends Migration {
 public function up(){Schema::table('top_announcements',function(Blueprint $table){$table->boolean('show_type_badge')->default(true)->after('announcement_type');});}
 public function down(){Schema::table('top_announcements',function(Blueprint $table){$table->dropColumn('show_type_badge');});}
}
