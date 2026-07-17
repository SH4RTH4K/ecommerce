<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * The URIs that should remain reachable during maintenance mode.
     *
     * @var array<int, string>
     */
    protected $except = [];
}
