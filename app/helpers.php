<?php

use Illuminate\Support\Str;

if (! function_exists('str_random')) {
    function str_random(int $length = 16): string
    {
        return Str::random($length);
    }
}

if (! function_exists('str_slug')) {
    function str_slug(string $title, string $separator = '-'): string
    {
        return Str::slug($title, $separator);
    }
}
