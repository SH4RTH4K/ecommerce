@extends('front-end.master')
@section('main_content')
<section class="lt-section lt-listing-page" style="padding-top:0">
    <nav class="lt-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ url('/') }}">Home</a>
        <i class="fa fa-angle-right"></i>
        <span>{{ $search_by_sub_category_name ? $search_by_sub_category_name->sub_category_name : 'Subcategory' }}</span>
    </nav>

    <div class="lt-page-hero">
        <div>
            <span>Subcategory</span>
            <h1>{{ $search_by_sub_category_name ? $search_by_sub_category_name->sub_category_name : 'Products' }}</h1>
            <p>Explore all published products in this subcategory and open each product page for price, stock, and specifications.</p>
        </div>
        <div class="lt-page-stats">
            <div class="lt-page-stat">
                <strong>{{ $all_sub_product_by_category->count() }}</strong>
                <span>Products</span>
            </div>
        </div>
    </div>

    <div class="lt-product-grid">
        @forelse($all_sub_product_by_category as $product)
            @include('partials.product-card', ['product' => $product])
        @empty
            <div class="lt-empty">No published products found.</div>
        @endforelse
    </div>
</section>
@endsection
