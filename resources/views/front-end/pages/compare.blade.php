@extends('front-end.master')
@section('title', 'Product Comparison | '.$brandName)
@section('meta_robots', 'noindex,nofollow')
@section('main_content')
<section class="lt-section lt-shop-page"><div class="lt-section-heading"><div><span>Choose confidently</span><h1>Compare Products</h1></div></div>
@include('partials.flash')
@if($items->isEmpty())<div class="lt-empty">No products selected for comparison.</div>@else
<div class="lt-table-wrap"><table class="lt-shop-table lt-compare-table"><tbody>
<tr><th>Product</th>@foreach($items as $item)<td><img src="{{ $item['product']->image_url }}" alt="{{ $item['product']->product_name }}"><h3>{{ $item['product']->product_name }}</h3></td>@endforeach</tr>
<tr><th>Price</th>@foreach($items as $item)<td><strong>৳{{ number_format($item['product']->selling_price) }}</strong>@if($item['product']->has_offer)<br><del>৳{{ number_format($item['product']->regular_price) }}</del>@endif</td>@endforeach</tr>
<tr><th>Model</th>@foreach($items as $item)<td>{{ $item['product']->product_model ?: '—' }}</td>@endforeach</tr>
<tr><th>Availability</th>@foreach($items as $item)<td>{{ $item['product']->product_condition }}</td>@endforeach</tr>
<tr><th>Warranty</th>@foreach($items as $item)<td>{{ $item['product']->warranty ?: 'Contact us' }}</td>@endforeach</tr>
@foreach($attributes as $attribute)<tr><th>{{ $attribute->name }}</th>@foreach($items as $item)@php $value=$item['product']->attributeValues->firstWhere('attribute_id',$attribute->id); @endphp<td>{{ $value ? $value->display_value : '—' }}</td>@endforeach</tr>@endforeach
<tr><th></th>@foreach($items as $item)<td><form action="{{ route('compare.remove', $item['product']->id) }}" method="post">{{ csrf_field() }}<button class="lt-link-button">Remove</button></form></td>@endforeach</tr>
</tbody></table></div>@endif</section>
@endsection
