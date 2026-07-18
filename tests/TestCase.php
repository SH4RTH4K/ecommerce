<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep the normal web stack in feature tests while bypassing this
        // project's custom-named CSRF middleware, as Laravel does by default.
        $this->withoutMiddleware(\App\Http\Middleware\PreventRequestForgery::class);
        if (!($this instanceof \Tests\Feature\DevelopmentModeTest)) {
            $this->withoutMiddleware(\App\Http\Middleware\DevelopmentModeMiddleware::class);
        }
    }
}
