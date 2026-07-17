<!DOCTYPE html>
<html lang="en">
    <head>

        <!-- start: Meta -->
        <meta charset="utf-8">
        <title>@yield("title", $brandName.' Login')</title>
        <meta name="description" content="Bootstrap Metro Dashboard">
        <meta name="author" content="{{ $brandName }}">
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
    </style>

    <body>
        <div class="container-fluid-full">
            <div class="row-fluid">

                <div class="row-fluid">
                    <div class="login-box">
                        <div class="icons">
                            <a href="index.html"><i class="halflings-icon home"></i></a>
                            <a href="#"><i class="halflings-icon cog"></i></a>
                        </div>
                        <h2>Administrator sign in</h2>
                        <p>Use your administrator username and password. No email address is required.</p>
                        @if(session('message'))<div class="alert alert-success" role="status">{{ session('message') }}</div>@endif
                        @if(session('exception'))<div class="alert alert-error" role="alert">{{ session('exception') }}</div>@endif
                        @if($errors->any())<div class="alert alert-error" role="alert">{{ $errors->first() }}</div>@endif
                        {!! Form::open(['route' => 'admin.login.submit', 'method' => 'post']) !!}
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
                        {!! Form::close() !!}
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
