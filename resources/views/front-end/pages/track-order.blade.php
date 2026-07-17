@extends('front-end.master')
@section('title','Track Your Order | '.$brandName)
@section('meta_robots','noindex,nofollow')
@section('main_content')
<section class="lt-section"><div class="lt-success-card"><i class="fa fa-truck"></i><span>Order assistance</span><h1>Track your order</h1><p>Enter the order number from your confirmation and the phone number used at checkout.</p>@if($errors->any())<div class="lt-alert is-error">{{ $errors->first() }}</div>@endif<form class="lt-track-form" method="post" action="{{ route('orders.track') }}">{{ csrf_field() }}<label>Order number<input name="order_number" value="{{ old('order_number') }}" placeholder="LBD-XXXXXX-XXXXXX" required></label><label>Phone number<input name="phone" value="{{ old('phone') }}" required></label><button class="lt-primary-button">Track order</button></form></div></section>
@endsection
