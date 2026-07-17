@extends('layouts.app')
@php $adminMode=request()->query('account')==='admin'; @endphp
@section('title', ($adminMode ? 'Administrator Sign In' : 'Sign In').' | '.$brandName)
@section('content')
<div class="lt-container lt-auth-wrap">
    <section class="lt-auth-message">
        <span>Welcome back</span>
        <h1>{{ $adminMode ? 'Manage your store securely.' : 'Your technology account, all in one place.' }}</h1>
        <p>{{ $adminMode ? 'Administrator access is separated from customer accounts and uses your assigned username.' : 'Sign in to manage your profile and keep your '.$brandName.' experience connected.' }}</p>
        <ul><li><i class="fa fa-check-circle"></i> {{ $adminMode ? 'Role-based administration' : 'Faster account access' }}</li><li><i class="fa fa-check-circle"></i> Secure authentication</li><li><i class="fa fa-check-circle"></i> {{ $adminMode ? 'Audited administrator activity' : 'Dedicated customer support' }}</li></ul>
    </section>
    <section class="lt-auth-card" aria-labelledby="login-title">
        <div class="lt-auth-icon"><i class="fa fa-user"></i></div>
        <div style="display:flex;border:1px solid #dbe3ea;border-radius:6px;overflow:hidden;margin-bottom:20px"><a href="{{ url('/login') }}" style="flex:1;text-align:center;padding:9px;{{ !$adminMode?'background:#0b3d62;color:white;font-weight:700;':'' }}">Customer</a><a href="{{ url('/login?account=admin') }}" style="flex:1;text-align:center;padding:9px;{{ $adminMode?'background:#0b3d62;color:white;font-weight:700;':'' }}">Administrator</a></div>
        <p class="lt-auth-kicker">{{ $adminMode ? 'Administrator portal' : 'Customer account' }}</p>
        <h2 id="login-title">{{ $adminMode ? 'Admin sign in' : 'Sign in' }}</h2>
        <p class="lt-auth-intro">{{ $adminMode ? 'Enter your username and password. No email is required.' : 'Enter your email and password to continue.' }}</p>
        @if(session('exception'))<div class="lt-field-error" role="alert" style="display:block;margin-bottom:14px">{{ session('exception') }}</div>@endif
        <form method="POST" action="{{ $adminMode ? route('admin.login.submit') : route('login') }}" class="lt-auth-form">
            {{ csrf_field() }}
            <div class="lt-field">
                @if($adminMode)
                    <label for="username">Administrator username</label>
                    <div class="lt-input-wrap"><i class="fa fa-user"></i><input id="username" type="text" class="{{ $errors->has('username') ? 'is-invalid' : '' }}" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus placeholder="Enter username"></div>
                    @if($errors->has('username'))<span class="lt-field-error" role="alert">{{ $errors->first('username') }}</span>@endif
                @else
                    <label for="email">Email address or administrator username</label>
                    <div class="lt-input-wrap"><i class="fa fa-envelope"></i><input id="email" type="text" class="{{ $errors->has('email') ? 'is-invalid' : '' }}" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="you@example.com or username"></div>
                    <span class="lt-auth-intro" style="display:block;margin-top:6px">A username without <strong>@</strong> is automatically sent to administrator sign in.</span>
                    @if($errors->has('email'))<span class="lt-field-error" role="alert">{{ $errors->first('email') }}</span>@endif
                @endif
            </div>
            <div class="lt-field">
                <div class="lt-label-row"><label for="password">Password</label>@if(!$adminMode)<a href="{{ route('password.request') }}">Forgot password?</a>@endif</div>
                <div class="lt-input-wrap"><i class="fa fa-lock"></i><input id="password" type="password" class="{{ $errors->has('password') ? 'is-invalid' : '' }}" name="password" autocomplete="current-password" required placeholder="Enter your password"><button type="button" class="lt-password-toggle" aria-label="Show password"><i class="fa fa-eye"></i></button></div>
                @if($errors->has('password'))<span class="lt-field-error" role="alert">{{ $errors->first('password') }}</span>@endif
            </div>
            @if(!$adminMode)<label class="lt-remember"><input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Remember me on this device</label>@endif
            <button type="submit" class="lt-auth-submit">{{ $adminMode ? 'Open dashboard' : 'Sign in' }} <i class="fa fa-arrow-right"></i></button>
        </form>
        @if(!$adminMode && Route::has('register'))<p class="lt-auth-switch">New to {{ $brandName }}? <a href="{{ route('register') }}">Create an account</a></p>@endif
    </section>
</div>
<script>document.addEventListener('DOMContentLoaded',function(){var button=document.querySelector('.lt-password-toggle');var input=document.getElementById('password');if(button&&input){button.addEventListener('click',function(){var show=input.type==='password';input.type=show?'text':'password';button.setAttribute('aria-label',show?'Hide password':'Show password');button.querySelector('i').className=show?'fa fa-eye-slash':'fa fa-eye';});}var form=document.querySelector('.lt-auth-form'),identifier=document.getElementById('email');if(form&&identifier){form.addEventListener('submit',function(){var value=identifier.value.trim();if(value&&value.indexOf('@')===-1){identifier.name='username';form.action=@json(route('admin.login.submit'));}});}});</script>
@endsection
