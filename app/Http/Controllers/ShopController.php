<?php

namespace App\Http\Controllers;

use App\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function cart(Request $request)
    {
        return view('front-end.pages.cart', ['items' => $this->sessionProducts($request, 'cart')]);
    }

    public function addToCart(Request $request, $id)
    {
        $product = Product::where('publication_status', 1)->findOrFail($id);
        if ($product->product_condition !== 'In Stock') return redirect()->back()->with('error','This product is currently out of stock.');
        $cart = (array) $request->session()->get('cart', []);
        $quantity = max(1, (int) $request->input('quantity', 1));
        if ($product->stock_tracking && $product->stock_quantity < $quantity) return redirect()->back()->with('error','Only '.$product->stock_quantity.' unit(s) are available.');
        $cart[$product->id] = min(99, ($cart[$product->id] ?? 0) + $quantity);
        $request->session()->put('cart', $cart);
        app(\App\Services\CartRecoveryService::class)->capture($request);

        return redirect()->back()->with('success', $product->product_name.' added to cart.');
    }

    public function updateCart(Request $request)
    {
        $cart = [];
        foreach ((array) $request->input('quantity', []) as $id => $quantity) {
            if ((int) $quantity > 0) $cart[(int) $id] = min(99, (int) $quantity);
        }
        $request->session()->put('cart', $cart);
        app(\App\Services\CartRecoveryService::class)->capture($request);
        return redirect()->route('cart.index')->with('success', 'Cart updated.');
    }

    public function removeFromCart(Request $request, $id)
    {
        $cart = (array) $request->session()->get('cart', []);
        unset($cart[$id]);
        $request->session()->put('cart', $cart);
        app(\App\Services\CartRecoveryService::class)->capture($request);
        return redirect()->back()->with('success', 'Product removed from cart.');
    }

    public function compare(Request $request)
    {
        $items=$this->sessionProducts($request, 'compare');
        $categoryIds=$items->pluck('product.category_id')->unique();
        $attributes=\App\CatalogAttribute::whereIn('category_id',$categoryIds)->where('is_comparable',1)->orderBy('display_order')->get();
        return view('front-end.pages.compare', compact('items','attributes'));
    }

    public function addToCompare(Request $request, $id)
    {
        $product = Product::where('publication_status', 1)->findOrFail($id);
        $compare = array_values((array) $request->session()->get('compare', []));
        if (!in_array($product->id, $compare) && count($compare) >= 4) {
            return redirect()->back()->with('error', 'You can compare up to 4 products.');
        }
        if (!in_array($product->id, $compare)) $compare[] = $product->id;
        $request->session()->put('compare', $compare);
        return redirect()->back()->with('success', $product->product_name.' added to compare.');
    }

    public function removeFromCompare(Request $request, $id)
    {
        $compare = array_values(array_filter((array) $request->session()->get('compare', []), function ($productId) use ($id) {
            return (int) $productId !== (int) $id;
        }));
        $request->session()->put('compare', $compare);
        return redirect()->back();
    }

    private function sessionProducts(Request $request, $key)
    {
        $stored = (array) $request->session()->get($key, []);
        $ids = $key === 'cart' ? array_keys($stored) : array_values($stored);
        $products = Product::with('attributeValues')->whereIn('id', $ids)->get()->keyBy('id');
        return collect($ids)->map(function ($id) use ($products, $stored, $key) {
            if (!$products->has($id)) return null;
            return ['product' => $products->get($id), 'quantity' => $key === 'cart' ? $stored[$id] : 1];
        })->filter();
    }
}
