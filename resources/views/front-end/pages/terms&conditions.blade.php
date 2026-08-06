@extends('front-end.master')
@section('title', 'Warranty & Terms | '.$brandName)
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

    $terms = [
        'hero' => [
            'kicker' => $pageSetting('terms_hero_kicker', 'Customer information'),
            'title' => $pageSetting('terms_hero_title', 'Warranty, Service & Terms'),
            'text' => $pageSetting('terms_hero_text', 'Please review these conditions before purchasing, receiving, or submitting a product for service.'),
        ],
        'nav' => [
            'coverage' => $pageSetting('terms_nav_coverage', 'Warranty coverage'),
            'exclusions' => $pageSetting('terms_nav_exclusions', 'Exclusions'),
            'service' => $pageSetting('terms_nav_service', 'Service process'),
            'delivery' => $pageSetting('terms_nav_delivery', 'Delivery & inspection'),
        ],
        'coverage' => [
            'title' => $pageSetting('terms_coverage_title', 'Warranty Coverage'),
            'text' => $pageSetting('terms_coverage_text', 'Product warranty follows the applicable manufacturer or distributor policy and begins from the purchase date shown on the invoice. Keep the invoice and warranty documents for any service request.'),
        ],
        'exclusions' => [
            'title' => $pageSetting('terms_exclusions_title', 'Warranty Exclusions'),
            'items' => $splitLines($pageSetting('terms_exclusions_items', "Unauthorized opening, repair, modification, or installation.\nAccident, fire, lightning, voltage fluctuation, short circuit, water, or physical damage.\nBroken panels, connectors, casing, or other visible physical damage.\nDamage caused by misuse, improper installation, or unsuitable operating conditions.")),
        ],
        'service' => [
            'title' => $pageSetting('terms_service_title', 'Warranty Service Process'),
            'items' => $splitLines($pageSetting('terms_service_items', "Contact our team with the invoice and product details.\nBring the product to our showroom or send it through a reliable courier.\nOur team will inspect it and coordinate eligible service with the supplier.\nCustomers are responsible for courier and transport costs in both directions.")),
        ],
        'delivery' => [
            'title' => $pageSetting('terms_delivery_title', 'Delivery & Product Inspection'),
            'text' => $pageSetting('terms_delivery_text', 'Inspect the model, configuration, physical condition, and included accessories when receiving the product. Report any delivery-related issue immediately. After acceptance, configuration concerns remain subject to the invoice and warranty policy.'),
        ],
        'help' => [
            'title' => $pageSetting('terms_help_title', 'Need clarification?'),
            'text' => $pageSetting('terms_help_text', 'Call +88 01612-717349 before submitting a product for service.'),
            'button_text' => $pageSetting('terms_help_button_text', 'Contact support'),
        ],
    ];
@endphp
<section class="lt-policy-page">
    <div class="lt-policy-heading"><span>{{ $terms['hero']['kicker'] }}</span><h1>{{ $terms['hero']['title'] }}</h1><div>{!! $renderRichText($terms['hero']['text']) !!}</div></div>
    <div class="lt-policy-layout"><nav aria-label="Policy sections"><a href="#coverage">{{ $terms['nav']['coverage'] }}</a><a href="#exclusions">{{ $terms['nav']['exclusions'] }}</a><a href="#service">{{ $terms['nav']['service'] }}</a><a href="#delivery">{{ $terms['nav']['delivery'] }}</a></nav><div class="lt-policy-content">
        <section id="coverage"><i class="fa fa-shield"></i><div><h2>{{ $terms['coverage']['title'] }}</h2><div>{!! $renderRichText($terms['coverage']['text']) !!}</div></div></section>
        <section id="exclusions"><i class="fa fa-exclamation-triangle"></i><div><h2>{{ $terms['exclusions']['title'] }}</h2><ul>@foreach($terms['exclusions']['items'] as $item)<li>{{ $item }}</li>@endforeach</ul></div></section>
        <section id="service"><i class="fa fa-wrench"></i><div><h2>{{ $terms['service']['title'] }}</h2><ol>@foreach($terms['service']['items'] as $item)<li>{{ $item }}</li>@endforeach</ol></div></section>
        <section id="delivery"><i class="fa fa-truck"></i><div><h2>{{ $terms['delivery']['title'] }}</h2><div>{!! $renderRichText($terms['delivery']['text']) !!}</div></div></section>
    </div></div>
    <div class="lt-policy-help"><div><strong>{{ $terms['help']['title'] }}</strong><div>{!! $renderRichText($terms['help']['text']) !!}</div></div><a href="{{ url('/contact-us') }}">{{ $terms['help']['button_text'] }}</a></div>
</section>
@endsection
