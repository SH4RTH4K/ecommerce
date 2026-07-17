<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php $siteName = $brandName; $siteLogo = $brandLogo; $metaDescription = isset($siteSettings['default_meta_description']) && $siteSettings['default_meta_description'] ? $siteSettings['default_meta_description'] : 'Shop products online from '.$brandName.'.'; @endphp
    <meta name="description" content="{{ $metaDescription }}">
    <title>{{ isset($siteSettings['default_meta_title']) && $siteSettings['default_meta_title'] ? $siteSettings['default_meta_title'] : $siteName.' | Computers, Networking & Accessories' }}</title>
    <link rel="canonical" href="{{ url('/') }}">
    <meta name="robots" content="{{ isset($siteSettings['robots_directive']) && $siteSettings['robots_directive'] ? $siteSettings['robots_directive'] : 'index,follow' }}">
    @if(isset($siteSettings['meta_keywords']) && $siteSettings['meta_keywords'])<meta name="keywords" content="{{ $siteSettings['meta_keywords'] }}">@endif
    @if(isset($siteSettings['google_site_verification']) && $siteSettings['google_site_verification'])<meta name="google-site-verification" content="{{ $siteSettings['google_site_verification'] }}">@endif
    <meta property="og:type" content="website"><meta property="og:title" content="{{ isset($siteSettings['default_meta_title']) && $siteSettings['default_meta_title'] ? $siteSettings['default_meta_title'] : $siteName.' | Computers, Networking & Accessories' }}"><meta property="og:description" content="{{ $metaDescription }}"><meta property="og:url" content="{{ url('/') }}"><meta property="og:site_name" content="{{ $siteName }}"><meta property="og:image" content="{{ asset(isset($siteSettings['default_og_image']) && $siteSettings['default_og_image'] ? $siteSettings['default_og_image'] : $siteLogo) }}"><meta name="twitter:card" content="summary_large_image">
    <script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'Organization','name'=>$siteName,'url'=>url('/'),'logo'=>asset($siteLogo),'email'=>isset($siteSettings['support_email'])?$siteSettings['support_email']:'support@example.com','telephone'=>isset($siteSettings['phone'])?$siteSettings['phone']:'+8801711000000','sameAs'=>array_values(array_filter([isset($siteSettings['facebook_url'])?$siteSettings['facebook_url']:null,isset($siteSettings['instagram_url'])?$siteSettings['instagram_url']:null,isset($siteSettings['youtube_url'])?$siteSettings['youtube_url']:null,isset($siteSettings['linkedin_url'])?$siteSettings['linkedin_url']:null,isset($siteSettings['twitter_url'])?$siteSettings['twitter_url']:null]))], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
    <link rel="icon" href="{{ asset(isset($siteSettings['favicon']) && $siteSettings['favicon'] ? $siteSettings['favicon'] : 'favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('asset/front-end/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ecommerce-home.css') }}">
    @include('partials.google-analytics')
</head>
<body>
    <a class="lt-skip-link" href="#main-content">Skip to content</a>
    @include('partials.topbar')
    @include('partials.header')

    <main id="main-content">
        <section class="lt-hero lt-container" aria-label="Featured promotions">
            <div class="lt-carousel" data-carousel tabindex="0">
                @php $slides = $banners->isNotEmpty() ? $banners : collect(['pic 1.jpg', 'pic 2.jpg', 'pic 3.jpg']); @endphp
                @foreach($slides as $index => $slide)
                    <div class="lt-slide {{ $index === 0 ? 'is-active' : '' }}" data-slide aria-hidden="{{ $index === 0 ? 'false' : 'true' }}">
                        @php $isCustom = is_object($slide); $image = $isCustom ? $slide->image_path : 'asset/front-end/img/home/'.$slide; @endphp
                        @if($isCustom && $slide->link_url)<a href="{{ url($slide->link_url) }}">@endif
                        <img src="{{ asset($image) }}" alt="{{ $isCustom && $slide->title ? $slide->title : $brandName.' promotion '.($index + 1) }}" {{ $index === 0 ? 'fetchpriority=high' : 'loading=lazy' }}>
                        @if($isCustom && ($slide->title || $slide->subtitle))<span class="lt-banner-copy"><strong>{{ $slide->title }}</strong><small>{{ $slide->subtitle }}</small></span>@endif
                        @if($isCustom && $slide->link_url)</a>@endif
                    </div>
                @endforeach
                <button class="lt-carousel-control lt-prev" type="button" data-prev aria-label="Previous slide"><i class="fa fa-angle-left"></i></button>
                <button class="lt-carousel-control lt-next" type="button" data-next aria-label="Next slide"><i class="fa fa-angle-right"></i></button>
                <button class="lt-carousel-pause" type="button" data-pause aria-label="Pause carousel"><i class="fa fa-pause"></i></button>
            </div>
            <aside class="lt-hero-side">
                <div><span>{{ isset($siteSettings['hero_side_title']) && $siteSettings['hero_side_title'] ? $siteSettings['hero_side_title'] : 'Build your dream PC' }}</span><h2>{{ isset($siteSettings['hero_side_text']) && $siteSettings['hero_side_text'] ? $siteSettings['hero_side_text'] : 'Expert guidance. Genuine parts.' }}</h2><a href="{{ url('/contact-us') }}">Get a quotation</a></div>
                <div><span>Fast nationwide delivery</span><h2>Technology at your doorstep.</h2><a href="#products">Shop products</a></div>
            </aside>
        </section>

        <section id="categories" class="lt-section lt-container">
            <div class="lt-section-heading"><div><span>Browse quickly</span><h2>Featured Categories</h2></div></div>
            <div class="lt-category-grid">
                @forelse($featuredCategories as $category)
                    <a href="{{ url('/product-by-category/'.$category->category_id) }}"><i class="fa {{ $category->icon_class ?: 'fa-folder-open' }}"></i><span>{{ $category->category_name }}</span></a>
                @empty
                    <div class="lt-empty">Publish categories in the admin panel to populate this section.</div>
                @endforelse
            </div>
        </section>

        <section id="products" class="lt-section lt-products-section">
            <div class="lt-container">
                <div class="lt-section-heading"><div><span>Chosen for you</span><h2>Featured Products</h2></div><div class="lt-tabs" role="tablist"><button class="is-active" data-tab-button="featured" role="tab" aria-selected="true">Featured</button><button data-tab-button="latest" role="tab" aria-selected="false">Latest</button></div></div>
                <div class="lt-product-grid" data-tab-panel="featured">
                    @forelse($featuredProducts as $product) @include('partials.product-card', ['product' => $product]) @empty <div class="lt-empty">No products are marked as featured yet. Set <code>top_product</code> for products in admin/data.</div> @endforelse
                </div>
                <div class="lt-product-grid" data-tab-panel="latest" hidden>
                    @forelse($latestProducts as $product) @include('partials.product-card', ['product' => $product]) @empty <div class="lt-empty">Published products will appear here.</div> @endforelse
                </div>
            </div>
        </section>

        <section class="lt-usp"><div class="lt-container lt-usp-grid"><div><i class="fa fa-truck"></i><span><strong>Nationwide Delivery</strong>Fast and carefully handled</span></div><div><i class="fa fa-shield"></i><span><strong>Genuine Products</strong>Official warranty support</span></div><div><i class="fa fa-headphones"></i><span><strong>Expert Support</strong>Before and after purchase</span></div><div><i class="fa fa-credit-card"></i><span><strong>Secure Payment</strong>Safe and convenient</span></div></div></section>

        <section id="new-arrivals" class="lt-section lt-container">
            <div class="lt-section-heading"><div><span>Just landed</span><h2>New Arrivals</h2></div></div>
            <div class="lt-product-grid">
                @forelse($newArrivals as $product) @include('partials.product-card', ['product' => $product]) @empty <div class="lt-empty">No products are marked as new arrivals yet. Admin/data flagging is required.</div> @endforelse
            </div>
        </section>

        <section class="lt-section lt-brands-section"><div class="lt-container"><div class="lt-section-heading"><div><span>Shop trusted names</span><h2>Popular Brands</h2></div></div><div class="lt-brand-grid">@forelse($brands as $brand)<a href="{{ url('/all-manufacturer-by-id/'.$brand->manufacturer_id) }}"><span>{{ strtoupper(substr($brand->manufacturer_name, 0, 2)) }}</span>{{ $brand->manufacturer_name }}</a>@empty<div class="lt-empty">Published manufacturers will appear here.</div>@endforelse</div></div></section>
    </main>

    @include('partials.footer')
    <script src="{{ asset('js/ecommerce-home.js') }}" defer></script>
</body>
</html>
