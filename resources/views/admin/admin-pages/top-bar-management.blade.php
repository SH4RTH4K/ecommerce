@extends('admin.admin-master')
@section('title', 'Top Bar & Contacts - '.$brandName)
@section('admin_main_content')
<div id="content" class="span10">
    <ul class="breadcrumb"><li><i class="icon-home"></i> Admin <i class="icon-angle-right"></i></li><li>Top Bar &amp; Contacts</li></ul>
    @if(session('message'))<div class="alert alert-success"><strong>Saved.</strong> {{ session('message') }}</div>@endif
    @if($errors->any())<div class="alert alert-error"><strong>Some information needs attention.</strong><ul style="margin-bottom:0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div style="margin-bottom:16px"><a class="btn" href="{{ url('/site-customization') }}"><i class="icon-cogs"></i> Website settings</a></div>
    @include('admin.components.top-bar-manager')
</div>
@endsection
