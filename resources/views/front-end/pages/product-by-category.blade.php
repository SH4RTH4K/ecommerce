@extends('front-end.master')
@section('title', $category->category_name.' | '.$brandName)
@section('meta_description', 'Shop '.$category->category_name.' products at '.$brandName.' with current prices, availability and product comparison.')
@section('canonical', url('/product-by-category/'.$category->category_id))
@section('main_content')
<section class="lt-section" style="padding-top:0">
    <nav class="lt-breadcrumb"><a href="{{ url('/') }}">Home</a><i class="fa fa-angle-right"></i><span>{{ $category->category_name }}</span></nav>
    <div class="lt-section-heading"><div><span>Shop smarter</span><h1>{{ $category->category_name }}</h1><p>{{ $products->total() }} products found</p></div></div>
    <div class="lt-catalog-layout">
        <aside class="lt-filter-panel">
            <form method="get">
                <div class="lt-filter-title"><h2>Filter products</h2><a href="{{ url('/product-by-category/'.$category->category_id) }}">Reset</a></div>
                <label>Search in category<input type="search" name="q" value="{{ request('q') }}" placeholder="Product or model"></label>
                <label>Brand<select name="manufacturer"><option value="">All brands</option>@foreach($manufacturers as $brand)<option value="{{ $brand->manufacturer_id }}" {{ (string) request('manufacturer') === (string) $brand->manufacturer_id ? 'selected' : '' }}>{{ $brand->manufacturer_name }}</option>@endforeach</select></label>
                @foreach($attributeFilters as $attribute)<label>{{ $attribute->name }}<select name="attributes[{{ $attribute->id }}]"><option value="">All</option>@foreach($attribute->values as $value)<option value="{{ $value }}" {{ request('attributes.'.$attribute->id)===$value?'selected':'' }}>{{ $value }}</option>@endforeach</select></label>@endforeach
                <div class="lt-price-fields"><label>Min price<input type="number" min="0" name="min_price" value="{{ request('min_price') }}"></label><label>Max price<input type="number" min="0" name="max_price" value="{{ request('max_price') }}"></label></div>
                <label class="lt-check"><input type="checkbox" name="availability" value="in-stock" {{ request('availability') === 'in-stock' ? 'checked' : '' }}> In stock only</label>
                <button class="lt-primary-button" type="submit">Apply filters</button>
            </form>
        </aside>
        <div class="lt-catalog-results">
            <form class="lt-sortbar" method="get">@foreach(request()->except(['sort','per_page','page']) as $name=>$value)@if(is_array($value))@foreach($value as $nestedKey=>$nestedValue)<input type="hidden" name="{{ $name }}[{{ $nestedKey }}]" value="{{ $nestedValue }}">@endforeach @else<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach<span>Showing {{ $products->firstItem() ?: 0 }}–{{ $products->lastItem() ?: 0 }} of {{ $products->total() }}</span><label>Sort<select name="sort" onchange="this.form.submit()"><option value="">Newest</option><option value="price-asc" {{ request('sort')==='price-asc'?'selected':'' }}>Price: low to high</option><option value="price-desc" {{ request('sort')==='price-desc'?'selected':'' }}>Price: high to low</option><option value="name" {{ request('sort')==='name'?'selected':'' }}>Name A–Z</option></select></label><label>Show<select name="per_page" onchange="this.form.submit()">@foreach([12,24,48] as $size)<option value="{{ $size }}" {{ $products->perPage()===$size?'selected':'' }}>{{ $size }}</option>@endforeach</select></label></form>
            <div class="lt-product-grid">@forelse($products as $product) @include('partials.product-card', ['product' => $product]) @empty <div class="lt-empty">No products match these filters.</div> @endforelse</div>
            <div class="lt-pagination">{{ $products->links() }}</div>
        </div>
    </div>
</section>
@endsection
