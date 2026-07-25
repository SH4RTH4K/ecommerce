<!DOCTYPE html>
<html lang="en">
    <head>

        <!-- start: Meta -->
        <meta charset="utf-8">
        <title>@yield("title", $brandName.' Login')</title>
        <meta name="description" content="Bootstrap Metro Dashboard">
        <meta name="author" content="{{ $brandName }}">
        @if($brandFavicon)<link rel="icon" href="{{ asset($brandFavicon) }}">@endif
        <meta name="keyword" content="Metro, Metro UI, Dashboard, Bootstrap, Admin, Template, Theme, Responsive, Fluid, Retina">
        <!-- end: Meta -->

        <!-- start: Mobile Specific -->
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- end: Mobile Specific -->

        <!-- start: CSS -->
        <link id="bootstrap-style" href="{{asset('asset/expert-admin/css/bootstrap.min.css')}}" rel="stylesheet">
        <link href="{{asset('asset/expert-admin/css/bootstrap-responsive.min.css')}}" rel="stylesheet">
        <link id="base-style" href="{{asset('asset/expert-admin/css/style.css')}}" rel="stylesheet">
        <link id="base-style-responsive" href="{{asset('asset/expert-admin/css/style-responsive.css')}}" rel="stylesheet">
        <link href='http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800&subset=latin,cyrillic-ext,latin-ext' rel='stylesheet' type='text/css'>
        <!-- end: CSS -->





    </head>
    <style type="text/css">
        body { background: url({{asset('asset/expert-admin/img/bg-login.jpg')}}) !important; }
        .login-box .login-back-link { display:inline-flex;align-items:center;gap:6px;color:#71808b;font-size:12px;font-weight:600;text-decoration:none; }
        .login-box .login-back-link:hover,.login-box .login-back-link:focus { color:#1f6f96;text-decoration:none; }
        .admin-login-brand { margin:8px 0 22px;text-align:center; }
        .admin-login-brand img { display:block;max-width:220px;max-height:72px;width:auto;height:auto;object-fit:contain;margin:0 auto; }
        .admin-login-name { display:block;color:#164b6b;font-family:"Segoe UI","Noto Sans Bengali","Nirmala UI","Vrinda",Arial,sans-serif;font-size:var(--brand-name-font-size,23px);font-weight:800;letter-spacing:-.01em;line-height:1.2;overflow-wrap:anywhere; }
        .admin-login-name.is-bengali { font-family:"Noto Sans Bengali","Nirmala UI","Vrinda","Segoe UI",sans-serif;letter-spacing:0;line-height:1.4; }
        .admin-login-brand small { display:block;margin-top:7px;color:#7a8993;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase; }
        .admin-login-tagline { display:inline-block;max-width:260px;margin:8px auto 0;color:#345a72;font-family:"Segoe UI","Noto Sans Bengali","Nirmala UI","Vrinda",Arial,sans-serif;font-size:var(--brand-tagline-font-size,12px);font-weight:700;letter-spacing:.02em;line-height:1.45;overflow-wrap:anywhere;text-transform:none; }
        .admin-login-tagline.is-bengali { font-family:"Noto Sans Bengali","Nirmala UI","Vrinda","Segoe UI",sans-serif;letter-spacing:0;line-height:1.55; }
        .admin-login-tagline:before { display:inline-block;width:18px;height:2px;margin:0 7px 3px 0;border-radius:2px;background:#f5821f;content:""; }
    </style>

    <body>
        <div class="container-fluid-full">
            <div class="row-fluid">

                <div class="row-fluid">
                    <div class="login-box">
                        <div class="icons"><a class="login-back-link" href="{{ url('/') }}"><i class="halflings-icon home"></i><span>Back to storefront</span></a></div>
                        @php
                            $adminTagline = (string)$siteSettings->get('site_tagline', '');
                            $adminTaglineIsBengali = (bool)preg_match('/[\x{0980}-\x{09FF}]/u', $adminTagline);
                            $adminNameIsBengali = (bool)preg_match('/[\x{0980}-\x{09FF}]/u', $brandName);
                        @endphp
                        <div class="admin-login-brand" style="--brand-name-font-size:{{ $brandNameFontSize }}px;--brand-tagline-font-size:{{ $brandTaglineFontSize }}px">
                            @if($hasCustomBrandLogo)<img src="{{ asset($brandLogo) }}" alt="{{ $brandName }} logo">@else<strong class="admin-login-name {{ $adminNameIsBengali ? 'is-bengali' : '' }}" @if($adminNameIsBengali) lang="bn" @endif>{{ $brandName }}</strong>@endif
                            @if($adminTagline)<span class="admin-login-tagline {{ $adminTaglineIsBengali ? 'is-bengali' : '' }}" @if($adminTaglineIsBengali) lang="bn" @endif>{{ $adminTagline }}</span>@endif
                            <small>Administration</small>
                        </div>
                        <h2>Administrator sign in</h2>
                        <p>Use your administrator username and password. No email address is required.</p>
                        @if(session('message'))<div class="alert alert-success" role="status">{{ session('message') }}</div>@endif
                        @if(session('exception'))<div class="alert alert-error" role="alert">{{ session('exception') }}</div>@endif
                        @if($errors->any())<div class="alert alert-error" role="alert">{{ $errors->first() }}</div>@endif
                        <form action="{{ route('admin.login.submit') }}" method="POST">
                            @csrf
                            <fieldset>

                                <div class="input-prepend" title="Username">
                                    <span class="add-on"><i class="halflings-icon user"></i></span>
                                    <input class="input-large span10" name="username" id="username" type="text" value="{{ old('username') }}" autocomplete="username" placeholder="Username" required autofocus/>
                                </div>
                                <div class="clearfix"></div>

                                <div class="input-prepend" title="Password">
                                    <span class="add-on"><i class="halflings-icon lock"></i></span>
                                    <input class="input-large span10" name="password" id="password" type="password" autocomplete="current-password" placeholder="Password" required/>
                                </div>
                                <div class="clearfix"></div>

                                <label class="remember" for="remember"><input type="checkbox" id="remember" />Remember me</label>

                                <div class="button-login">	
                                    <button type="submit" class="btn btn-primary">Login</button>
                                </div>
                                <div class="clearfix"></div>
                        </form>
                        <hr>
                        <p><a href="{{ url('/login') }}">Customer account sign in</a></p>
                    </div><!--/span-->
                </div><!--/row-->


            </div><!--/.fluid-container-->

        </div><!--/fluid-row-->

        <!-- start: JavaScript-->

        <script src="{{asset('asset/expert-admin/js/jquery-1.9.1.min.js')}}"></script>
        <script src="{{asset('asset/expert-admin/js/jquery-migrate-1.0.0.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery-ui-1.10.0.custom.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.ui.touch-punch.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/modernizr.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/bootstrap.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.cookie.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/fullcalendar.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.dataTables.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/excanvas.js')}}"></script>
        <script src="{{asset('asset/expert-admin/js/jquery.flot.js')}}"></script>
        <script src="{{asset('asset/expert-admin/js/jquery.flot.pie.js')}}"></script>
        <script src="{{asset('asset/expert-admin/js/jquery.flot.stack.js')}}"></script>
        <script src="{{asset('asset/expert-admin/js/jquery.flot.resize.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.chosen.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.uniform.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.cleditor.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.noty.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.elfinder.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.raty.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.iphone.toggle.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.uploadify-3.1.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.gritter.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.imagesloaded.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.masonry.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.knob.modified.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/jquery.sparkline.min.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/counter.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/retina.js')}}"></script>

        <script src="{{asset('asset/expert-admin/js/custom.js')}}"></script>
        <!-- end: JavaScript-->

    </body>
</html>
