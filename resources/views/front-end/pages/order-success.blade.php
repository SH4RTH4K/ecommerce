@extends('front-end.master')
@section('title','Order Confirmed | '.$brandName)
@section('meta_robots','noindex,nofollow')
@section('main_content')
<section class="lt-section"><div class="lt-success-card"><i class="fa fa-check-circle"></i><span>Thank you</span><h1>Your order is confirmed</h1><p>Order number <strong>{{ $order->order_number }}</strong></p><p>We will contact you at {{ $order->phone }} to confirm delivery.</p><a class="lt-primary-button" href="{{ route('orders.invoice',$order->id) }}">View invoice</a> <a class="lt-secondary-button" href="{{ url('/') }}">Continue shopping</a></div></section>
@endsection
