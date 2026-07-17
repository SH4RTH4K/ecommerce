<footer id="footer">
    <div class="footer-widget">
        <div class="container">
            <div class="row">
                <div class="col-sm-3">
                    <div class="single-widget">
                        <h2 style="text-align: center">Our Address</h2>
                        <ul class="nav nav-pills nav-stacked" style="text-align: center">
                            <li><a href="#"><strong style="font-size: 18px">{{ $brandName }}</strong></a></li>
                            <li><a href="#">Shop Number 17, 6th Floor, Shah Ali Plaza, Mirpur-10, Dhaka-1216 </a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6">
                    <div class="single-widget">
                        <h2>Quick Shop</h2>
                        <?php
                        $all_published_category = DB::table('category')
                                ->whereNotIn('category_id', [14, 15])
                                ->where('publication_status', 1)
                                ->limit(5)
                                ->get();
                        ?>
                        @foreach($all_published_category as $vcategory)
                        <ul class="nav nav-pills nav-stacked">
                            <li><a href="{{URL::to('/product-by-category/'.$vcategory->category_id)}}">{{$vcategory->category_name}}</a></li>
                        </ul>
                        @endforeach
                    </div>
                </div>
                <div class="col-sm-3 col-xs-6">
                    <div class="single-widget">
                        <h2>Policies</h2>
                        <ul class="nav nav-pills nav-stacked">
                            <li><a href="{{URL::to('/terms&conditions')}}">Terms of Use</a></li>
                            <li><a href="#">Privecy Policy</a></li>
                            <li><a href="#">Refund Policy</a></li>
                            <li><a href="#">Billing System</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="single-widget">
                        <h2>About {{ $brandName }}</h2>
                        <ul class="nav nav-pills nav-stacked">
                            <li><a href="{{URL::to('/about-us')}}">Company Information</a></li>
                            <li><a href="{{URL::to('/contact-us')}}">Store Location</a></li>
                            <li><a href="#">Careers</a></li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <div class="row">
                <p style="text-align: center">Copyright &copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.</p>
            </div>
        </div>
    </div>

</footer>
