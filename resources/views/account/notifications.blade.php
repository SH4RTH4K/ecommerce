@extends('layouts.app')
@section('title','Notifications | '.$brandName)
@section('content')
<section class="lt-section lt-account-page"><div class="lt-section-heading"><div><span>Your account</span><h1>Notifications</h1></div><form method="post" action="{{ route('account.notifications.readAll') }}">{{ csrf_field() }}<button class="lt-secondary-button">Mark all as read</button></form></div>
<div class="lt-account-list">@forelse($notifications as $notification)<a class="lt-account-order {{ $notification->read_at?'':'is-unread' }}" href="{{ route('account.notifications.read',$notification->id) }}"><div><strong>{{ $notification->title }}</strong><p>{{ $notification->message }}</p><small>{{ date('M j, Y g:i A',strtotime($notification->created_at)) }}</small></div><i class="fa fa-angle-right"></i></a>@empty<div class="lt-empty-state"><h2>No notifications yet</h2><p>Order updates will appear here.</p></div>@endforelse</div>{{ $notifications->links() }}</section>
@endsection
