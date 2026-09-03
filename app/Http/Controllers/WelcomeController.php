<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Session;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;
use App\Category;
use App\Manufacturer;
use App\Product;
use App\Banner;
use App\Services\StorefrontNavbarService;
use App\Services\HomepageFeatureCardService;

class WelcomeController extends Controller
{
    public function index(HomepageFeatureCardService $featureCards)
    {
        $categoryTree = app(StorefrontNavbarService::class)->categoryTree();
        $homepageSettings = DB::table('site_settings')
            ->whereIn('setting_key', [
                'homepage_featured_products_limit',
                'homepage_featured_products_per_row',
                'homepage_new_arrivals_limit',
                'homepage_new_arrivals_per_row',
            ])
            ->pluck('setting_value', 'setting_key');

        $featuredProductsLimit = max(1, min(50, (int) ($homepageSettings->get('homepage_featured_products_limit') ?: 20)));
        $featuredProductsPerRow = max(2, min(6, (int) ($homepageSettings->get('homepage_featured_products_per_row') ?: 5)));
        $newArrivalsLimit = max(1, min(50, (int) ($homepageSettings->get('homepage_new_arrivals_limit') ?: 20)));
        $newArrivalsPerRow = max(2, min(6, (int) ($homepageSettings->get('homepage_new_arrivals_per_row') ?: 5)));

        // top_product is the legacy schema's featured flag.
        $featuredProducts = Product::where('publication_status', 1)
            ->where('top_product', 1)
            ->latest()
            ->limit($featuredProductsLimit)
            ->get();

        $newArrivals = Product::where('publication_status', 1)
            ->where('is_new_arrival', 1)
            ->latest()
            ->limit($newArrivalsLimit)
            ->get();

        $latestProducts = Product::where('publication_status', 1)
            ->latest()
            ->limit(10)
            ->get();

        $featuredBrandSetting = DB::table('site_settings')
            ->where('setting_key', 'homepage_featured_brands')
            ->value('setting_value');
        $featuredBrandIcons = [];
        $featuredBrandImages = [];
        if ($featuredBrandSetting !== null) {
            $featuredBrandIds = collect(json_decode($featuredBrandSetting, true) ?: [])
                ->map(fn ($id) => (int) $id)->filter()->values();
            $featuredBrandIcons = json_decode(DB::table('site_settings')->where('setting_key', 'homepage_featured_brand_icons')->value('setting_value') ?: '{}', true) ?: [];
            $featuredBrandImages = json_decode(DB::table('site_settings')->where('setting_key', 'homepage_featured_brand_images')->value('setting_value') ?: '{}', true) ?: [];
            $brands = Manufacturer::where('publication_status', 1)
                ->whereIn('manufacturer_id', $featuredBrandIds)
                ->get()
                ->sortBy(fn ($brand) => $featuredBrandIds->search((int) $brand->manufacturer_id))
                ->values();
        } else {
            $brands = Manufacturer::where('publication_status', 1)
                ->orderBy('manufacturer_name')
                ->limit(12)
                ->get();
        }

        $featuredCategories = $categoryTree->where('is_featured', 1)
            ->sortBy(function ($category) {
                return sprintf('%05d-%s', $category->display_order, $category->category_name);
            })->values();

        $banners = Banner::with(['product', 'category'])
            ->visible()
            ->orderBy('display_order')
            ->orderByDesc('id')
            ->get();

        $homepageFeatureCards = $featureCards->storefront();

        return view('home', compact(
            'categoryTree',
            'featuredCategories',
            'featuredProducts',
            'newArrivals',
            'latestProducts',
            'featuredProductsLimit',
            'featuredProductsPerRow',
            'newArrivalsLimit',
            'newArrivalsPerRow',
            'brands',
            'banners', 'homepageFeatureCards', 'featuredBrandIcons', 'featuredBrandImages'
        ));
    }
    
    public function productByCategory(Request $request, $category_id)
    {
        $category = Category::where('publication_status', 1)->findOrFail($category_id);
        $query = Product::where('category_id', $category_id)->where('publication_status', 1);

        if ($request->filled('manufacturer')) $query->where('manufacturer_id', $request->manufacturer);
        if ($request->filled('min_price')) $query->whereRaw(Product::sellingPriceSql().' >= ?', [max(0, (float) $request->min_price)]);
        if ($request->filled('max_price')) $query->whereRaw(Product::sellingPriceSql().' <= ?', [max(0, (float) $request->max_price)]);
        if ($request->availability === 'in-stock') $query->where('product_condition', 'In Stock');
        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($q) use ($term) {
                $q->where('product_name', 'like', '%'.$term.'%')->orWhere('product_model', 'like', '%'.$term.'%');
            });
        }
        foreach((array)$request->input('attributes',[]) as $attributeId=>$value) {
            if($value==='') continue;
            $query->whereHas('attributeValues',function($attributeQuery) use($attributeId,$value){$attributeQuery->where('attribute_id',(int)$attributeId)->where(function($valueQuery)use($value){$valueQuery->where('value',$value)->orWhere('value','like','%"'.$value.'"%');});});
        }

        switch ($request->input('sort')) {
            case 'price-asc': $query->orderByRaw(Product::sellingPriceSql().' ASC'); break;
            case 'price-desc': $query->orderByRaw(Product::sellingPriceSql().' DESC'); break;
            case 'name': $query->orderBy('product_name'); break;
            default: $query->latest();
        }

        $manufacturerIds = Product::where('category_id', $category_id)->where('publication_status', 1)
            ->whereNotNull('manufacturer_id')->distinct()->pluck('manufacturer_id');
        $manufacturers = Manufacturer::whereIn('manufacturer_id', $manufacturerIds)->orderBy('manufacturer_name')->get();
        $perPage = in_array((int) $request->per_page, [12, 24, 48]) ? (int) $request->per_page : 12;
        $products = $query->paginate($perPage)->appends($request->query());
        $attributeFilters = DB::table('catalog_attributes')->where('category_id',$category_id)->where('is_filterable',1)->orderBy('display_order')->get()->map(function($attribute) use($category_id){
            $storedValues=DB::table('product_attribute_values')->join('product','product_attribute_values.product_id','=','product.id')->whereNull('product.deleted_at')->where('product_attribute_values.attribute_id',$attribute->id)->where('product.category_id',$category_id)->where('product.publication_status',1)->pluck('product_attribute_values.value');
            if($attribute->input_type==='multiselect'){$attribute->values=$storedValues->flatMap(function($value){$decoded=json_decode($value,true);return is_array($decoded)?$decoded:[$value];})->filter()->unique()->sort()->values();}
            else $attribute->values=$storedValues->unique()->sort()->values();
            return $attribute;
        })->filter(function($attribute){return $attribute->values->isNotEmpty();});

        return view('front-end.pages.product-by-category', compact('category', 'products', 'manufacturers', 'attributeFilters'));
    }
    
    public function productBySubCategory(Request $request, $sub_category)
    {
        $search_by_sub_category_name = DB::table('sub_category')
            ->where('sub_category_id',$sub_category)
            ->where('publication_status',1)
            ->first();
        abort_unless($search_by_sub_category_name,404);

        // Older catalog data may still use a standalone category such as
        // "ROUTER" instead of the newer "Router" subcategory relationship.
        // Keep those products visible without rewriting the product records.
        $legacyCategoryIds = DB::table('category')
            ->whereRaw('LOWER(category_name) = LOWER(?)', [$search_by_sub_category_name->sub_category_name])
            ->pluck('category_id');

        $productQuery = Product::whereNull('deleted_at')
            ->where('publication_status', 1)
            ->where(function ($query) use ($sub_category, $search_by_sub_category_name, $legacyCategoryIds) {
                $query->where('sub_category', (string) $sub_category)
                    ->orWhere('sub_category', $search_by_sub_category_name->sub_category_name);

                if ($legacyCategoryIds->isNotEmpty()) {
                    $query->orWhereIn('category_id', $legacyCategoryIds);
                }
            });

        if ($request->filled('manufacturer')) $productQuery->where('manufacturer_id', $request->manufacturer);
        if ($request->filled('min_price')) $productQuery->whereRaw(Product::sellingPriceSql().' >= ?', [max(0, (float) $request->min_price)]);
        if ($request->filled('max_price')) $productQuery->whereRaw(Product::sellingPriceSql().' <= ?', [max(0, (float) $request->max_price)]);
        if ($request->input('availability') === 'in-stock') $productQuery->where('product_condition', 'In Stock');
        if ($request->filled('q')) { $term = trim($request->q); $productQuery->where(function ($query) use ($term) { $query->where('product_name', 'like', '%'.$term.'%')->orWhere('product_model', 'like', '%'.$term.'%'); }); }
        switch ($request->input('sort')) {
            case 'price-asc': $productQuery->orderByRaw(Product::sellingPriceSql().' ASC'); break;
            case 'price-desc': $productQuery->orderByRaw(Product::sellingPriceSql().' DESC'); break;
            case 'name': $productQuery->orderBy('product_name'); break;
            default: $productQuery->latest();
        }
        $manufacturerIds = (clone $productQuery)->reorder()->whereNotNull('manufacturer_id')->distinct()->pluck('manufacturer_id');
        $manufacturers = Manufacturer::whereIn('manufacturer_id', $manufacturerIds)->orderBy('manufacturer_name')->get();
        $perPage = in_array((int) $request->per_page, [12, 24, 48]) ? (int) $request->per_page : 12;
        $products = $productQuery->paginate($perPage)->appends($request->query());

        return view('front-end.pages.product-by-sub-category', compact('products', 'manufacturers', 'search_by_sub_category_name'));
    }
    
    public function allManufacturerById($manufacturer_id)
    {
        $manufacturer = Manufacturer::where('publication_status', 1)->findOrFail($manufacturer_id);
        $all_manufacturer_by_id=DB::table('product')
                ->whereNull('deleted_at')
                ->where('manufacturer_id',$manufacturer_id)
                ->where('publication_status',1)
                ->select('product.*')
                ->selectRaw(Product::sellingPriceSql().' as selling_price')
                ->selectRaw('CASE WHEN offer_price IS NOT NULL AND offer_price < regular_price THEN 1 ELSE 0 END as has_offer')
                ->latest()
                ->get();
        return view('front-end.pages.manufacturer-by-id', compact('all_manufacturer_by_id', 'manufacturer'));
    }
    
    public function searchProduct(Request $request)
    {
        $search_term = trim((string) $request->search_text);
        $search_product=DB::table('product')
                ->whereNull('deleted_at')
                ->where('publication_status',1)
                ->where(function ($query) use ($search_term) {
                    $query->where('product_name','like','%'.$search_term.'%')
                        ->orWhere('product_model','like','%'.$search_term.'%')
                        ->orWhere('sku','like','%'.$search_term.'%')
                        ->orWhereExists(function($attributeQuery) use($search_term){$attributeQuery->select(DB::raw(1))->from('product_attribute_values')->whereRaw('product_attribute_values.product_id = product.id')->where('value','like','%'.$search_term.'%');});
                })
                ->select('product.*')
                ->selectRaw(Product::sellingPriceSql().' as selling_price')
                ->selectRaw('CASE WHEN offer_price IS NOT NULL AND offer_price < regular_price THEN 1 ELSE 0 END as has_offer')
                ->get();
        return view('front-end.pages.search-product', compact('search_product', 'search_term'));
    }

    public function searchSuggestions(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 1) {
            return response()->json(['products' => [], 'categories' => [], 'total' => 0]);
        }

        $like = '%'.$term.'%';
        $products = Product::query()
            ->whereNull('deleted_at')
            ->where('publication_status', 1)
            ->where(function ($query) use ($like) {
                $query->where('product_name', 'like', $like)
                    ->orWhere('product_model', 'like', $like)
                    ->orWhere('sku', 'like', $like);
            })
            ->select(['id', 'product_name', 'product_model', 'sku', 'product_image', 'regular_price', 'offer_price', 'product_condition', 'stock_quantity', 'stock_tracking'])
            ->selectRaw(Product::sellingPriceSql().' as selling_price')
            ->orderBy('product_name')
            ->limit(8)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => (int) $product->id,
                    'name' => $product->product_name,
                    'model' => $product->product_model,
                    'image' => $product->image_url,
                    'price' => (float) $product->selling_price,
                    'regular_price' => (float) $product->regular_price,
                    'has_offer' => $product->offer_price !== null && (float) $product->offer_price < (float) $product->regular_price,
                    'in_stock' => (bool) (($product->stock_tracking && (int) $product->stock_quantity > 0) || (!$product->stock_tracking && strcasecmp((string) $product->product_condition, 'Out Of Stock') !== 0)),
                ];
            })->values();

        $categories = DB::table('category')
            ->whereNull('deleted_at')
            ->where('publication_status', 1)
            ->where('category_name', 'like', $like)
            ->select(['category_id as id', 'category_name as name'])
            ->orderBy('category_name')
            ->limit(8)
            ->get()
            ->map(fn ($category) => ['id' => (int) $category->id, 'name' => $category->name, 'url' => url('/product-by-category/'.$category->id)])
            ->values();

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'total' => $products->count(),
            'search_url' => url('/search-product'),
        ]);
    }
    
    
    
    public function gift_item()
    {
        return view('front-end.pages.gift-item');
    }

    public function physiotherapy()
    {
        return view('front-end.pages.physiotherapy');
    }
    
    
    public function about_us()
    {
        return view('front-end.pages.about-us');
    }
    
    public function contact_us()
    {
        return view('front-end.pages.contact-us');
    }
    
    public function termsandconditions()
    {
        return view('front-end.pages.terms&conditions');
    }
    
    
    
    public function product_details($id)
    {
        $product_details = Product::with(['manufacturer.company', 'series', 'category', 'attributeValues', 'variants' => function($query){$query->where('is_active',1)->orderBy('id');}, 'lots' => function($query){$query->where(function($date){$date->whereNull('expires_at')->orWhere('expires_at','>=',now()->toDateString());})->orderBy('expires_at');}])
            ->where('publication_status', 1)->findOrFail($id);
        $similarProducts = Product::where('publication_status', 1)
            ->where('category_id', $product_details->category_id)
            ->where('id', '<>', $product_details->id)->latest()->limit(5)->get();
        $reviews = DB::table('product_reviews')->where('product_id',$id)->where('is_approved',1)->latest()->get();
        $questions = DB::table('product_questions')->where('product_id',$id)->where('is_approved',1)->whereNotNull('answer')->latest()->get();
        $averageRating = $reviews->count() ? round($reviews->avg('rating'), 1) : null;
        return view('front-end.pages.product-details', compact('product_details', 'similarProducts', 'reviews', 'questions', 'averageRating'));
    }
    
    

    public function store(Request $request)
    {
        //
    }

}
