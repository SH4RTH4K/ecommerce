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
use Illuminate\Support\Facades\Cache;

class WelcomeController extends Controller
{
    public function index()
    {
        $categoryTree = Cache::remember('mega-menu-tree', now()->addHours(6), function () {
            return Category::with('subCategories')
                ->withCount(['products as published_products_count' => function ($query) {
                    $query->where('publication_status', 1);
                }])
                ->where('publication_status', 1)
                ->orderBy('category_name')
                ->get();
        });

        // top_product is the legacy schema's featured flag.
        $featuredProducts = Product::where('publication_status', 1)
            ->where('top_product', 1)
            ->latest()
            ->limit(10)
            ->get();

        $newArrivals = Product::where('publication_status', 1)
            ->where('is_new_arrival', 1)
            ->latest()
            ->limit(10)
            ->get();

        $latestProducts = Product::where('publication_status', 1)
            ->latest()
            ->limit(10)
            ->get();

        $brands = Manufacturer::where('publication_status', 1)
            ->orderBy('manufacturer_name')
            ->limit(12)
            ->get();

        $featuredCategories = $categoryTree->where('is_featured', 1)
            ->sortBy(function ($category) {
                return sprintf('%05d-%s', $category->display_order, $category->category_name);
            })->take(10);

        $banners = Banner::with(['product', 'category'])
            ->visible()
            ->orderBy('display_order')
            ->orderByDesc('id')
            ->get();

        return view('home', compact(
            'categoryTree',
            'featuredCategories',
            'featuredProducts',
            'newArrivals',
            'latestProducts',
            'brands',
            'banners'
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
            $storedValues=DB::table('product_attribute_values')->join('product','product_attribute_values.product_id','=','product.id')->where('product_attribute_values.attribute_id',$attribute->id)->where('product.category_id',$category_id)->where('product.publication_status',1)->pluck('product_attribute_values.value');
            if($attribute->input_type==='multiselect'){$attribute->values=$storedValues->flatMap(function($value){$decoded=json_decode($value,true);return is_array($decoded)?$decoded:[$value];})->filter()->unique()->sort()->values();}
            else $attribute->values=$storedValues->unique()->sort()->values();
            return $attribute;
        })->filter(function($attribute){return $attribute->values->isNotEmpty();});

        return view('front-end.pages.product-by-category', compact('category', 'products', 'manufacturers', 'attributeFilters'));
    }
    
    public function productBySubCategory($sub_category)
    {
        $search_by_sub_category_name = DB::table('sub_category')
            ->where('sub_category_id',$sub_category)
            ->where('publication_status',1)
            ->first();
        abort_unless($search_by_sub_category_name,404);
        $all_sub_product_by_category=DB::table('product')
                ->where('sub_category',$sub_category)
                ->where('publication_status',1)
                ->latest()
                ->get();
        return view('front-end.pages.product-by-sub-category',compact('all_sub_product_by_category','search_by_sub_category_name'));
    }
    
    public function allManufacturerById($manufacturer_id)
    {
//        $search_by_sub_category_name = DB::table('product')
//            ->join('sub_category', 'product.sub_category', '=', 'sub_category.sub_category_id')
//            ->select('product.*', 'sub_category.sub_category_name')
//            ->where('product.sub_category',$sub_category)
//            ->first();
        $all_manufacturer_by_id=DB::table('product')
                ->where('manufacturer_id',$manufacturer_id)
                ->where('publication_status',1)
                ->latest()
                ->get();
        $manufacturer_home= view('front-end.pages.manufacturer-by-id')
                ->with('all_manufacturer_by_id', $all_manufacturer_by_id);
//                ->with('search_by_sub_category_name',$search_by_sub_category_name);
        return view('front-end.master')
                    ->with('main_content', $manufacturer_home);
    }
    
    public function searchProduct(Request $request)
    {
        $search=$request->search_text;
        $search_product=DB::table('product')
                ->where('publication_status',1)
                ->where(function ($query) use ($search) {
                    $query->where('product_name','like','%'.$search.'%')
                        ->orWhere('product_model','like','%'.$search.'%')
                        ->orWhere('sku','like','%'.$search.'%')
                        ->orWhereExists(function($attributeQuery) use($search){$attributeQuery->select(DB::raw(1))->from('product_attribute_values')->whereRaw('product_attribute_values.product_id = product.id')->where('value','like','%'.$search.'%');});
                })->get();
        $search_home= view('front-end.pages.search-product')
                ->with('search_product', $search_product);
        return view('front-end.master')
                    ->with('main_content', $search_home);
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
        $product_details = Product::with(['manufacturer.company', 'series', 'category', 'attributeValues'])
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
