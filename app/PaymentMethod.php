<?php
namespace App;use Illuminate\Database\Eloquent\Model;
class PaymentMethod extends Model {protected $guarded=[];protected $casts=['supports_emi'=>'boolean','is_active'=>'boolean'];public function emiPlans(){return $this->hasMany(EmiPlan::class)->where('is_active',1)->orderBy('months');}}
