<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWishlistsAndSavedBuildsTables extends Migration
{
 public function up(){Schema::create('wishlists',function(Blueprint $t){$t->increments('id');$t->unsignedInteger('user_id');$t->unsignedInteger('product_id');$t->timestamps();$t->unique(['user_id','product_id']);$t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');});Schema::create('saved_pc_builds',function(Blueprint $t){$t->increments('id');$t->unsignedInteger('user_id');$t->string('name');$t->text('components');$t->decimal('estimated_total',12,2)->default(0);$t->timestamps();$t->index(['user_id','updated_at']);$t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');});}
 public function down(){Schema::dropIfExists('saved_pc_builds');Schema::dropIfExists('wishlists');}
}
