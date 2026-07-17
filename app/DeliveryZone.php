<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class DeliveryZone extends Model { protected $table='delivery_zones'; protected $guarded=[]; protected $casts=['is_active'=>'boolean']; public function chargeFor($subtotal){return $this->free_delivery_minimum && $subtotal >= $this->free_delivery_minimum ? 0 : (float)$this->charge;} }
