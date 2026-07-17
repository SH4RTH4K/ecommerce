@extends('front-end.master')
@section('main_content')
<section class="lt-section" style="padding-top:0">
    <div class="lt-section-heading"><div><span>Catalog</span><h2>Search Results</h2></div></div>
    <div class="lt-product-grid">@forelse($search_product as $product) @include('partials.product-card', ['product' => $product]) @empty <div class="lt-empty">No matching products found.</div> @endforelse</div>
</section>
@endsection
