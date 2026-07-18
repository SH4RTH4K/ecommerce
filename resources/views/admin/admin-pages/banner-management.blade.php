@extends('admin.admin-master')
@section('admin_main_content')
<div id="content" class="span10">
    <ul class="breadcrumb"><li><i class="icon-home"></i> Admin <i class="icon-angle-right"></i></li><li>Homepage Banners</li></ul>
    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if(session('exception'))<div class="alert alert-error">{{ session('exception') }}</div>@endif
    @if($errors->any())<div class="alert alert-error"><strong>Please correct the banner:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div style="margin-bottom:16px"><a class="btn" href="{{ url('/site-customization') }}"><i class="icon-cogs"></i> Website settings</a></div>
    @include('admin.components.banner-manager')
</div>
@endsection
