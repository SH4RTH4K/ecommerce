<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationUpdateSetting extends Model
{
    protected $table = 'application_update_settings';
    protected $guarded = [];
    protected $casts = [
        'enabled' => 'boolean', 'run_migrations' => 'boolean',
        'clear_cache' => 'boolean', 'health_check' => 'boolean',
    ];
}
