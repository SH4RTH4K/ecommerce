@extends('front-end.master')
@section('title', 'About Us | '.$brandName)
@section('main_content')
@php
    $settings = $siteSettings ?? collect();
    $pageSetting = function ($key, $default = '') use ($settings) {
        $value = trim((string) data_get($settings, $key, ''));
        return $value === '' ? $default : $value;
    };
    $splitLines = function ($value) {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(function ($line) {
                return trim($line);
            })
            ->filter()
            ->values();
    };
    $renderRichText = function ($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        return preg_match('/<[^>]+>/', $value) ? $value : nl2br(e($value));
    };

    $aboutUs = [
        'hero' => [
            'kicker' => $pageSetting('about_us_hero_kicker', 'About our store'),
            'title' => $pageSetting('about_us_hero_title', 'A flexible ecommerce experience built around customers.'),
            'text' => $pageSetting('about_us_hero_text', $brandName.' presents products clearly, accepts orders securely, and provides dependable customer service.'),
        ],
        'story' => [
            'kicker' => $pageSetting('about_us_story_kicker', 'Our approach'),
            'title' => $pageSetting('about_us_story_title', 'Clear information and practical service'),
            'text_1' => $pageSetting('about_us_story_text_1', 'This storefront can be customized for different industries, product catalogs, delivery areas, and business models.'),
            'text_2' => $pageSetting('about_us_story_text_2', 'Store owners can configure branding, contact details, policies, products, inventory, payments, and customer support from the administration dashboard.'),
        ],
        'highlights' => [
            [
                'title' => $pageSetting('about_us_highlight_1_title', 'Flexible'),
                'text' => $pageSetting('about_us_highlight_1_text', 'Configurable catalog and branding'),
            ],
            [
                'title' => $pageSetting('about_us_highlight_2_title', 'Responsive'),
                'text' => $pageSetting('about_us_highlight_2_text', 'Shopping across devices'),
            ],
            [
                'title' => $pageSetting('about_us_highlight_3_title', 'Customer first'),
                'text' => $pageSetting('about_us_highlight_3_text', 'Support before and after purchase'),
            ],
        ],
        'values' => [
            [
                'icon' => 'fa-bullseye',
                'title' => $pageSetting('about_us_mission_title', 'Our Mission'),
                'text' => $pageSetting('about_us_mission_text', 'Make dependable technology accessible through honest advice, suitable products, and responsive service.'),
            ],
            [
                'icon' => 'fa-eye',
                'title' => $pageSetting('about_us_vision_title', 'Our Vision'),
                'text' => $pageSetting('about_us_vision_text', 'Become a trusted technology partner for households, professionals, and growing organizations.'),
            ],
            [
                'icon' => 'fa-handshake-o',
                'title' => $pageSetting('about_us_promise_title', 'Our Promise'),
                'text' => $pageSetting('about_us_promise_text', 'Put customer needs first and recommend products with value, quality, and long-term usability in mind.'),
            ],
        ],
        'capabilities' => [
            'kicker' => $pageSetting('about_us_capabilities_kicker', 'What we provide'),
            'title' => $pageSetting('about_us_capabilities_title', 'Products and expertise for every setup'),
            'text' => $pageSetting('about_us_capabilities_text', 'Our catalog covers desktops, laptops, monitors, networking products, printers, office equipment, cameras, security systems, accessories, and PC components from established brands.'),
            'items' => $splitLines($pageSetting('about_us_capabilities_items', "Personal and custom PC solutions\nNetworking and office hardware\nCorporate procurement support\nNationwide product delivery")),
        ],
        'cta' => [
            'title' => $pageSetting('about_us_cta_title', 'Need help choosing the right product?'),
            'text' => $pageSetting('about_us_cta_text', 'Tell our team what you need and we will help you compare suitable options.'),
            'button_text' => $pageSetting('about_us_cta_button_text', 'Talk to our team'),
        ],
    ];
@endphp
<section class="lt-info-page">
    <div class="lt-info-hero"><div><span>{{ $aboutUs['hero']['kicker'] }}</span><h1>{{ $aboutUs['hero']['title'] }}</h1><div>{!! $renderRichText($aboutUs['hero']['text']) !!}</div></div><div class="lt-info-mark"><i class="fa fa-shopping-bag"></i></div></div>
    <div class="lt-story-grid"><div><span class="lt-info-kicker">{{ $aboutUs['story']['kicker'] }}</span><h2>{{ $aboutUs['story']['title'] }}</h2><div>{!! $renderRichText($aboutUs['story']['text_1']) !!}</div><div>{!! $renderRichText($aboutUs['story']['text_2']) !!}</div></div><aside>@foreach($aboutUs['highlights'] as $highlight)<strong>{{ $highlight['title'] }}</strong><span>{{ $highlight['text'] }}</span>@endforeach</aside></div>
    <div class="lt-values-grid">@foreach($aboutUs['values'] as $value)<article><i class="fa {{ $value['icon'] }}"></i><h3>{{ $value['title'] }}</h3><div>{!! $renderRichText($value['text']) !!}</div></article>@endforeach</div>
    <div class="lt-capabilities"><div><span class="lt-info-kicker">{{ $aboutUs['capabilities']['kicker'] }}</span><h2>{{ $aboutUs['capabilities']['title'] }}</h2><div>{!! $renderRichText($aboutUs['capabilities']['text']) !!}</div><a href="{{ url('/#categories') }}">Explore categories <i class="fa fa-arrow-right"></i></a></div><ul>@foreach($aboutUs['capabilities']['items'] as $item)<li><i class="fa fa-check"></i> {{ $item }}</li>@endforeach</ul></div>
    <div class="lt-info-cta"><div><h2>{{ $aboutUs['cta']['title'] }}</h2><div>{!! $renderRichText($aboutUs['cta']['text']) !!}</div></div><a href="{{ url('/contact-us') }}">{{ $aboutUs['cta']['button_text'] }}</a></div>
</section>
@endsection
