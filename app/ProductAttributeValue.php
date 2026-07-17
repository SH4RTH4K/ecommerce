<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class ProductAttributeValue extends Model {
    protected $table='product_attribute_values'; protected $guarded=[];
    public function attribute(){return $this->belongsTo(CatalogAttribute::class,'attribute_id');}
    public function getValuesAttribute(){ $decoded=json_decode($this->value,true); return is_array($decoded)?array_values($decoded):[$this->value]; }
    public function getDisplayValueAttribute(){ return implode(', ',$this->values); }
}
