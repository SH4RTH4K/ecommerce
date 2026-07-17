@extends('front-end.master')
@section('title', 'Contact Us | '.$brandName)
@section('main_content')
<section class="lt-contact-page">
    @include('partials.flash')
    <div class="lt-contact-hero">
        <div><span>We are here to help</span><h1>Contact {{ $brandName }}</h1><p>Need product advice, an order quotation, or after-sales support? Talk to our team.</p></div>
        <div class="lt-contact-hero-icon"><i class="fa fa-comments"></i></div>
    </div>

    <div class="lt-contact-cards">
        <a href="tel:{{ preg_replace('/[^+0-9]/', '', $siteSettings->get('support_phone', $siteSettings->get('phone'))) }}"><i class="fa fa-phone"></i><span><small>Call our team</small><strong>{{ $siteSettings->get('support_phone', $siteSettings->get('phone', 'Contact support')) }}</strong><em>Available during business hours</em></span></a>
        <a href="mailto:{{ $siteSettings->get('support_email', $siteSettings->get('email')) }}"><i class="fa fa-envelope"></i><span><small>Email us</small><strong>{{ $siteSettings->get('support_email', $siteSettings->get('email', 'Support email')) }}</strong><em>We will respond as soon as possible</em></span></a>
        <div><i class="fa fa-clock-o"></i><span><small>Business hours</small><strong>{{ $siteSettings->get('business_hours', 'Contact us for opening hours') }}</strong></span></div>
    </div>

    <div class="lt-contact-grid">
        <div class="lt-location-card">
            <span class="lt-contact-kicker">Visit our store</span>
            <h2>Find us</h2>
            <p>Our team can help you compare products, place an order, and arrange after-sales support.</p>
            <address><i class="fa fa-map-marker"></i><span><strong>{{ $brandName }}</strong>{{ $siteSettings->get('address', 'Store address is available from customer support.') }}</span></address>
            <a class="lt-directions" href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($siteSettings->get('address', $brandName)) }}" target="_blank" rel="noopener">Get directions <i class="fa fa-external-link"></i></a>
        </div>
        <div class="lt-contact-map">
            <iframe src="https://www.google.com/maps?q={{ urlencode($siteSettings->get('address', $brandName)) }}&amp;output=embed" title="{{ $brandName }} store location" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>

    <div class="lt-contact-support"><div><i class="fa fa-headphones"></i><span><strong>Looking for product support?</strong>Have your product model and invoice details ready so our team can assist you faster.</span></div><a href="tel:{{ preg_replace('/[^+0-9]/', '', $siteSettings->get('support_phone', $siteSettings->get('phone'))) }}">Call support</a></div>
    <div class="lt-support-form-wrap"><div><span class="lt-contact-kicker">Send a request</span><h2>How can we help?</h2><p>Submit the details below. Your request will enter our support inbox for follow-up.</p></div><form class="lt-feedback-form" method="post" action="{{ route('support.store') }}">{{ csrf_field() }}<div class="lt-inline-fields"><label>Name<input name="customer_name" value="{{ old('customer_name', optional(auth()->user())->name) }}" required></label><label>Email<input type="email" name="email" value="{{ old('email', optional(auth()->user())->email) }}" required></label></div><div class="lt-inline-fields"><label>Phone<input name="phone" value="{{ old('phone') }}"></label><label>Order number (optional)<input name="order_number" value="{{ old('order_number') }}"></label></div><label>Subject<input name="subject" value="{{ old('subject') }}" required></label><label>Message<textarea name="message" minlength="10" required>{{ old('message') }}</textarea></label><button class="lt-primary-button">Send support request</button></form></div>
</section>
@endsection
