@php
    $cartCount = array_sum((array) session('cart', []));
    $compareCount = count((array) session('compare', []));
    $wishlistCount = auth()->check() && Schema::hasTable('wishlists') ? DB::table('wishlists')->where('user_id',auth()->id())->count() : 0;
@endphp
@php
    $siteName = $brandName;
    $siteLogo = $brandLogoHeader ?: $brandLogo;
    $tabletLogo = $brandLogoTablet ?: $siteLogo;
    $mobileLogo = $brandLogoMobile ?: $tabletLogo;
    $siteNameIsBengali = (bool)preg_match('/[\x{0980}-\x{09FF}]/u', $siteName);
    $headerTagline = (string)$siteSettings->get('site_tagline', '');
    $headerTaglineIsBengali = (bool)preg_match('/[\x{0980}-\x{09FF}]/u', $headerTagline);
    $headerLogoIsRemote = $siteLogo && preg_match('#^https?://#i', $siteLogo);
    $resolvedHeaderLogo = $siteLogo ? ($headerLogoIsRemote ? $siteLogo : asset($siteLogo)) : null;
    $headerLogoPath = $siteLogo ? parse_url($siteLogo, PHP_URL_PATH) : null;
    $headerLogoAvailable = $siteLogo && ($headerLogoIsRemote || ($headerLogoPath && file_exists(public_path(ltrim($headerLogoPath, '/')))));
    if (!$headerLogoAvailable) $resolvedHeaderLogo = null;
    $resolveResponsiveLogo = function ($logo) {
        if (!$logo) return null;
        $isRemote = preg_match('#^https?://#i', $logo);
        $path = parse_url($logo, PHP_URL_PATH);
        return $isRemote || ($path && file_exists(public_path(ltrim($path, '/')))) ? ($isRemote ? $logo : asset($logo)) : null;
    };
    $resolvedTabletLogo = $resolveResponsiveLogo($tabletLogo);
    $resolvedMobileLogo = $resolveResponsiveLogo($mobileLogo);
@endphp
<header class="lt-header">
    <div class="lt-container lt-header-main">
        <div class="lt-brand-lockup" style="--brand-name-font-size:{{ $brandNameFontSize }}px;--brand-tagline-font-size:{{ $brandTaglineFontSize }}px;--brand-logo-width:{{ $brandLogoDisplayWidth }}px;--brand-logo-height:{{ $brandLogoDisplayHeight }}px;--brand-logo-mobile-width:{{ $brandLogoMobileWidth }}px;--brand-logo-mobile-height:{{ $brandLogoMobileHeight }}px">
            <a class="lt-logo" href="{{ url('/') }}" aria-label="{{ $siteName }} home">@if($resolvedHeaderLogo)<picture>@if($resolvedMobileLogo)<source media="(max-width: 720px)" srcset="{{ $resolvedMobileLogo }}">@endif @if($resolvedTabletLogo)<source media="(min-width: 721px) and (max-width: 1024px)" srcset="{{ $resolvedTabletLogo }}">@endif<img src="{{ $resolvedHeaderLogo }}" alt="{{ $siteName }}" decoding="async"></picture>@else<span class="lt-brand-name {{ $siteNameIsBengali ? 'is-bengali' : '' }}" @if($siteNameIsBengali) lang="bn" @endif>{{ $siteName }}</span>@endif</a>
            @if($headerTagline)<span class="lt-brand-tagline {{ $headerTaglineIsBengali ? 'is-bengali' : '' }}" @if($headerTaglineIsBengali) lang="bn" @endif>{{ $headerTagline }}</span>@endif
        </div>
        <form class="lt-search" action="{{ url('/search-product') }}" method="post" role="search" data-live-search>
            {{ csrf_field() }}
            <label class="sr-only" for="site-search">Search products</label>
            <input id="site-search" name="search_text" type="search" placeholder="Search by product name or model" required>
            <button type="submit" aria-label="Search"><i class="fa fa-search"></i></button>
            <div class="lt-search-panel" data-search-panel hidden>
                <div class="lt-search-tabs" role="tablist">
                    <button type="button" class="is-active" data-search-tab="products" role="tab" aria-selected="true">Products</button>
                    <button type="button" data-search-tab="categories" role="tab" aria-selected="false">Categories</button>
                </div>
                <div class="lt-search-results" data-search-results></div>
                <a class="lt-search-all" data-search-all href="#">See all results</a>
            </div>
        </form>
        <nav class="lt-actions" aria-label="Account actions">
            <a href="{{ auth()->check() ? route('wishlist.index') : url('/login') }}"><i class="fa fa-heart-o"></i><span>Wishlist</span>@if($wishlistCount)<b>{{ $wishlistCount }}</b>@endif</a>
            <a href="{{ auth()->check() ? route('account.orders') : url('/login') }}"><i class="fa fa-user"></i><span>{{ auth()->check() ? 'My Orders' : 'Account' }}</span></a>
            <a href="{{ route('compare.index') }}"><i class="fa fa-exchange"></i><span>Compare</span><b>{{ $compareCount }}</b></a>
            <a href="{{ route('cart.index') }}"><i class="fa fa-shopping-cart"></i><span>Cart</span><b>{{ $cartCount }}</b></a>
            <a class="lt-nav-cta" href="{{ route('pc-builder.index') }}"><i class="fa fa-wrench"></i><span>PC Builder</span></a>
        </nav>
        <button class="lt-menu-toggle" type="button" aria-expanded="false" aria-controls="main-menu"><i class="fa fa-bars"></i><span>Menu</span></button>
    </div>
    @include('partials.mega-menu')
</header>
<script src="{{ asset('js/storefront-navbar.js') }}" defer></script>
<script src="{{ asset('js/storefront-search.js') }}?v={{ filemtime(public_path('js/storefront-search.js')) }}" defer></script>
