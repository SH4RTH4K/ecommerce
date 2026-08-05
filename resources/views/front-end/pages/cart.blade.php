@extends('front-end.master')
@section('title', 'Shopping Cart | '.$brandName)
@section('meta_robots', 'noindex,nofollow')
@section('main_content')
@php($cartTotal = 0)
@php($cartQuantity = 0)
@foreach($items as $item)
    @php($cartQuantity += $item['quantity'])
    @php($cartTotal += $item['product']->selling_price * $item['quantity'])
@endforeach
<section class="lt-section lt-shop-page lt-listing-page">
    <div class="lt-page-hero">
        <div>
            <span>Your order</span>
            <h1>Shopping Cart</h1>
            <p>Review the products in your cart, update quantities, or continue to checkout when you are ready.</p>
        </div>
        <div class="lt-page-stats">
            <div class="lt-page-stat">
                <strong>{{ $cartQuantity }}</strong>
                <span>Items</span>
            </div>
            <div class="lt-page-stat">
                <strong>&#2547;{{ number_format($cartTotal) }}</strong>
                <span>Subtotal</span>
            </div>
        </div>
    </div>

    @include('partials.flash')

    @if($items->isEmpty())
        <div class="lt-empty">Your cart is empty. <a href="{{ url('/') }}">Continue shopping</a></div>
    @else
        <form action="{{ route('cart.update') }}" method="post">
            {{ csrf_field() }}
            <div class="lt-table-wrap">
                <table class="lt-shop-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            @php($product = $item['product'])
                            @php($subtotal = $product->selling_price * $item['quantity'])
                            <tr>
                                <td>
                                    <a class="lt-cart-product" href="{{ url('/product-details/'.$product->id) }}">
                                        <img src="{{ $product->image_url }}" alt="{{ $product->product_name }}">
                                        <span>{{ $product->product_name }}</span>
                                    </a>
                                </td>
                                <td>&#2547;{{ number_format($product->selling_price) }}</td>
                                <td>
                                    <label class="sr-only" for="quantity-{{ $product->id }}">Quantity for {{ $product->product_name }}</label>
                                    <input id="quantity-{{ $product->id }}" type="number" min="0" max="99" name="quantity[{{ $product->id }}]" value="{{ $item['quantity'] }}">
                                </td>
                                <td><strong>&#2547;{{ number_format($subtotal) }}</strong></td>
                                <td>
                                    <button class="lt-link-button" formaction="{{ route('cart.remove', $product->id) }}" aria-label="Remove {{ $product->product_name }}">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="lt-cart-summary">
                <p>Total <strong>&#2547;{{ number_format($cartTotal) }}</strong></p>
                <div>
                    <button class="lt-secondary-button" type="submit">Update cart</button>
                    <a class="lt-primary-button" href="{{ url('/checkout') }}">Proceed to checkout</a>
                </div>
            </div>
        </form>

        @guest
            <form class="lt-checkout-form" method="post" action="{{ route('cart.recovery-email') }}" style="margin-top:20px">
                {{ csrf_field() }}
                <h3>Save this cart for later</h3>
                <p>Enter your email to receive a secure recovery link if you leave before checkout.</p>
                <div style="display:flex;gap:10px">
                    <input type="email" name="email" value="{{ session('cart_email') }}" placeholder="Email address" required style="flex:1;padding:11px;border:1px solid #ccd8e0;border-radius:8px">
                    <button class="lt-secondary-button" type="submit">Save recovery email</button>
                </div>
            </form>
        @endguest
    @endif
</section>
@endsection
