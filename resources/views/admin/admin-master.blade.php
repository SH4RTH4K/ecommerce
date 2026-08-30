<!DOCTYPE html>
<html lang="en">
    @include('admin.components.head')

    <body>
        <!-- start: Header -->
        @include('admin.components.admin-header')
        <!-- start: Header -->

        <div class="container-fluid-full">
            <div class="row-fluid">

                <!-- start: Main Menu -->
                @include('admin.components.main-menu')
                <!-- end: Main Menu -->

                <noscript>
                <div class="alert alert-block span10">
                    <h4 class="alert-heading">Warning!</h4>
                    <p>You need to have <a href="http://en.wikipedia.org/wiki/JavaScript" target="_blank">JavaScript</a> enabled to use this site.</p>
                </div>
                </noscript>

                <!-- start: Content -->
                @yield('admin_main_content')
                <!-- end: Content -->
            </div><!--/#content.span10-->
        </div><!--/fluid-row-->

        <div class="modal hide fade" id="myModal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h3>Settings</h3>
            </div>
            <div class="modal-body">
                <p>Here settings can be configured...</p>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn" data-dismiss="modal">Close</a>
                <a href="#" class="btn btn-primary">Save changes</a>
            </div>
        </div>

        <div class="clearfix"></div>

<!--        start footers-->
            @include('admin.components.footer')
<!--        end footer-->

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

        <script src="{{ asset('asset/expert-admin/js/specification-paste.js') }}?v={{ filemtime(public_path('asset/expert-admin/js/specification-paste.js')) }}"></script>
        <script src="{{ asset('asset/expert-admin/js/custom.js') }}?v={{ filemtime(public_path('asset/expert-admin/js/custom.js')) }}"></script>
        <!-- end: JavaScript-->

    </body>
</html>
