<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Banner extends Model { protected $table='banners'; protected $guarded=[]; protected $casts=['is_active'=>'boolean']; }
