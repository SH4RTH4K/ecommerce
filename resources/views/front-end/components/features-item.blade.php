@extends('front-end.master')
@section('main_content')
<section class="lt-section" style="padding-top:0">
    <div class="lt-section-heading"><div><span>Browse products</span><h2>Featured Items</h2></div></div>
    <div class="lt-product-grid">
        @forelse($all_published_product as $product)
            @include('partials.product-card', ['product' => $product])
        @empty
            <div class="lt-empty">No published products found.</div>
        @endforelse
    </div>
</section>
@endsection
