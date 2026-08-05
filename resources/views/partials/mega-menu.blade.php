<nav id="main-menu" class="lt-mega lt-startech-menu" aria-label="Primary navigation">
    <div class="lt-container lt-mega-inner">
        @php
            $menuLinks = [
                ['label' => 'Offers', 'href' => url('/#offers'), 'icon' => 'fa-tag'],
                ['label' => 'Latest Offers', 'href' => url('/#latest-offers'), 'icon' => 'fa-bolt'],
                ['label' => 'Happy Hour Special Deals', 'href' => url('/#offers'), 'icon' => 'fa-fire'],
                ['label' => 'Compare', 'href' => route('compare.index'), 'icon' => 'fa-exchange'],
                ['label' => 'Track Order', 'href' => route('orders.track.form'), 'icon' => 'fa-truck'],
                ['label' => 'About Us', 'href' => url('/about-us'), 'icon' => 'fa-info-circle'],
                ['label' => 'Contact Us', 'href' => url('/contact-us'), 'icon' => 'fa-phone'],
            ];
        @endphp
        <div class="lt-menu-strip" aria-label="Featured links">
            @foreach($menuLinks as $link)
                <a class="lt-menu-pill" href="{{ $link['href'] }}"><i class="fa {{ $link['icon'] }}"></i><span>{{ $link['label'] }}</span></a>
            @endforeach
        </div>
        <a class="lt-nav-cta" href="{{ route('pc-builder.index') }}">
            <i class="fa fa-wrench"></i>
            <span>PC Builder</span>
        </a>
    </div>
</nav>
