@php
    $cartCount = array_sum((array) session('cart', []));
    $compareCount = count((array) session('compare', []));
    $wishlistCount = auth()->check() && Schema::hasTable('wishlists') ? DB::table('wishlists')->where('user_id',auth()->id())->count() : 0;
@endphp
@php
    $siteName = $brandName;
    $siteLogo = $brandLogo;
@endphp
<header class="lt-header">
    <div class="lt-container lt-header-main">
        <div class="lt-brand-lockup"><a class="lt-logo" href="{{ url('/') }}" aria-label="{{ $siteName }} home"><img src="{{ asset($siteLogo) }}" alt="{{ $siteName }}" decoding="async"></a>@if($siteSettings->get('site_tagline'))<span class="lt-brand-tagline">{{ $siteSettings->get('site_tagline') }}</span>@endif</div>
        <form class="lt-search" action="{{ url('/search-product') }}" method="post" role="search">
            {{ csrf_field() }}
            <label class="sr-only" for="site-search">Search products</label>
            <input id="site-search" name="search_text" type="search" placeholder="Search by product name or model" required>
            <button type="submit" aria-label="Search"><i class="fa fa-search"></i></button>
        </form>
        <nav class="lt-actions" aria-label="Account actions">
            <a href="{{ route('pc-builder.index') }}"><i class="fa fa-wrench"></i><span>PC Builder</span></a>
            <a href="{{ auth()->check() ? route('wishlist.index') : url('/login') }}"><i class="fa fa-heart-o"></i><span>Wishlist</span>@if($wishlistCount)<b>{{ $wishlistCount }}</b>@endif</a>
            <a href="{{ auth()->check() ? route('account.orders') : url('/login') }}"><i class="fa fa-user"></i><span>{{ auth()->check() ? 'My Orders' : 'Account' }}</span></a>
            <a href="{{ route('compare.index') }}"><i class="fa fa-exchange"></i><span>Compare</span><b>{{ $compareCount }}</b></a>
            <a href="{{ route('cart.index') }}"><i class="fa fa-shopping-cart"></i><span>Cart</span><b>{{ $cartCount }}</b></a>
        </nav>
        <button class="lt-menu-toggle" type="button" aria-expanded="false" aria-controls="main-menu"><i class="fa fa-bars"></i><span>Menu</span></button>
    </div>
    @include('partials.mega-menu')
</header>
