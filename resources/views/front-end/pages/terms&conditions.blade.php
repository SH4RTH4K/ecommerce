@extends('front-end.master')
@section('title', 'Warranty & Terms | '.$brandName)
@section('main_content')
<section class="lt-policy-page">
    <div class="lt-policy-heading"><span>Customer information</span><h1>Warranty, Service &amp; Terms</h1><p>Please review these conditions before purchasing, receiving, or submitting a product for service.</p></div>
    <div class="lt-policy-layout"><nav aria-label="Policy sections"><a href="#coverage">Warranty coverage</a><a href="#exclusions">Exclusions</a><a href="#service">Service process</a><a href="#delivery">Delivery &amp; inspection</a></nav><div class="lt-policy-content">
        <section id="coverage"><i class="fa fa-shield"></i><div><h2>Warranty Coverage</h2><p>Product warranty follows the applicable manufacturer or distributor policy and begins from the purchase date shown on the invoice. Keep the invoice and warranty documents for any service request.</p></div></section>
        <section id="exclusions"><i class="fa fa-exclamation-triangle"></i><div><h2>Warranty Exclusions</h2><ul><li>Unauthorized opening, repair, modification, or installation.</li><li>Accident, fire, lightning, voltage fluctuation, short circuit, water, or physical damage.</li><li>Broken panels, connectors, casing, or other visible physical damage.</li><li>Damage caused by misuse, improper installation, or unsuitable operating conditions.</li></ul></div></section>
        <section id="service"><i class="fa fa-wrench"></i><div><h2>Warranty Service Process</h2><ol><li>Contact our team with the invoice and product details.</li><li>Bring the product to our showroom or send it through a reliable courier.</li><li>Our team will inspect it and coordinate eligible service with the supplier.</li><li>Customers are responsible for courier and transport costs in both directions.</li></ol></div></section>
        <section id="delivery"><i class="fa fa-truck"></i><div><h2>Delivery &amp; Product Inspection</h2><p>Inspect the model, configuration, physical condition, and included accessories when receiving the product. Report any delivery-related issue immediately. After acceptance, configuration concerns remain subject to the invoice and warranty policy.</p></div></section>
    </div></div>
    <div class="lt-policy-help"><div><strong>Need clarification?</strong><span>Call +88 01612-717349 before submitting a product for service.</span></div><a href="{{ url('/contact-us') }}">Contact support</a></div>
</section>
@endsection
