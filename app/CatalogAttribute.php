<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class CatalogAttribute extends Model { protected $table='catalog_attributes'; protected $guarded=[]; protected $casts=['options'=>'array','is_filterable'=>'boolean','is_comparable'=>'boolean']; public function values(){return $this->hasMany(ProductAttributeValue::class,'attribute_id');} }
