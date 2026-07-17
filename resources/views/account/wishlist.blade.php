@extends('layouts.app')
@section('title','My Wishlist | '.$brandName)
@section('content')
<section class="lt-section lt-account-page"><div class="lt-section-heading"><div><span>Your account</span><h1>My Wishlist</h1><p>Products saved for later.</p></div><a class="lt-secondary-button" href="{{ route('saved-builds.index') }}">Saved PC builds</a></div>@if(session('success'))<div class="lt-alert is-success">{{ session('success') }}</div>@endif @if($products->isEmpty())<div class="lt-empty">Your wishlist is empty. <a href="{{ url('/') }}">Browse products</a></div>@else<div class="lt-product-grid">@foreach($products as $product)<div>@include('partials.product-card',['product'=>$product])<form method="post" action="{{ route('wishlist.remove',$product->id) }}">{{ csrf_field() }}<button class="lt-secondary-button" style="width:100%;border:0">Remove from wishlist</button></form></div>@endforeach</div><div class="lt-pagination">{{ $products->links() }}</div>@endif</section>
@endsection
