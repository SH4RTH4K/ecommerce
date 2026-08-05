@extends('front-end.master')
@section('title', 'Product Comparison | '.$brandName)
@section('meta_robots', 'noindex,nofollow')
@section('main_content')
<section class="lt-section lt-shop-page lt-listing-page">
    <div class="lt-page-hero">
        <div>
            <span>Choose confidently</span>
            <h1>Compare Products</h1>
            <p>Review specifications side by side so you can pick the model that fits your needs, budget, and warranty preference.</p>
        </div>
        <div class="lt-page-stats">
            <div class="lt-page-stat">
                <strong>{{ $items->count() }}</strong>
                <span>Products</span>
            </div>
            <div class="lt-page-stat">
                <strong>{{ $attributes->count() }}</strong>
                <span>Attributes</span>
            </div>
        </div>
    </div>

    @include('partials.flash')

    @if($items->isEmpty())
        <div class="lt-empty">No products selected for comparison.</div>
    @else
        <div class="lt-table-wrap">
            <table class="lt-shop-table lt-compare-table">
                <tbody>
                    <tr>
                        <th>Product</th>
                        @foreach($items as $item)
                            <td>
                                <img src="{{ $item['product']->image_url }}" alt="{{ $item['product']->product_name }}">
                                <h3>{{ $item['product']->product_name }}</h3>
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>Price</th>
                        @foreach($items as $item)
                            <td>
                                <strong>&#2547;{{ number_format($item['product']->selling_price) }}</strong>
                                @if($item['product']->has_offer)
                                    <br><del>&#2547;{{ number_format($item['product']->regular_price) }}</del>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>Model</th>
                        @foreach($items as $item)
                            <td>{{ $item['product']->product_model ?: '-' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>Availability</th>
                        @foreach($items as $item)
                            <td>{{ $item['product']->product_condition }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>Warranty</th>
                        @foreach($items as $item)
                            <td>{{ $item['product']->warranty ?: 'Contact us' }}</td>
                        @endforeach
                    </tr>
                    @foreach($attributes as $attribute)
                        <tr>
                            <th>{{ $attribute->name }}</th>
                            @foreach($items as $item)
                                @php($value = $item['product']->attributeValues->firstWhere('attribute_id', $attribute->id))
                                <td>{{ $value ? $value->display_value : '-' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr>
                        <th></th>
                        @foreach($items as $item)
                            <td>
                                <form action="{{ route('compare.remove', $item['product']->id) }}" method="post">
                                    {{ csrf_field() }}
                                    <button class="lt-link-button" type="submit">Remove</button>
                                </form>
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
