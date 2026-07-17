<section id="slider">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
             
<div class="w3-content w3-section" style="max-width:1200px">
  <a href="{{ url('/product-by-category/37') }}"><img class="mySlides" src="{{asset('asset/front-end/img/home/pic 1.jpg')}}" style="width:100%"></a>
  <a href="{{ url('/product-by-category/37') }}"><img class="mySlides" src="{{asset('asset/front-end/img/home/pic 2.jpg')}}" style="width:100%"></a>
  <a href="{{ url('/product-by-category/37') }}"><img class="mySlides" src="{{asset('asset/front-end/img/home/pic 3.jpg')}}" style="width:100%"></a>
  <a href="{{ url('/product-by-category/37') }}"><img class="mySlides" src="{{asset('asset/front-end/img/home/pic 4.jpg')}}" style="width:100%"></a>
  <!--<img class="mySlides" src="{{asset('asset/front-end/img/home/pic 5.jpg')}}" style="width:100%">-->
</div>
<div style="padding: 12px 20px 8px;
    background: #f7f7f7;
    margin-top: 30px;
    border-radius: 33px;
    font-size: 16px;
    color: #444;
    min-height: 40px;">
    <marquee direction="left"><text> জরুরী সেবা প্রদানের জন্য আমাদের শপ খোলা রয়েছে। প্রয়োজনে কল করুন ০১৯১৪-৭১৭৩৪৯ অথবা ০১৬১২-৭১৭৩৪৯
নম্বরে    </text></marquee>
</div>

<script>
var myIndex = 0;
carousel();

function carousel() {
  var i;
  var x = document.getElementsByClassName("mySlides");
  for (i = 0; i < x.length; i++) {
    x[i].style.display = "none";  
  }
  myIndex++;
  if (myIndex > x.length) {myIndex = 1}    
  x[myIndex-1].style.display = "block";  
  setTimeout(carousel, 4000); // Change image every 4 seconds
}
</script>
                
                
                
                
                <!--<div id="slider-carousel" class="carousel slide" data-ride="carousel">-->
                <!--    <ol class="carousel-indicators">-->
                <!--        <li data-target="#slider-carousel" data-slide-to="0" class="active"></li>-->
                <!--        <li data-target="#slider-carousel" data-slide-to="1"></li>-->
                <!--        <li data-target="#slider-carousel" data-slide-to="2"></li>-->
                <!--    </ol>-->

                <!--    <div class="carousel-inner">-->
                <!--        <div class="item active">-->
                <!--            <div class="col-sm-12">-->
                <!--                <img src="{{asset('asset/front-end/img/home/pic 1.jpg')}}" class="girl img-responsive" alt="" />-->
                <!--            </div>-->
                <!--        </div>-->
                <!--        <div class="item">-->
                <!--            <div class="col-sm-12">-->
                <!--                <img src="{{asset('asset/front-end/img/home/pic 2.jpg')}}" class="girl img-responsive" alt="" />-->
                <!--            </div>-->
                <!--        </div>-->

                <!--        <div class="item">-->
                <!--            <div class="col-sm-12">-->
                <!--                <img src="{{asset('asset/front-end/img/home/pic 3.jpg')}}" class="girl img-responsive" alt="" />-->
                <!--            </div>-->
                <!--        </div>-->
                        
                <!--        <div class="item">-->
                <!--            <div class="col-sm-12">-->
                <!--                <img src="{{asset('asset/front-end/img/home/pic 4.jpg')}}" class="girl img-responsive" alt="" />-->
                <!--            </div>-->
                <!--        </div>-->

                <!--    </div>-->

                <!--    <a href="#slider-carousel" class="left control-carousel hidden-xs" data-slide="prev">-->
                <!--        <i class="fa fa-angle-left"></i>-->
                <!--    </a>-->
                <!--    <a href="#slider-carousel" class="right control-carousel hidden-xs" data-slide="next">-->
                <!--        <i class="fa fa-angle-right"></i>-->
                <!--    </a>-->
                <!--</div>-->

            </div>
        </div>
    </div>
</section>
