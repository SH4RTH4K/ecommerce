@extends('front-end.master')
@section('main_content')
<section class="lt-section lt-listing-page" style="padding-top:0">
    <nav class="lt-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ url('/') }}">Home</a>
        <i class="fa fa-angle-right"></i>
                <span>{{ $manufacturer->manufacturer_name ?? 'Brand products' }}</span>
                @if($subCategory)<i class="fa fa-angle-right"></i><span>{{ $subCategory->navbar_name ?: $subCategory->sub_category_name }}</span>@endif
    </nav>

    <div class="lt-page-hero">
        <div>
            <span>Brand</span>
            <h1>{{ $manufacturer->manufacturer_name ?? 'Products by manufacturer' }}</h1>
            <p>@if($subCategory)Published {{ $manufacturer->manufacturer_name }} products from this category are listed below.@else All published products from this brand are listed below so shoppers can compare price, stock, and features in one place.@endif</p>
        </div>
        <div class="lt-page-stats">
            <div class="lt-page-stat">
                <strong>{{ $all_manufacturer_by_id->count() }}</strong>
                <span>Products</span>
            </div>
        </div>
    </div>

    <div class="lt-product-grid">
        @forelse($all_manufacturer_by_id as $product)
            @include('partials.product-card', ['product' => $product])
        @empty
            <div class="lt-empty">No published products found.</div>
        @endforelse
    </div>
</section>
@endsection
