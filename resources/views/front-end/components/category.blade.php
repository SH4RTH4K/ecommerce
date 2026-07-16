<div class="col-sm-3">
    <div class="left-sidebar">
        <h2>Category</h2>
        <div class="panel-group category-products" id="accordian"><!--category-productsr-->

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordian" href="#sportswear" class="collapsed">
                            <span class="badge pull-right"><i class="fa fa-plus"></i></span>
                            Computer
                        </a>
                    </h4>
                </div>
                <div id="sportswear" class="panel-collapse collapse" style="height: 0px;">
                    <div class="panel-body">
                        <?php
                        $sub_category = DB::table('sub_category')
                                ->where('category_id', 14)
                                ->get();
                        ?>
                        @foreach($sub_category as $vcategory)
                        <ul>
                            <li><a href="#">{{$vcategory->sub_category_name}} </a></li>

                        </ul>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordian" href="#mens" class="collapsed">
                            <span class="badge pull-right"><i class="fa fa-plus"></i></span>
                            Used Product
                        </a>
                    </h4>
                </div>
                <div id="mens" class="panel-collapse collapse" style="height: 0px;">
                    <div class="panel-body">
                        <?php
                        $sub_category = DB::table('sub_category')
                                ->where('category_id', 15)
                                ->get();
                        ?>
                        @foreach($sub_category as $vcategory)
                        <ul>
                            <li><a href="#">{{$vcategory->sub_category_name}} </a></li>

                        </ul>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="panel panel-default">
                <?php
                $all_published_category=DB::table('category')
                        ->whereNotIn('category_id',[14 ,15])
                        ->get();
                ?>
                @foreach($all_published_category as $vcategory)
                <div class="panel-heading">
                    <h4 class="panel-title"><a href="#">{{$vcategory->category_name}}</a></h4>
                </div>
                @endforeach
            </div>
        </div><!--/category-products-->

        <div class="brands_products"><!--brands_products-->
            <h2>Brands</h2>
            <div class="brands-name">
                <ul class="nav nav-pills nav-stacked">
                    <li><a href="#"> <span class="pull-right">(50)</span>LG</a></li>
                    <li><a href="#"> <span class="pull-right">(56)</span>HP</a></li>
                    <li><a href="#"> <span class="pull-right">(27)</span>ASUS</a></li>
                    <li><a href="#"> <span class="pull-right">(32)</span>lenovo</a></li>
                    <li><a href="#"> <span class="pull-right">(5)</span>Walton</a></li>
                    <li><a href="#"> <span class="pull-right">(9)</span>TP Link</a></li>
                    <li><a href="#"> <span class="pull-right">(4)</span>Netgear</a></li>
                </ul>
            </div>
        </div><!--/brands_products-->

        <div class="price-range"><!--price-range-->
            <h2>Price Range</h2>
            <div class="well text-center">
                <div class="slider slider-horizontal" style="width: 176px;"><div class="slider-track"><div class="slider-selection" style="left: 41.6667%; width: 33.3333%;"></div><div class="slider-handle round left-round" style="left: 41.6667%;"></div><div class="slider-handle round" style="left: 75%;"></div></div><div class="tooltip top" style="top: -30px; left: 70.1667px;"><div class="tooltip-arrow"></div><div class="tooltip-inner">250 : 450</div></div><input type="text" class="span2" value="" data-slider-min="0" data-slider-max="600" data-slider-step="5" data-slider-value="[250,450]" id="sl2" style=""></div><br>
                <b class="pull-left">$ 0</b> <b class="pull-right">$ 600</b>
            </div>
        </div><!--/price-range-->

        <div class="shipping text-center"><!--shipping-->
            <img src="http://www.experttechbd.com/asset/front-end/img/home/.jpg" alt="">
        </div><!--/shipping-->

    </div>
</div>