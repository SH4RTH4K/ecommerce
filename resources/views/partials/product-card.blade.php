@php
    $productName = $product->product_name;
    $productPrice = $product->selling_price;
    $discount = isset($product->discount_percent) ? $product->discount_percent : null;
    $imageUrl = isset($product->image_url) ? $product->image_url : asset($product->product_image ?: 'asset/front-end/img/home/pic 1.jpg');
@endphp
<article class="lt-product-card">
    <a class="lt-product-image" href="{{ url('/product-details/'.$product->id) }}">
        @if($discount)
            <span class="lt-discount">-{{ $discount }}%</span>
        @endif
        <img src="{{ $imageUrl }}" alt="{{ $productName }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('asset/front-end/img/home/pic 1.jpg') }}';">
    </a>
    <div class="lt-product-body">
        <p class="lt-model">{{ $product->product_model ?: $brandName }}</p>
        <h3><a href="{{ url('/product-details/'.$product->id) }}">{{ $productName }}</a></h3>
        <div class="lt-price">
            <strong>&#2547;{{ number_format($productPrice) }}</strong>
            @if($product->has_offer)
                <del>&#2547;{{ number_format($product->regular_price) }}</del>
            @endif
        </div>
        <div class="lt-card-actions">
            <form action="{{ route('cart.add', $product->id) }}" method="post">{{ csrf_field() }}<button type="submit"><i class="fa fa-shopping-cart" aria-hidden="true"></i><span>Add to cart</span></button></form>
            <form action="{{ route('compare.add', $product->id) }}" method="post">{{ csrf_field() }}<button type="submit" aria-label="Compare"><i class="fa fa-exchange" aria-hidden="true"></i><span>Compare</span></button></form>
            <form action="{{ route('wishlist.add', $product->id) }}" method="post">{{ csrf_field() }}<button type="submit" aria-label="Save to wishlist"><i class="fa fa-heart-o" aria-hidden="true"></i><span>Favorite</span></button></form>
        </div>
        <a class="lt-view-product" href="{{ url('/product-details/'.$product->id) }}">View details <i class="fa fa-arrow-right"></i></a>
    </div>
</article>
