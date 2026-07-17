<?php
use Illuminate\Support\Facades\Schema; use Illuminate\Database\Schema\Blueprint; use Illuminate\Database\Migrations\Migration;
class NormalizeManufacturerPrimaryKey extends Migration {
 public function up(){if(Schema::hasColumn('manufacturer','menufacturer_id')&&!Schema::hasColumn('manufacturer','manufacturer_id'))Schema::table('manufacturer',function(Blueprint $table){$table->renameColumn('menufacturer_id','manufacturer_id');});}
 public function down(){if(Schema::hasColumn('manufacturer','manufacturer_id')&&!Schema::hasColumn('manufacturer','menufacturer_id'))Schema::table('manufacturer',function(Blueprint $table){$table->renameColumn('manufacturer_id','menufacturer_id');});}
}
