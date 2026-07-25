@php
    $siteName = $brandName;
    $siteLogo = $brandLogo;
    $footerNameIsBengali = (bool)preg_match('/[\x{0980}-\x{09FF}]/u', $siteName);
    $footerTagline = (string)$siteSettings->get('site_tagline', '');
    $footerTaglineIsBengali = (bool)preg_match('/[\x{0980}-\x{09FF}]/u', $footerTagline);
    $copyright = isset($siteSettings['copyright_text']) && $siteSettings['copyright_text'] ? $siteSettings['copyright_text'] : '© {year} '.$siteName.'. All rights reserved.';
    $copyright = str_replace('{year}', date('Y'), $copyright);
    $socialLinks = [
        'facebook_url' => ['Facebook', 'fa-facebook'], 'instagram_url' => ['Instagram', 'fa-instagram'],
        'youtube_url' => ['YouTube', 'fa-youtube-play'], 'linkedin_url' => ['LinkedIn', 'fa-linkedin'],
        'twitter_url' => ['X / Twitter', 'fa-twitter']
    ];
@endphp
<footer class="lt-footer">
    <div class="lt-container lt-footer-grid">
        <div style="--brand-name-font-size:{{ $brandNameFontSize }}px;--brand-tagline-font-size:{{ $brandTaglineFontSize }}px">@if($hasCustomBrandLogo && $siteLogo)<img class="lt-footer-logo" src="{{ asset($siteLogo) }}" alt="{{ $siteName }}" style="filter:none">@else<span class="lt-footer-brand-name {{ $footerNameIsBengali ? 'is-bengali' : '' }}" @if($footerNameIsBengali) lang="bn" @endif>{{ $siteName }}</span>@endif<p>{{ isset($siteSettings['footer_description']) && $siteSettings['footer_description'] ? $siteSettings['footer_description'] : 'Your trusted destination for computers, networking equipment, accessories, and dependable after-sales service.' }}</p>@if($footerTagline)<p class="lt-footer-tagline {{ $footerTaglineIsBengali ? 'is-bengali' : '' }}" @if($footerTaglineIsBengali) lang="bn" @endif><strong>{{ $footerTagline }}</strong></p>@endif</div>
        <div><h3>Information</h3><a href="{{ url('/about-us') }}">About us</a><a href="{{ url('/contact-us') }}">Contact us</a><a href="{{ url('/terms&conditions') }}">Terms &amp; conditions</a></div>
        <div><h3>Customer service</h3><a href="{{ route('orders.track.form') }}">Track your order</a><a href="{{ route('service-claims.form') }}">Warranty &amp; service request</a><a href="#">Warranty policy</a><a href="#">Delivery information</a><a href="#">Returns &amp; refunds</a></div>
        <div><h3>Stay connected</h3><p><i class="fa fa-map-marker"></i> {{ isset($siteSettings['shop_address']) && $siteSettings['shop_address'] ? $siteSettings['shop_address'] : 'Dhaka, Bangladesh' }}</p><p><i class="fa fa-envelope"></i> {{ isset($siteSettings['support_email']) && $siteSettings['support_email'] ? $siteSettings['support_email'] : 'support@example.com' }}</p>@if(isset($siteSettings['business_hours']) && $siteSettings['business_hours'])<p><i class="fa fa-clock-o"></i> {{ $siteSettings['business_hours'] }}</p>@endif<div class="lt-social">@foreach($socialLinks as $key=>$social)@if(isset($siteSettings[$key]) && $siteSettings[$key])<a href="{{ $siteSettings[$key] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social[0] }}"><i class="fa {{ $social[1] }}"></i></a>@endif @endforeach</div></div>
    </div>
    <div class="lt-copyright"><div class="lt-container">{{ $copyright }}</div></div>
</footer>
