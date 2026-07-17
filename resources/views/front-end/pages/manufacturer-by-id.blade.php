@extends('front-end.master')
@section('main_content')
<section class="lt-section" style="padding-top:0">
    <div class="lt-section-heading"><div><span>Brand</span><h2>Products by Manufacturer</h2></div></div>
    <div class="lt-product-grid">@forelse($all_manufacturer_by_id as $product) @include('partials.product-card', ['product' => $product]) @empty <div class="lt-empty">No published products found.</div> @endforelse</div>
</section>
@endsection
