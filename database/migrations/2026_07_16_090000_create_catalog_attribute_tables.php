<?php
use Illuminate\Support\Facades\Schema; use Illuminate\Database\Schema\Blueprint; use Illuminate\Database\Migrations\Migration;
class CreateCatalogAttributeTables extends Migration {
 public function up(){
  Schema::create('catalog_attributes',function(Blueprint $t){$t->increments('id');$t->unsignedInteger('category_id');$t->string('name');$t->string('slug');$t->string('input_type')->default('select');$t->text('options')->nullable();$t->boolean('is_filterable')->default(true);$t->boolean('is_comparable')->default(true);$t->unsignedInteger('display_order')->default(0);$t->timestamps();$t->unique(['category_id','slug']);$t->index(['category_id','display_order']);});
  Schema::create('product_attribute_values',function(Blueprint $t){$t->increments('id');$t->unsignedInteger('product_id');$t->unsignedInteger('attribute_id');$t->string('value');$t->timestamps();$t->unique(['product_id','attribute_id']);$t->index(['attribute_id','value']);$t->foreign('attribute_id')->references('id')->on('catalog_attributes')->onDelete('cascade');});
 }
 public function down(){Schema::dropIfExists('product_attribute_values');Schema::dropIfExists('catalog_attributes');}
}
