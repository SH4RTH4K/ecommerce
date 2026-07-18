<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Account | '.$brandName)</title>
    <link rel="icon" href="{{ asset(isset($siteSettings['favicon']) && $siteSettings['favicon'] ? $siteSettings['favicon'] : 'favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('asset/front-end/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ecommerce-home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/top-bar.css') }}">
    @include('partials.google-analytics')
</head>
<body>
    <a class="lt-skip-link" href="#main-content">Skip to content</a>
    @include('partials.topbar')
    @include('partials.header')
    <main id="main-content" class="lt-auth-main">@yield('content')</main>
    @include('partials.footer')
    <script src="{{ asset('js/ecommerce-home.js') }}" defer></script>
</body>
</html>
