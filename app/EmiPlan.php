<?php
namespace App;use Illuminate\Database\Eloquent\Model;
class EmiPlan extends Model {protected $guarded=[];protected $casts=['is_active'=>'boolean'];public function monthlyAmount($total){$payable=$total+($total*((float)$this->interest_rate/100))+(float)$this->processing_fee;return round($payable/$this->months,2);}public function method(){return $this->belongsTo(PaymentMethod::class,'payment_method_id');}}
