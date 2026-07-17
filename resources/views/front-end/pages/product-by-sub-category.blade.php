@extends('front-end.master')
@section('main_content')
<section class="lt-section" style="padding-top:0">
    <div class="lt-section-heading"><div><span>Subcategory</span><h2>{{ $search_by_sub_category_name ? $search_by_sub_category_name->sub_category_name : 'Products' }}</h2></div></div>
    <div class="lt-product-grid">@forelse($all_sub_product_by_category as $product) @include('partials.product-card', ['product' => $product]) @empty <div class="lt-empty">No published products found.</div> @endforelse</div>
</section>
@endsection
