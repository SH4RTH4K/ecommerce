<?php
namespace App;use Illuminate\Database\Eloquent\Model;class PaymentTransaction extends Model{protected $guarded=[];protected $casts=['verified_at'=>'datetime'];public function method(){return $this->belongsTo(PaymentMethod::class,'payment_method_id');}}
