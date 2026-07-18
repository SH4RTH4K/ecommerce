<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', isset($siteSettings['default_meta_description']) && $siteSettings['default_meta_description'] ? $siteSettings['default_meta_description'] : 'Shop products online from '.$brandName.'.')">
    <meta name="robots" content="@yield('meta_robots', isset($siteSettings['robots_directive']) && $siteSettings['robots_directive'] ? $siteSettings['robots_directive'] : 'index,follow')">
    <title>@yield('title', isset($siteSettings['default_meta_title']) && $siteSettings['default_meta_title'] ? $siteSettings['default_meta_title'] : 'Products | '.$brandName)</title>
    @if(isset($siteSettings['meta_keywords']) && $siteSettings['meta_keywords'])<meta name="keywords" content="{{ $siteSettings['meta_keywords'] }}">@endif
    @if(isset($siteSettings['google_site_verification']) && $siteSettings['google_site_verification'])<meta name="google-site-verification" content="{{ $siteSettings['google_site_verification'] }}">@endif
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('og_title', trim($__env->yieldContent('title', $brandName)))">
    <meta property="og:description" content="@yield('og_description', trim($__env->yieldContent('meta_description', 'Shop products online from '.$brandName.'.')))">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:site_name" content="{{ $brandName }}">
    @hasSection('og_image')<meta property="og:image" content="@yield('og_image')">@elseif(isset($siteSettings['default_og_image']) && $siteSettings['default_og_image'])<meta property="og:image" content="{{ asset($siteSettings['default_og_image']) }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="{{ asset($brandFavicon) }}">
    <link rel="stylesheet" href="{{ asset('asset/front-end/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ecommerce-home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/top-bar.css') }}">
    @include('partials.google-analytics')
    @stack('structured_data')
</head>
<body>
    <a class="lt-skip-link" href="#main-content">Skip to content</a>
    @include('partials.topbar')
    @include('partials.header')

    <main id="main-content" class="lt-container lt-catalog-main">
        @yield('main_content')
    </main>

    @include('partials.footer')
    <script src="{{ asset('js/ecommerce-home.js') }}" defer></script>
</body>
</html>
