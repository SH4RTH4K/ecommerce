@include("front-end.components.head")
@include("front-end.components.header")
<div class="col-sm-offset-1 col-sm-10">
    <div class="product-details"><!--product-details-->
        <div class="col-sm-5">
            <div class="view-product">
                <img src="{{asset($product_details->product_image)}}" alt="product_image">
                <!--                <h3>ZOOM</h3>-->
            </div>

        </div>
        <form name="manufacturer">
            <div class="col-sm-7">
                <div class="product-information"><!--/product-information-->
                    <img src="images/product-details/new.jpg" class="newarrival" alt="">
                    <h2 style="text-transform: uppercase">{{$product_details->product_name}}</h2>
                    <p>Product ID: {{$product_details->product_id}}</p>
                    <!--<img src="images/product-details/rating.png" alt="">-->
                    <span>
                        <span>BDT {{$product_details->product_price}}</span>
                        <!--                    <label>Quantity:</label>
                                            <input type="text" value="3">
                                            <button type="button" class="btn btn-fefault cart">
                                                <i class="fa fa-shopping-cart"></i>
                                                Add to cart
                                            </button>-->
                    </span>
    <!--                <p><b>Availability:</b> In Stock</p>-->
                    <?PHP 
                    $temp=$product_details->product_condition;
                     if($temp=="new"){
                         ?>
                    <p><b>Condition: </b><span style="color: green"> {{$product_details->product_condition}}</span></p>
                         <?PHP
                     }else
                     {
                         ?>
                    <p><b>Condition: </b><span style="color: red">{{$product_details->product_condition}}</span></p>
                    
                     <?PHP
                     }
                    ?>
                   
                    <div style="overflow-x:auto;">
                        <table>
                            <tr name="manufacturer_id">
                                <td>Brand</td>
                                <td  style="text-transform: uppercase">{{$product_details->manufacturer_name}}</td>

                            </tr>
                            <tr>
                                <td>Model</td>
                                <td>{{$product_details->product_model}}</td>
                            </tr>
                            <tr>
                                <td>Product Description</td>
                                <td>{!!$product_details->product_description!!}</td>
                            </tr>
                        </table>
                    </div>
                    <h2 style="color: red; padding-top: 20px;">For Order : +88 01774-014546</h2>


                    <!--<a href=""><img src="{{asset('asset/front-end/img/product-details/share.png')}}" class="share img-responsive" alt=""></a>-->
                </div><!--/product-information-->
            </div>
        </form>
    </div><!--/product-details-->




    <div class="recommended_items"><!--recommended_items-->
        <h2 class="title text-center">Experttechbd Warranty, Guaranty বিক্রয়োত্তর সেবা </h2>

        <div id="recommended-item-carousel" class="carousel slide" data-ride="carousel">
            <!--<p>* 1 Year Guaranty (Parts & Panel)</p>-->
            <p><span><strong>Warranty Guarantee Is Not Applicable Following Reason:</strong></span></p>
            <ol>
                <li>
                    If PC Open By Locally Or By Any Person.
                </li>
                <li>
                    Any Accidents, Lighting, Fire, Public Disturbance, Voltage Fluctuating.
                </li>
                <li>
                    If Panel Is Broken.
                </li>
                <li>
                    Defects Caused By Improper Use or Improper Installation.
                </li>
                <li>
                    Any Short Circuit Damage.
                </li>
            </ol>
            <p>
                <span>
                    <strong>For Guarantee & Warranty Service:</strong>
                </span>
            </p>
            <ol>
                <li>
                    Send Your PC to Our Shop Address (By Any Person)
                </li>
                <li>
                    Send Your PC by Any courier Service to Our Shop Address.
                </li>
                <li>
                    After Servicing (Guarantee & Warranty) We Will Send You.(Customer Will Bear All Up Down Cost Of The Product).
                </li>
                <li>
                    Mobile No: 01774-014546
                </li>
            </ol>
            <p>
                <span><strong>বিক্রয়োত্তর সেবা প্রদানের জন্য (Guaranty & Warranty):</strong></span>
            </p>
            <ol>
                <li>
                    আপনার কোন সার্ভিস প্রয়োজন হলে দয়া করে PC আমাদের দোকানের ঠিকানায় পাঠিয়ে দিবেন।
                </li>
                <li>
                    আপনি নিজে প্রোডাক্টটি নিয়ে আসতে পারেন আথবা, কোন কুরিয়ার সার্ভিসের মাধ্যমে আমাদের দোকানের ঠিকানায় প্রেরণ করুন।
                </li>
                <li>
                    আপনার সার্ভিসিং (Guarantee & Warranty) হয়ে গেলে আমরা আপনার নিকটস্থ কোন কুরিয়ার সার্ভিস সেন্টারে পাঠিয়ে দিবো। (পরিবহন সংক্রান্ত যাবতীয় খরচাদি ক্রেতাকে বহন করতে হবে) 
                </li>
            </ol>
            Please Check PC Then Receive Your Product. You Should/Will Receive Your Product At Your Own Risk.
            After Receiving The PC, Our Company Will Not Take Any Other Responsibility About Configuration Except The Service Issue.
            <br>
            দয়া করে প্রথমে যাচাই করুন অতঃপর আপনি আপনার নিজ দায়িত্বে আপনার পণ্যটি বুঝে নিন।
            <br>
            আপনার পণ্যটি বুঝে নেওয়ার পর থেকে কনফিগারেশন সম্পর্কিত কোন দায় দায়িত্ব আমাদের কোম্পানি গ্রহন করবে না। কিন্তু সার্ভিস সংক্রান্ত যে কোন সেবা আমাদের নির্দিষ্ট মেয়াদ পর্যন্ত প্রদান করা হবে।   
        </div>
    </div><!--/recommended_items-->
    <br>
</div>
@include("front-end.components.script")