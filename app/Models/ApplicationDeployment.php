<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationDeployment extends Model
{
    protected $table = 'application_deployments';
    protected $guarded = [];
    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime'];
}
