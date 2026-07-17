@extends('layouts.app')
@section('title','My Returns | '.$brandName)
@section('content')
<section class="lt-section lt-account-page"><div class="lt-section-heading"><div><span>After-sales</span><h1>My Returns</h1><p>Track return approvals, receiving, and refunds.</p></div><a class="lt-secondary-button" href="{{ route('account.orders') }}">My Orders</a></div>
@if(session('success'))<div class="lt-alert is-success">{{ session('success') }}</div>@endif
@if($returns->isEmpty())<div class="lt-empty">You have no return requests.</div>@else<div class="lt-table-wrap"><table class="lt-shop-table"><thead><tr><th>Return</th><th>Order</th><th>Date</th><th>Requested</th><th>Status</th><th></th></tr></thead><tbody>@foreach($returns as $return)<tr><td><strong>{{ $return->return_number }}</strong></td><td>#{{ $return->order_id }}</td><td>{{ date('M j, Y',strtotime($return->created_at)) }}</td><td>৳{{ number_format($return->requested_amount,2) }}</td><td><span class="lt-order-status is-{{ $return->status }}">{{ ucfirst($return->status) }}</span></td><td><a href="{{ route('account.returns.show',$return->id) }}">View</a></td></tr>@endforeach</tbody></table></div>{{ $returns->links() }}@endif</section>
@endsection
