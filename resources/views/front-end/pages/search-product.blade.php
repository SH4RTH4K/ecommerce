@extends('front-end.master')
@section('title', (trim($search_term) ? 'Search: '.$search_term : 'Search Results').' | '.$brandName)
@section('meta_description', trim($search_term) ? 'Search results for '.$search_term.' at '.$brandName.'.' : 'Search results at '.$brandName.'.')
@section('main_content')
<section class="lt-section lt-listing-page" style="padding-top:0">
    <nav class="lt-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ url('/') }}">Home</a>
        <i class="fa fa-angle-right"></i>
        <span>Search results</span>
    </nav>

    <div class="lt-page-hero">
        <div>
            <span>Search</span>
            <h1>{{ trim($search_term) ?: 'Products' }}</h1>
            <p>{{ $search_product->count() }} products matched your search term.</p>
        </div>
        <div class="lt-page-stats">
            <div class="lt-page-stat">
                <strong>{{ $search_product->count() }}</strong>
                <span>Matches</span>
            </div>
        </div>
    </div>

    <div class="lt-product-grid">
        @forelse($search_product as $product)
            @include('partials.product-card', ['product' => $product])
        @empty
            <div class="lt-empty">No matching products found.</div>
        @endforelse
    </div>
</section>
@endsection
