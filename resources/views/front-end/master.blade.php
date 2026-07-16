@include("front-end.components.head")
    <body>
        <!--header-->
        @include("front-end.components.header")
        <!--/header-->

        <!--slider-->
        @include("front-end.components.slider")
        <!--/slider-->
        <section>
            <div class="container">
                <div class="row">
                    <!--category-->
                    @include("front-end.components.category")
                    <!--End Category-->                
                    
                    <div class="col-sm-9 padding-right">
                        <!--features_items-->
                        @yield('main_content')
                        <!--features_items-->
                        
                        <!--recommended_items-->
                        @include("front-end.components.category-items")
                        <!--/recommended_items-->

                    </div>
                </div>
            </div>
        </section>

        <!--Footer-->
        @include("front-end.components.footer")
        <!--/Footer-->
        @include("front-end.components.script")


        
    </body>
</html>