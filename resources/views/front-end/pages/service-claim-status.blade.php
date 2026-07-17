@extends('front-end.master')
@section('title','Service Request '.$claim->claim_number.' | '.$brandName)
@section('meta_robots','noindex,nofollow')
@section('main_content')
<section class="lt-section"><div class="lt-success-card"><i class="fa fa-wrench"></i><span>Service reference</span><h1>{{ $claim->claim_number }}</h1>@if(session('success'))<div class="lt-alert is-success">{{ session('success') }}</div>@endif<p><span class="lt-order-status is-{{ $claim->status }}">{{ ucwords(str_replace('_',' ',$claim->status)) }}</span></p><h2>{{ $claim->product_name }}</h2><p>{{ ucwords($claim->claim_type) }} · Submitted {{ date('M j, Y',strtotime($claim->created_at)) }}</p><p>{{ $claim->issue_description }}</p>@if($claim->admin_note)<div class="lt-alert"><strong>Service team update</strong><p>{{ $claim->admin_note }}</p></div>@endif<a class="lt-primary-button" href="{{ url('/') }}">Continue shopping</a></div></section>
@endsection
