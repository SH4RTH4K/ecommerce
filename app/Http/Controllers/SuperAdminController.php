<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;
use Session;
use DB;
use App\Banner;
use App\Category;
use App\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SuperAdminController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $this->authCheck();
        $today=date('Y-m-d');$yesterday=date('Y-m-d',strtotime('-1 day'));
        $stats = [
            'orders' => DB::table('orders')->count(),
            'pending_orders' => DB::table('orders')->whereIn('status',['pending','confirmed','processing'])->count(),
            'today_orders' => DB::table('orders')->whereDate('created_at',date('Y-m-d'))->count(),
            'revenue' => DB::table('orders')->where('status','<>','cancelled')->sum('total'),
            'products' => DB::table('product')->where('publication_status',1)->count(),
            'low_stock' => DB::table('product')->where('stock_tracking',1)->where('stock_quantity','<=',5)->count(),
            'support' => DB::table('support_requests')->whereIn('status',['new','in_progress'])->count(),
            'feedback' => DB::table('product_reviews')->where('is_approved',0)->count() + DB::table('product_questions')->where('is_approved',0)->count(),
            'customers' => DB::table('users')->count(),
            'today_customers' => DB::table('users')->whereDate('created_at',$today)->count(),
            'total_visits' => DB::table('page_visits')->count(),
            'unique_visitors' => DB::table('visitor_sessions')->count(),
            'active_visitors' => DB::table('visitor_sessions')->where('last_seen_at','>=',now()->subMinutes(5))->count(),
            'today_visits' => DB::table('page_visits')->whereDate('visited_at',$today)->count(),
            'today_unique' => DB::table('visitor_sessions')->whereDate('last_seen_at',$today)->count(),
            'today_revenue' => DB::table('orders')->whereDate('created_at',$today)->where('status','<>','cancelled')->sum('total'),
            'open_claims' => DB::table('service_claims')->whereNotIn('status',['completed','rejected'])->count(),
        ];
        $yesterdayVisits=DB::table('page_visits')->whereDate('visited_at',$yesterday)->count();$stats['visit_change']=$yesterdayVisits?round((($stats['today_visits']-$yesterdayVisits)/$yesterdayVisits)*100,1):($stats['today_visits']?100:0);$stats['conversion_rate']=$stats['unique_visitors']?round(($stats['orders']/$stats['unique_visitors'])*100,2):0;
        $recentOrders = DB::table('orders')->latest()->limit(8)->get();
        $lowStockProducts = DB::table('product')->where('stock_tracking',1)->where('stock_quantity','<=',5)->orderBy('stock_quantity')->limit(8)->get();
        $topProducts = DB::table('order_items')->select('product_name',DB::raw('SUM(quantity) as units'),DB::raw('SUM(subtotal) as sales'))->groupBy('product_name')->orderByDesc('units')->limit(5)->get();
        $trafficTrend=DB::table('page_visits')->where('visited_at','>=',now()->subDays(6)->startOfDay())->selectRaw('DATE(visited_at) visit_date, COUNT(*) visits, COUNT(DISTINCT visitor_session_id) visitors')->groupBy(DB::raw('DATE(visited_at)'))->orderBy('visit_date')->get();
        $popularPages=DB::table('page_visits')->select('path',DB::raw('COUNT(*) visits'),DB::raw('COUNT(DISTINCT visitor_session_id) visitors'))->groupBy('path')->orderByDesc('visits')->limit(8)->get();
        $currentVisitors=DB::table('visitor_sessions')->where('last_seen_at','>=',now()->subMinutes(5))->latest('last_seen_at')->limit(10)->get();
        $recentCustomers=DB::table('users')->latest()->limit(6)->get();
        return view('admin.admin-pages.admin-home',compact('stats','recentOrders','lowStockProducts','topProducts','trafficTrend','popularPages','currentVisitors','recentCustomers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function logout() {
        Session::put('admin_name', '');
        Session::put('admin_id', '');
        return Redirect::to('/xyz');
    }

    public function addCategory() {
        $this->authCheck();
        $add_category = view('admin.admin-pages.add-category');
        return view('admin.admin-master')
                        ->with('admin_main_content', $add_category);
    }

    public function saveCategory(Request $request) {
        $data = array();
        $data['category_name'] = $request->category_name;
        $data['category_description'] = $request->category_description;
        $allowedIcons = ['fa-folder-open','fa-music','fa-signal','fa-link','fa-archive','fa-refresh','fa-picture-o','fa-desktop','fa-dot-circle-o','fa-gamepad','fa-hdd-o','fa-headphones','fa-video-camera','fa-keyboard-o','fa-laptop','fa-mouse-pointer','fa-print','fa-clock-o','fa-volume-up','fa-bolt','fa-camera','fa-mobile','fa-cogs','fa-shield','fa-globe','fa-sitemap','fa-shopping-cart'];
        $data['icon_class'] = in_array($request->icon_class, $allowedIcons, true) ? $request->icon_class : 'fa-folder-open';
        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;
        $data['display_order'] = max(0, (int) $request->display_order);
        $data['publication_status'] = $request->publication_status;
        DB::table('category')->insert($data);
        Cache::forget('mega-menu-tree');
        Session::put('message', 'Save Category Successfully');
        return Redirect::to('/add-category');
    }

    public function manageCategory() {
        $this->authCheck();
        $all_category = DB::table('category')
                ->get();
        $manage_category = view('admin.admin-pages.manage-category')
                ->with('all_category_info', $all_category);
        return view('admin.admin-master')
                        ->with('admin_main_content', $manage_category);
    }

    public function unpublishedCategory($category_id) {
        DB::table('category')
                ->where('category_id', $category_id)
                ->update(['publication_status' => 0]);
        Cache::forget('mega-menu-tree');
        return Redirect::to('/manage-category');
    }

    public function publishedCategory($category_id) {
        DB::table('category')
                ->where('category_id', $category_id)
                ->update(['publication_status' => 1]);
        Cache::forget('mega-menu-tree');
        return Redirect::to('/manage-category');
    }

    public function editCategory($category_id) {
        $this->authCheck();
        $category_info = DB::table('category')
                ->where('category_id', $category_id)
                ->first();
        $edit_category = view('admin.admin-pages.edit-category')
                ->with('category_info', $category_info);
        return view('admin.admin-master')
                        ->with('admin_main_content', $edit_category);
    }

    public function updateCategory(Request $request) {
        $this->authCheck();
        $data = array();
        $data['category_name'] = $request->category_name;
        $data['category_description'] = $request->category_description;
        $allowedIcons = ['fa-folder-open','fa-music','fa-signal','fa-link','fa-archive','fa-refresh','fa-picture-o','fa-desktop','fa-dot-circle-o','fa-gamepad','fa-hdd-o','fa-headphones','fa-video-camera','fa-keyboard-o','fa-laptop','fa-mouse-pointer','fa-print','fa-clock-o','fa-volume-up','fa-bolt','fa-camera','fa-mobile','fa-cogs','fa-shield','fa-globe','fa-sitemap','fa-shopping-cart'];
        $data['icon_class'] = in_array($request->icon_class, $allowedIcons, true) ? $request->icon_class : 'fa-folder-open';
        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;
        $data['display_order'] = max(0, (int) $request->display_order);
        $category_id = $request->category_id;
        DB::table('category')
                ->where('category_id', $category_id)
                ->update($data);
        Cache::forget('mega-menu-tree');
        return Redirect::to('/manage-category');
    }

    public function deleteCategory($category_id) {
        DB::table('category')
                ->where('category_id', $category_id)
                ->delete();
        Cache::forget('mega-menu-tree');
        return Redirect::to('/manage-category');
    }

    public function bulkDeleteCategories(Request $request)
    {
        $this->validate($request,['category_ids'=>'required|array|min:1','category_ids.*'=>'required|integer|distinct|exists:category,category_id']);
        $ids=array_values(array_unique(array_map('intval',$request->category_ids)));
        $used=array_unique(array_merge(
            DB::table('product')->whereIn('category_id',$ids)->pluck('category_id')->map(function($id){return (int)$id;})->all(),
            DB::table('sub_category')->whereIn('category_id',$ids)->pluck('category_id')->map(function($id){return (int)$id;})->all()
        ));
        $deletable=array_values(array_diff($ids,$used));
        $deleted=$deletable?DB::transaction(function()use($deletable){return DB::table('category')->whereIn('category_id',$deletable)->delete();}):0;
        Cache::forget('mega-menu-tree');Cache::forget('xml-sitemap');
        return $this->bulkDeleteResult('/manage-category',$deleted,count($used),'categor','products or subcategories');
    }
    
    
    public function addSubCategory() {
        $this->authCheck();
        $all_category=DB::table('category')
                ->whereIn('category_id',[14,15])
                ->get();
        $addSubCategory = view('admin.admin-pages.add-subCategory')
                ->with('all_category', $all_category);
        return view('admin.admin-master')
                        ->with('admin_main_content', $addSubCategory);
    }

    public function saveSubCategory(Request $request) {
        $data = array();
        $data['sub_category_name'] = $request->subCategory_name;
        $data['category_id'] = $request->category_id;
        $data['publication_status'] = $request->publication_status;
        DB::table('sub_category')
                ->insert($data);
        Session::put('message', 'Sub Category Save Successfully');
        return Redirect::to('/add-subCategory');
    }

    public function manageSubCategory() {
        $this->authCheck();
        $category_details = DB::table('sub_category')
                ->join('category', 'sub_category.category_id', '=', 'category.category_id')
                ->select('sub_category.*', 'category.category_name')
                ->get();
//        $all_subCategory = DB::table('sub_category')
//                ->get();
        $manage_subCategory = view('admin.admin-pages.manage-subCategory')
//                ->with('all_subCategory', $all_subCategory)
                ->with('category_details',$category_details);
        return view('admin.admin-master')
                        ->with('admin_main_content', $manage_subCategory);
    }

    public function unpublishedSubCategory($sub_category_id) {
        DB::table('sub_category')
                ->where('sub_category_id', $sub_category_id)
                ->update(['publication_status' => 0]);
        return Redirect::to('/manage-subCategory');
    }

    public function publishedSubCategory($sub_category_id) {
        DB::table('sub_category')
                ->where('sub_category_id', $sub_category_id)
                ->update(['publication_status' => 1]);
        return Redirect::to('/manage-subCategory');
    }

    public function deleteSubCategory($sub_category_id) {
        DB::table('sub_category')
                ->where('sub_category_id', $sub_category_id)
                ->delete();
        return Redirect::to('/manage-subCategory');
    }

    public function bulkDeleteSubCategories(Request $request)
    {
        $this->validate($request, [
            'sub_category_ids' => 'required|array|min:1',
            'sub_category_ids.*' => 'required|integer|distinct|exists:sub_category,sub_category_id',
        ]);

        $ids = array_values(array_unique(array_map('intval', $request->sub_category_ids)));
        $usedIds = DB::table('product')->whereIn('sub_category', $ids)
            ->pluck('sub_category')->map(function ($id) { return (int) $id; })->unique()->all();
        $deletableIds = array_values(array_diff($ids, $usedIds));
        $deleted = 0;

        if ($deletableIds) {
            $deleted = DB::transaction(function () use ($deletableIds) {
                return DB::table('sub_category')->whereIn('sub_category_id', $deletableIds)->delete();
            });
        }

        $message = $deleted.' subcategor'.($deleted === 1 ? 'y' : 'ies').' deleted.';
        if ($usedIds) $message .= ' '.count($usedIds).' skipped because '.(count($usedIds) === 1 ? 'it is' : 'they are').' assigned to products.';
        return Redirect::to('/manage-subCategory')->with($deleted ? 'message' : 'exception', $message);
    }

    public function editSubCategory($sub_category_id) {
        $this->authCheck();
        $all_category=DB::table('category')
                ->whereIn('category_id',[14,15])
                ->get();
        $subCategory_info = DB::table('sub_category')
                ->where('sub_category_id', $sub_category_id)
                ->first();
        $edit_subCategory = view('admin.admin-pages.edit-subCategory')
                ->with('subCategory_info', $subCategory_info)
                ->with('all_category',$all_category);
        return view('admin.admin-master')
                        ->with('admin_main_content', $edit_subCategory);
    }

    public function updateSubCategory(Request $request) {
        $this->authCheck();
        $data = array();
        $data['sub_category_name'] = $request->subCategory_name;
        $data['category_id'] = $request->category_id;
        $sub_category_id=$request->subCategory_id;
        DB::table('sub_category')
                ->where('sub_category_id', $sub_category_id)
                ->update($data);
        return Redirect::to('/manage-subCategory');
    }
    
    
//  For Manufacturer
    

    public function addManufacturer() {
        $this->authCheck();
        $add_manufacturer = view('admin.admin-pages.add-manufacturer');
        return view('admin.admin-master')
                        ->with('admin_main_content', $add_manufacturer);
    }

    public function saveManufacturer(Request $request) {
        $data = array();
        $data['manufacturer_name'] = $request->manufacturer_name;
        $data['publication_status'] = $request->publication_status;
        DB::table('manufacturer')
                ->insert($data);
        Session::put('message', 'Company Name Save Successfully');
        return Redirect::to('/add-manufacturer');
    }

    public function manageManufacturer() {
        $this->authCheck();
        $all_manufacturer = DB::table('manufacturer')
                ->get();
        $manage_manufacturer = view('admin.admin-pages.manage-manufacturer')
                ->with('all_manufacturer', $all_manufacturer);
        return view('admin.admin-master')
                        ->with('admin_main_content', $manage_manufacturer);
    }

    public function unpublishedManufacturer($manufacturer_id) {
        DB::table('manufacturer')
                ->where('manufacturer_id', $manufacturer_id)
                ->update(['publication_status' => 0]);
        return Redirect::to('/manage-manufacturer');
    }

    public function publishedManufacturer($manufacturer_id) {
        DB::table('manufacturer')
                ->where('manufacturer_id', $manufacturer_id)
                ->update(['publication_status' => 1]);
        return Redirect::to('/manage-manufacturer');
    }

    public function deleteManufacturer($manufacturer_id) {
        DB::table('manufacturer')
                ->where('manufacturer_id', $manufacturer_id)
                ->delete();
        return Redirect::to('/manage-manufacturer');
    }

    public function bulkDeleteManufacturers(Request $request)
    {
        $this->validate($request,['manufacturer_ids'=>'required|array|min:1','manufacturer_ids.*'=>'required|integer|distinct|exists:manufacturer,manufacturer_id']);
        $ids=array_values(array_unique(array_map('intval',$request->manufacturer_ids)));
        $used=DB::table('product')->whereIn('manufacturer_id',$ids)->pluck('manufacturer_id')->map(function($id){return (int)$id;})->unique()->all();
        $deletable=array_values(array_diff($ids,$used));
        $deleted=$deletable?DB::transaction(function()use($deletable){return DB::table('manufacturer')->whereIn('manufacturer_id',$deletable)->delete();}):0;
        return $this->bulkDeleteResult('/manage-manufacturer',$deleted,count($used),'manufacturer','products');
    }

    public function editManufacturer($manufacturer_id) {
        $this->authCheck();
        $manufacturer_info = DB::table('manufacturer')
                ->where('manufacturer_id', $manufacturer_id)
                ->first();
        $edit_manufacturer = view('admin.admin-pages.edit-manufacturer')
                ->with('manufacturer_info', $manufacturer_info);
        return view('admin.admin-master')
                        ->with('admin_main_content', $edit_manufacturer);
    }

    public function updateManufacturer(Request $request) {
        $this->authCheck();
        $data = array();
        $data['manufacturer_name'] = $request->manufacturer_name;
        $manufacturer_id = $request->manufacturer_id;
        DB::table('manufacturer')
                ->where('manufacturer_id', $manufacturer_id)
                ->update($data);
        return Redirect::to('/manage-manufacturer');
    }

    
    //  For Product
    
    public function addProduct() {
        $this->authCheck();
        $category = DB::table('category')
                ->orderBy("category_name","asc")
                ->get();
        $sub_category = DB::table('sub_category')
                ->get();
        $manufacturer = DB::table('manufacturer')
                ->orderBy("manufacturer_name","asc")
                ->get();
        $catalogAttributes = DB::table('catalog_attributes')->orderBy('category_id')->orderBy('display_order')->get()->groupBy('category_id');
        $specificationTemplates = config('catalog_specification_templates', []);
        $home = view('admin.admin-pages.add-product')
                ->with('category', $category)
                ->with('manufacturer', $manufacturer)
                ->with('sub_category', $sub_category)
                ->with('catalogAttributes', $catalogAttributes)
                ->with('specificationTemplates', $specificationTemplates);
        return view('admin.admin-master')
                        ->with('admin_main_content', $home);
    }

    public function saveProduct(Request $request) {
        $this->authCheck();
        $request->merge(['barcode' => $request->filled('barcode') ? trim($request->barcode) : null]);
        $this->validate($request, [
            'barcode' => 'nullable|string|max:64|unique:product,barcode',
            'regular_price' => 'required|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'product_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'gallery_images' => 'nullable|array|max:10',
            'gallery_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        $data = array();
        $data['product_id'] = $request->product_id;
        $data['sku'] = $request->sku ?: null;
        $data['barcode'] = $request->barcode;
        $data['category_id'] = $request->category_id;
        $data['sub_category'] = $request->sub_category_id;
        $data['manufacturer_id'] = $request->manufacturer_id;
        $data['product_model'] = $request->product_model;
        $data['product_name'] = $request->product_name;
        $data['product_description'] = $request->product_description;
        $data['short_description'] = $request->short_description;
        $data['key_features'] = json_encode($this->parseList($request->key_features));
        $data['specifications'] = json_encode($this->parseSpecifications($request->specifications));
        $data['gallery_images'] = json_encode($this->storeProductImages((array)$request->file('gallery_images', [])));
        $regularPrice = max(0,(float)$request->regular_price);
        $offerPrice = $request->filled('offer_price') ? max(0,(float)$request->offer_price) : null;
        $data['regular_price'] = $regularPrice;
        $data['offer_price'] = $offerPrice !== null && $offerPrice < $regularPrice ? $offerPrice : null;
        $data['purchase_price'] = max(0,(float)$request->purchase_price);
        $data['product_condition'] = $request->product_condition;
        $data['stock_quantity'] = max(0, (int) $request->stock_quantity);
        $data['warranty'] = $request->warranty;
        $data['publication_status'] = $request->publication_status;
        $data['top_product'] = $request->has('top_product') ? 1 : 0;
        $data['is_new_arrival'] = $request->has('is_new_arrival') ? 1 : 0;
        $data['seo_title'] = $request->seo_title;
        $data['seo_description'] = $request->seo_description;
        $image = $request->file('product_image');
        $data['product_image'] = $image ? $this->storeProductImage($image) : 'asset/front-end/img/home/pic 1.jpg';
        $productId = DB::table('product')->insertGetId($data);
        $this->syncProductAttributes($productId, $request);
        Session::put('message', 'Save Product Successfully');
        return Redirect::to('/add-product');
    }
    
   public function editProduct($id) {
        $this->authCheck();
        $product_info=DB::table('product')
                ->where('id',$id)
                ->first();
        $category = DB::table('category')
                ->orderBy("category_name","asc")
                ->get();
        $sub_category = DB::table('sub_category')
                ->get();
        $manufacturer = DB::table('manufacturer')
                ->orderBy("manufacturer_name","asc")
                ->get();
        $catalogAttributes = DB::table('catalog_attributes')->orderBy('category_id')->orderBy('display_order')->get()->groupBy('category_id');
        $specificationTemplates = config('catalog_specification_templates', []);
        $productAttributeValues = DB::table('product_attribute_values')->where('product_id',$id)->pluck('value','attribute_id');
        $edit_product = view('admin.admin-pages.edit-product')
                ->with('product_info', $product_info)
                ->with('category', $category)
                ->with('manufacturer', $manufacturer)
                ->with('sub_category', $sub_category)
                ->with('catalogAttributes', $catalogAttributes)
                ->with('specificationTemplates', $specificationTemplates)
                ->with('productAttributeValues', $productAttributeValues);
        return view('admin.admin-master')
                        ->with('admin_main_content', $edit_product);
    }
            

    public function updateProduct(Request $request) {
        $this->authCheck();
        $data = array();
        $id=$request->id;
        $request->merge(['barcode' => $request->filled('barcode') ? trim($request->barcode) : null]);
        $this->validate($request, [
            'barcode' => 'nullable|string|max:64|unique:product,barcode,'.$id,
            'regular_price' => 'required|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'product_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'gallery_images' => 'nullable|array|max:10',
            'gallery_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_gallery_images' => 'nullable|array',
        ]);
        $beforeProduct=DB::table('product')->where('id',$id)->first();
        $data['product_id'] = $request->product_id;
        $data['sku'] = $request->sku ?: null;
        $data['barcode'] = $request->barcode;
        $data['category_id'] = $request->category_id;
        $data['sub_category'] = $request->sub_category_id;
        $data['manufacturer_id'] = $request->manufacturer_id;
        $data['product_model'] = $request->product_model;
        $data['product_name'] = $request->product_name;
        $data['product_description'] = $request->product_description;
        $data['short_description'] = $request->short_description;
        $data['key_features'] = json_encode($this->parseList($request->key_features));
        $data['specifications'] = json_encode($this->parseSpecifications($request->specifications));
        $currentGallery=array_values(array_filter((array)json_decode($beforeProduct->gallery_images,true)));
        $removeGallery=array_values(array_intersect($currentGallery,(array)$request->input('remove_gallery_images',[])));
        $keptGallery=array_values(array_diff($currentGallery,$removeGallery));
        $galleryUploads=(array)$request->file('gallery_images',[]);
        if(count($keptGallery)+count($galleryUploads)>10)return Redirect::back()->withInput()->withErrors(['gallery_images'=>'A product can have a maximum of 10 gallery images. Remove existing images or select fewer new files.']);
        $newGallery=$this->storeProductImages($galleryUploads);
        $data['gallery_images']=json_encode(array_values(array_unique(array_merge($keptGallery,$newGallery))));
        $regularPrice = max(0,(float)$request->regular_price);
        $offerPrice = $request->filled('offer_price') ? max(0,(float)$request->offer_price) : null;
        $data['regular_price'] = $regularPrice;
        $data['offer_price'] = $offerPrice !== null && $offerPrice < $regularPrice ? $offerPrice : null;
        $data['purchase_price'] = max(0,(float)$request->purchase_price);
        $data['top_product'] = $request->has('top_product') ? 1 : 0;
        $data['is_new_arrival'] = $request->has('is_new_arrival') ? 1 : 0;
        $data['product_condition'] = $request->product_condition;
        $data['stock_quantity'] = max(0, (int) $request->stock_quantity);
        $data['warranty'] = $request->warranty;
        $data['seo_title'] = $request->seo_title;
        $data['seo_description'] = $request->seo_description;
        $image = $request->file('product_image');
        if ($image) {
            $data['product_image']=$this->storeProductImage($image);
        }
        DB::table('product')->where('id',$id)->update($data);
        if($image)$this->deleteOwnedProductImage($beforeProduct->product_image);
        foreach($removeGallery as $removedImage)$this->deleteOwnedProductImage($removedImage);
        $this->syncProductAttributes($id, $request);
        if($data['product_condition']==='In Stock' && (!$beforeProduct || $beforeProduct->product_condition!=='In Stock')) app(\App\Services\StockAlertService::class)->process($id);
        Session::put('message', 'Update Product Successfully');
        return Redirect::to('/manage-product');
    }
    
    
    
    public function manageProduct()
    {
        $this->authCheck();
        $all_product=DB::table('product')->select('product.*')->selectRaw(\App\Product::sellingPriceSql().' selling_price')->orderBy('id','DESC')->get();
        $manage_product = view('admin.admin-pages.manage-product')
                ->with('all_product', $all_product);
        return view('admin.admin-master')
                        ->with('admin_main_content', $manage_product);
    }

    public function unpublishedProduct($id) {
        DB::table('product')
                ->where('id', $id)
                ->update(['publication_status' => 0]);
        return Redirect::to('/manage-product');
    }

    public function publishedProduct($id) {
        DB::table('product')
                ->where('id', $id)
                ->update(['publication_status' => 1]);
        return Redirect::to('/manage-product');
    }

    public function deleteproduct($id) {
        DB::table('product')
                ->where('id', $id)
                ->delete();
        return Redirect::to('/manage-product');
    }

    public function bulkDeleteProducts(Request $request)
    {
        $this->validate($request,['product_ids'=>'required|array|min:1','product_ids.*'=>'required|integer|distinct|exists:product,id']);
        $ids=array_values(array_unique(array_map('intval',$request->product_ids)));
        $protected=[];
        foreach(['order_items','purchase_order_items','stock_receipts','service_claims','order_return_items','stock_transfer_items'] as $table) {
            if(\Schema::hasTable($table)) $protected=array_merge($protected,DB::table($table)->whereIn('product_id',$ids)->pluck('product_id')->map(function($id){return (int)$id;})->all());
        }
        $protected=array_values(array_unique($protected));$deletable=array_values(array_diff($ids,$protected));
        $products=$deletable?DB::table('product')->whereIn('id',$deletable)->select('id','product_image')->get():collect();
        $deleted=0;
        if($deletable) $deleted=DB::transaction(function()use($deletable){
            foreach(['product_attribute_values','product_reviews','product_questions','wishlists','stock_alerts','product_location_stock'] as $table) if(\Schema::hasTable($table)) DB::table($table)->whereIn('product_id',$deletable)->delete();
            return DB::table('product')->whereIn('id',$deletable)->delete();
        });
        foreach($products as $product){$path=ltrim((string)$product->product_image,'/');if(strpos($path,'asset/front-end/img/Product_image/')===0&&is_file(public_path($path)))unlink(public_path($path));}
        Cache::forget('xml-sitemap');
        return $this->bulkDeleteResult('/manage-product',$deleted,count($protected),'product','orders, purchasing, returns, service claims, or transfer history');
    }

    public function siteCustomization()
    {
        $this->authCheck();
        $settings = DB::table('site_settings')->pluck('setting_value', 'setting_key');
        $topAnnouncements = \Schema::hasTable('top_announcements') ? \App\TopAnnouncement::orderByDesc('priority')->orderBy('display_order')->get() : collect();
        $siteContactItems = \Schema::hasTable('site_contact_items') ? \App\SiteContactItem::orderByDesc('is_primary')->orderBy('display_order')->get() : collect();
        return view('admin.admin-pages.site-customization', compact('settings','topAnnouncements','siteContactItems'));
    }

    public function bannerManagement()
    {
        $this->authCheck();
        $banners = Banner::with(['product', 'category'])->orderBy('display_order')->orderByDesc('id')->get();
        $bannerProducts = Product::where('publication_status', 1)->orderBy('product_name')->get(['id','product_id','sku','product_name','product_image','regular_price','offer_price','publication_status']);
        $bannerCategories = Category::where('publication_status', 1)->orderBy('category_name')->get(['category_id','category_name']);
        return view('admin.admin-pages.banner-management', compact('banners', 'bannerProducts', 'bannerCategories'));
    }

    public function updateSiteSettings(Request $request)
    {
        $this->authCheck();
        $this->validate($request, [
            'site_name' => 'required|string|max:120',
            'site_tagline' => 'nullable|string|max:180',
            'notice_text' => 'nullable|string|max:300',
            'phone' => ['nullable','string','max:40','regex:/^[0-9+()\-\s.]+$/'],
            'support_phone' => ['nullable','string','max:40','regex:/^[0-9+()\-\s.]+$/'],
            'whatsapp_number' => ['nullable','string','max:40','regex:/^[0-9+()\-\s.]+$/'],
            'support_email' => 'nullable|email|max:150',
            'shop_address' => 'nullable|string|max:500',
            'business_hours' => 'nullable|string|max:180',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'favicon' => 'nullable|mimes:ico,png|max:512',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'google_analytics_id' => ['nullable', 'regex:/^G-[A-Z0-9]+$/i', 'max:30'],
            'google_site_verification' => 'nullable|string|max:255',
            'default_meta_title' => 'nullable|string|max:70',
            'default_meta_description' => 'nullable|string|max:320',
            'meta_keywords' => 'nullable|string|max:500',
            'robots_directive' => ['nullable', \Illuminate\Validation\Rule::in(['index,follow','index,nofollow','noindex,follow','noindex,nofollow'])],
            'seo_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'footer_description' => 'nullable|string|max:500',
            'copyright_text' => 'nullable|string|max:255',
            'hero_side_title' => 'nullable|string|max:120',
            'hero_side_text' => 'nullable|string|max:240',
            'development_mode_enabled' => 'required|boolean',
            'development_mode_message_type' => ['required', \Illuminate\Validation\Rule::in(['development','maintenance','coming_soon','system_upgrade','emergency','custom'])],
            'development_mode_title' => 'required|string|max:150',
            'development_mode_message' => 'required|string|max:2000',
            'development_mode_additional_message' => 'nullable|string|max:2000',
            'development_mode_availability_text' => 'nullable|string|max:255',
            'development_mode_show_admin_login' => 'required|boolean',
            'development_mode_login_button_text' => 'nullable|string|max:100',
        ]);
        $previousDevelopmentMode = DB::table('site_settings')
            ->where('setting_key', 'development_mode_enabled')->value('setting_value');
        $allowed = [
            'site_name','site_tagline','notice_text','phone','support_phone','whatsapp_number',
            'support_email','shop_address','business_hours','facebook_url','instagram_url',
            'youtube_url','linkedin_url','twitter_url','default_meta_description',
            'default_meta_title','meta_keywords','robots_directive','google_analytics_id',
            'google_site_verification','footer_description','copyright_text','hero_side_title','hero_side_text',
            'development_mode_enabled','development_mode_message_type','development_mode_title',
            'development_mode_message','development_mode_additional_message',
            'development_mode_availability_text','development_mode_show_admin_login',
            'development_mode_login_button_text'
        ];
        foreach ($allowed as $key) {
            $value = null;
            if ($request->filled($key)) {
                $value = (string)$request->input($key);
                $value = preg_replace('/<(script|iframe|object|embed|style)\b[^>]*>.*?<\/\1>/is', '', $value);
                $value = trim(strip_tags($value));
            }
            DB::table('site_settings')->updateOrInsert(['setting_key' => $key], [
                'setting_value' => $value,
                'updated_at' => now(), 'created_at' => now()
            ]);
        }
        foreach (['logo' => 'site_logo', 'favicon' => 'favicon', 'seo_image' => 'default_og_image'] as $input => $settingKey) {
            if (!$request->hasFile($input)) continue;
            $file = $request->file($input);
            $path = 'asset/front-end/img/branding/';
            if (!is_dir(public_path($path))) mkdir(public_path($path), 0755, true);
            $name = $input.'-'.str_random(12).'.'.strtolower($file->getClientOriginalExtension());
            $file->move(public_path($path), $name);
            $oldPath = DB::table('site_settings')->where('setting_key', $settingKey)->value('setting_value');
            DB::table('site_settings')->updateOrInsert(['setting_key' => $settingKey], [
                'setting_value' => $path.$name, 'updated_at' => now(), 'created_at' => now()
            ]);
            if ($oldPath && strpos($oldPath, $path) === 0 && is_file(public_path($oldPath))) unlink(public_path($oldPath));
        }
        Cache::forget('site-settings');
        $newDevelopmentMode = (string)$request->input('development_mode_enabled') === '1';
        $previouslyEnabled = in_array($previousDevelopmentMode, [true, 1, '1', 'true', 'on'], true);
        if ($newDevelopmentMode !== $previouslyEnabled && \Schema::hasTable('admin_activity_logs')) {
            DB::table('admin_activity_logs')->insert([
                'admin_id' => session('admin_id'),
                'admin_name' => session('admin_name'),
                'action' => 'Development Mode Updated',
                'method' => 'POST',
                'path' => '/site-settings',
                'ip_hash' => hash_hmac('sha256', (string)$request->ip(), config('app.key')),
                'details' => json_encode(['previous_status' => $previouslyEnabled, 'new_status' => $newDevelopmentMode, 'message_type' => $request->development_mode_message_type]),
                'created_at' => now(),
            ]);
        }
        if ($newDevelopmentMode && !$previouslyEnabled) {
            $message = 'Development Mode has been enabled. Public visitors will now see the configured Development Mode message.';
        } elseif (!$newDevelopmentMode && $previouslyEnabled) {
            $message = 'Development Mode has been disabled. The public website is now available.';
        } else {
            $message = 'Site settings updated.';
        }
        $destination = $newDevelopmentMode !== $previouslyEnabled
            ? '/site-customization#development-mode'
            : '/site-customization';
        return Redirect::to($destination)->with('message', $message);
    }

    public function saveBanner(Request $request)
    {
        $this->authCheck();
        $data = $this->validatedBannerData($request);
        $desktopImage = $request->hasFile('desktop_image') ? $this->storeBannerImage($request->file('desktop_image'), 'desktop') : null;
        $mobileImage = $request->hasFile('mobile_image') ? $this->storeBannerImage($request->file('mobile_image'), 'mobile') : null;
        $data['image_path'] = $desktopImage;
        $data['mobile_image'] = $mobileImage;
        try { Banner::create($data); }
        catch (\Throwable $exception) { $this->removeBannerFile($desktopImage); $this->removeBannerFile($mobileImage); throw $exception; }
        return Redirect::to('/banner-management')->with('message', 'Banner added.');
    }

    public function updateBanner(Request $request, $id)
    {
        $this->authCheck();
        $banner = Banner::findOrFail($id);
        $data = $this->validatedBannerData($request, $banner);
        $oldDesktop = $banner->image_path;
        $oldMobile = $banner->mobile_image;
        $newDesktop = $request->hasFile('desktop_image') ? $this->storeBannerImage($request->file('desktop_image'), 'desktop') : null;
        $newMobile = $request->hasFile('mobile_image') ? $this->storeBannerImage($request->file('mobile_image'), 'mobile') : null;
        if ($newDesktop) $data['image_path'] = $newDesktop;
        if ($newMobile) $data['mobile_image'] = $newMobile;
        if ($request->has('remove_mobile_image')) $data['mobile_image'] = null;
        try { $banner->update($data); }
        catch (\Throwable $exception) { $this->removeBannerFile($newDesktop); $this->removeBannerFile($newMobile); throw $exception; }
        if ($newDesktop) $this->removeBannerFileIfUnused($oldDesktop, $banner->id);
        if ($newMobile || $request->has('remove_mobile_image')) $this->removeBannerFileIfUnused($oldMobile, $banner->id);
        return Redirect::to('/banner-management')->with('message', 'Banner updated.');
    }

    public function bannerProductPreview($id)
    {
        $this->authCheck();
        $product = Product::where('publication_status', 1)->findOrFail($id);
        return response()->json($this->productBannerData($product));
    }

    public function toggleBanner($id)
    {
        $this->authCheck();
        $banner = Banner::findOrFail($id);
        $banner->update(['is_active' => !$banner->is_active]);
        return Redirect::to('/banner-management')->with('message', $banner->is_active ? 'Banner is now visible.' : 'Banner is now hidden.');
    }

    public function deleteBanner($id)
    {
        $this->authCheck();
        $banner = Banner::findOrFail($id);
        $desktop = $banner->image_path;
        $mobile = $banner->mobile_image;
        $banner->delete();
        $this->removeBannerFileIfUnused($desktop, $id);
        $this->removeBannerFileIfUnused($mobile, $id);
        return Redirect::to('/banner-management')->with('message', 'Banner deleted.');
    }

    private function validatedBannerData(Request $request, Banner $existing = null)
    {
        $types = ['custom','product','category','campaign','information'];
        $positions = ['center','top','bottom','left','right'];
        $validator = Validator::make($request->all(), [
            'banner_type' => ['required', Rule::in($types)],
            'product_id' => ['nullable','integer',Rule::exists('product','id')->where(function ($query) { $query->where('publication_status', 1); })],
            'category_id' => ['nullable','integer',Rule::exists('category','category_id')->where(function ($query) { $query->where('publication_status', 1); })],
            'link_url' => ['nullable','string','max:255'],
            'title' => ['nullable','string','max:255'],
            'subtitle' => ['nullable','string','max:255'],
            'button_text' => ['nullable','string','max:100'],
            'desktop_image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
            'mobile_image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
            'image_position' => ['required', Rule::in($positions)],
            'display_order' => ['required','integer','min:0'],
            'starts_at' => ['nullable','date'],
            'expires_at' => ['nullable','date','after_or_equal:starts_at'],
        ]);
        $validator->after(function ($validator) use ($request, $existing) {
            $type = $request->input('banner_type');
            if ($type === 'product' && !$request->filled('product_id')) $validator->errors()->add('product_id', 'Choose a published product.');
            if ($type === 'category' && !$request->filled('category_id')) $validator->errors()->add('category_id', 'Choose a published category.');
            if ($type === 'custom' && !$request->filled('link_url')) $validator->errors()->add('link_url', 'Enter an internal path or HTTPS destination.');
            if (in_array($type, ['custom','campaign'], true) && $request->filled('link_url') && !$this->isSafeBannerUrl($request->input('link_url'))) $validator->errors()->add('link_url', 'Use an internal path beginning with / or a valid HTTPS URL.');
            $useProduct = $request->boolean('use_product_image');
            if ($useProduct && !$request->filled('product_id')) $validator->errors()->add('use_product_image', 'Choose a product before using its image.');
            $hasDesktop = $request->hasFile('desktop_image') || ($existing && $existing->image_path);
            if (!$hasDesktop) $validator->errors()->add('desktop_image', 'Upload a dedicated desktop banner image. It remains the safe fallback in product-image mode.');
            if ($useProduct && $request->filled('product_id')) {
                $product = Product::where('publication_status', 1)->find($request->product_id);
                if ((!$product || !$product->product_image) && !$hasDesktop) $validator->errors()->add('desktop_image', 'This product has no image. Upload a desktop banner fallback.');
            }
        });
        $validator->validate();

        $type = $request->banner_type;
        $product = $type === 'product' || $request->boolean('use_product_image') ? Product::where('publication_status', 1)->find($request->product_id) : null;
        $title = $request->filled('title') ? trim($request->title) : ($product ? $product->product_name : null);
        $subtitle = $request->filled('subtitle') ? trim($request->subtitle) : ($product ? $this->productBannerData($product)['subtitle'] : null);
        return [
            'banner_type' => $type,
            'product_id' => $product ? $product->id : null,
            'category_id' => $type === 'category' ? (int)$request->category_id : null,
            'title' => $title,
            'subtitle' => $subtitle,
            'button_text' => $request->filled('button_text') ? trim($request->button_text) : ($type === 'product' ? 'Shop Now' : null),
            'link_url' => in_array($type, ['custom','campaign'], true) && $request->filled('link_url') ? trim($request->link_url) : null,
            'use_product_image' => $request->boolean('use_product_image'),
            'image_position' => $request->image_position,
            'show_overlay' => $request->boolean('show_overlay'),
            'display_order' => (int)$request->display_order,
            'starts_at' => $request->filled('starts_at') ? $request->starts_at : null,
            'expires_at' => $request->filled('expires_at') ? $request->expires_at : null,
            'open_in_new_tab' => $request->boolean('open_in_new_tab'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function productBannerData(Product $product)
    {
        $discount = $product->discount_percent;
        $price = number_format($product->selling_price, 0);
        return [
            'id' => $product->id,
            'name' => $product->product_name,
            'sku' => $product->sku ?: $product->product_id,
            'regular_price' => (float)$product->regular_price,
            'selling_price' => (float)$product->selling_price,
            'discount_percent' => $discount,
            'subtitle' => $discount ? 'Save '.$discount.'% — Now ৳'.$price : 'Now ৳'.$price,
            'button_text' => 'Shop Now',
            'image' => $product->image_url,
            'url' => route('store.product.show', $product->id),
            'status' => $product->publication_status ? 'Published' : 'Hidden',
        ];
    }

    private function storeBannerImage($file, $prefix)
    {
        $path = 'asset/front-end/img/banners/';
        if (!is_dir(public_path($path))) mkdir(public_path($path), 0755, true);
        $name = $prefix.'-'.str_random(24).'.'.strtolower($file->getClientOriginalExtension());
        $file->move(public_path($path), $name);
        return $path.$name;
    }

    private function isSafeBannerUrl($url)
    {
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) return true;
        return filter_var($url, FILTER_VALIDATE_URL) && strtolower((string)parse_url($url, PHP_URL_SCHEME)) === 'https';
    }

    private function removeBannerFileIfUnused($path, $excludingId)
    {
        if (!$path || strpos($path, 'asset/front-end/img/banners/') !== 0) return;
        $used = Banner::where('id', '<>', $excludingId)->where(function ($query) use ($path) { $query->where('image_path', $path)->orWhere('mobile_image', $path); })->exists();
        if (!$used) $this->removeBannerFile($path);
    }

    private function removeBannerFile($path)
    {
        if (!$path || strpos($path, 'asset/front-end/img/banners/') !== 0) return;
        $fullPath = public_path($path);
        if (is_file($fullPath)) unlink($fullPath);
    }

    public function customerInbox()
    {
        $this->authCheck();
        $reviews = DB::table('product_reviews')->join('product','product_reviews.product_id','=','product.id')->select('product_reviews.*','product.product_name')->latest('product_reviews.created_at')->get();
        $questions = DB::table('product_questions')->join('product','product_questions.product_id','=','product.id')->select('product_questions.*','product.product_name')->latest('product_questions.created_at')->get();
        $supportRequests = DB::table('support_requests')->latest()->get();
        return view('admin.admin-pages.customer-inbox', compact('reviews','questions','supportRequests'));
    }

    public function moderateReview(Request $request, $id)
    {
        $this->authCheck();
        if ($request->action === 'delete') DB::table('product_reviews')->where('id',$id)->delete();
        else DB::table('product_reviews')->where('id',$id)->update(['is_approved'=>$request->action === 'approve' ? 1 : 0,'updated_at'=>now()]);
        return Redirect::to('/customer-inbox')->with('message','Review updated.');
    }

    public function answerQuestion(Request $request, $id)
    {
        $this->authCheck();
        if ($request->action === 'delete') DB::table('product_questions')->where('id',$id)->delete();
        else {
            $this->validate($request,['answer'=>'required|min:2|max:3000']);
            DB::table('product_questions')->where('id',$id)->update(['answer'=>$request->answer,'is_approved'=>1,'answered_at'=>now(),'updated_at'=>now()]);
        }
        return Redirect::to('/customer-inbox')->with('message','Question updated.');
    }

    public function updateSupportRequest(Request $request, $id)
    {
        $this->authCheck();
        $this->validate($request,['status'=>'required|in:new,in_progress,resolved','admin_note'=>'nullable|max:3000']);
        DB::table('support_requests')->where('id',$id)->update(['status'=>$request->status,'admin_note'=>$request->admin_note,'updated_at'=>now()]);
        return Redirect::to('/customer-inbox')->with('message','Support request updated.');
    }

    public function manageOrders(Request $request)
    {
        $this->authCheck();
        $query = DB::table('orders')->latest();
        if ($request->filled('status')) $query->where('status',$request->status);
        if ($request->filled('search')) {
            $term=$request->search;
            $query->where(function($q) use($term){$q->where('order_number','like','%'.$term.'%')->orWhere('customer_name','like','%'.$term.'%')->orWhere('phone','like','%'.$term.'%');});
        }
        $orders=$query->paginate(20)->appends($request->query());
        return view('admin.admin-pages.manage-orders',compact('orders'));
    }

    public function viewOrder($id)
    {
        $this->authCheck();
        $order=DB::table('orders')->where('id',$id)->first();
        abort_unless($order,404);
        $items=DB::table('order_items')->where('order_id',$id)->get();
        return view('admin.admin-pages.view-order',compact('order','items'));
    }

    public function updateOrderStatus(Request $request,$id)
    {
        $this->authCheck();
        $this->validate($request,['status'=>'required|in:pending,confirmed,processing,shipped,delivered,cancelled','delivery_charge'=>'nullable|numeric|min:0']);
        $order=DB::table('orders')->where('id',$id)->first();
        abort_unless($order,404);
        $delivery=$request->filled('delivery_charge')?(float)$request->delivery_charge:(float)$order->delivery_charge;
        DB::table('orders')->where('id',$id)->update(['status'=>$request->status,'delivery_charge'=>$delivery,'total'=>(float)$order->subtotal-(float)$order->discount+$delivery,'updated_at'=>now()]);
        if($order->status!==$request->status) {
            $updated=DB::table('orders')->where('id',$id)->first();
            app(\App\Services\OrderNotifier::class)->customer($updated,'Order '.$updated->order_number.' is '.ucfirst($updated->status),'Your order status has been updated to '.ucfirst($updated->status).'.');
            app(\App\Services\WebhookService::class)->dispatch('order.updated',['order_id'=>$updated->id,'order_number'=>$updated->order_number,'status'=>$updated->status,'total'=>(float)$updated->total]);
        }
        return Redirect::to('/manage-orders/'.$id)->with('message','Order updated.');
    }

    public function inventory(Request $request)
    {
        $this->authCheck();
        $query=DB::table('product')->orderBy('product_name');
        if ($request->filter==='low') $query->where('stock_tracking',1)->whereBetween('stock_quantity',[1,5]);
        if ($request->filter==='out') $query->where('stock_tracking',1)->where('stock_quantity',0);
        if ($request->filter==='untracked') $query->where('stock_tracking',0);
        if ($request->filled('search')) $query->where(function($q)use($request){$q->where('product_name','like','%'.$request->search.'%')->orWhere('sku','like','%'.$request->search.'%');});
        $products=$query->paginate(25)->appends($request->query());
        $counts=['tracked'=>DB::table('product')->where('stock_tracking',1)->count(),'low'=>DB::table('product')->where('stock_tracking',1)->whereBetween('stock_quantity',[1,5])->count(),'out'=>DB::table('product')->where('stock_tracking',1)->where('stock_quantity',0)->count()];
        return view('admin.admin-pages.inventory',compact('products','counts'));
    }

    public function updateInventory(Request $request,$id)
    {
        $this->authCheck();
        $this->validate($request,['stock_quantity'=>'required|integer|min:0|max:100000']);
        $before=DB::table('product')->where('id',$id)->first();
        $quantity=(int)$request->stock_quantity;
        DB::table('product')->where('id',$id)->update(['stock_quantity'=>$quantity,'stock_tracking'=>$request->has('stock_tracking')?1:0,'product_condition'=>$request->has('stock_tracking')?($quantity>0?'In Stock':'Out Of Stock'):$request->product_condition,'updated_at'=>now()]);
        if(\Schema::hasTable('inventory_locations')){$location=DB::table('inventory_locations')->where('is_default',1)->first();if($location){DB::table('product_location_stock')->updateOrInsert(['location_id'=>$location->id,'product_id'=>$id],['quantity'=>$quantity,'updated_at'=>now(),'created_at'=>now()]);$total=DB::table('product_location_stock')->where('product_id',$id)->sum('quantity');DB::table('product')->where('id',$id)->update(['stock_quantity'=>$total,'product_condition'=>$total>0?'In Stock':'Out Of Stock']);}}
        $after=DB::table('product')->where('id',$id)->first();$notified=0;
        if($after && $after->product_condition==='In Stock' && (!$before || $before->product_condition!=='In Stock')) $notified=app(\App\Services\StockAlertService::class)->process($id);
        if($after) app(\App\Services\WebhookService::class)->dispatch('inventory.updated',['product_id'=>$after->id,'sku'=>$after->sku,'stock_quantity'=>$after->stock_quantity,'condition'=>$after->product_condition]);
        return Redirect::back()->with('message','Inventory updated.'.($notified?' '.$notified.' stock alert(s) processed.':''));
    }

    public function catalogAttributes(Request $request)
    {
        $this->authCheck();
        $categories=DB::table('category')->where('publication_status',1)->orderBy('category_name')->get();
        $query=DB::table('catalog_attributes')->join('category','catalog_attributes.category_id','=','category.category_id')
            ->select('catalog_attributes.*','category.category_name',DB::raw('(SELECT COUNT(*) FROM product_attribute_values pav WHERE pav.attribute_id = catalog_attributes.id) as usage_count'))
            ->orderBy('category.category_name')->orderBy('catalog_attributes.display_order')->orderBy('catalog_attributes.name');
        if($request->filled('category_id')) $query->where('catalog_attributes.category_id',$request->category_id);
        if($request->filled('search')) $query->where(function($q)use($request){$term='%'.trim($request->search).'%';$q->where('catalog_attributes.name','like',$term)->orWhere('catalog_attributes.slug','like',$term);});
        $attributes=$query->get();
        $categoryStats=DB::table('catalog_attributes')->select('category_id',DB::raw('COUNT(*) as attribute_count'),DB::raw('SUM(is_filterable) as filter_count'),DB::raw('SUM(is_comparable) as compare_count'))->groupBy('category_id')->get()->keyBy('category_id');
        return view('admin.admin-pages.catalog-attributes',compact('categories','attributes','categoryStats'));
    }

    public function saveCatalogAttribute(Request $request)
    {
        $this->authCheck();
        if($request->has('attributes')){
            $this->validate($request,['category_id'=>'required|integer|exists:category,category_id','attributes'=>'required|array|min:1|max:30','attributes.*.name'=>'required|string|max:100','attributes.*.input_type'=>'required|in:select,multiselect,text','attributes.*.display_order'=>'nullable|integer|min:0']);
            $rows=[];$slugs=[];
            foreach($request->attributes as $index=>$attribute){
                $slug=str_slug($attribute['name']);
                if(in_array($slug,$slugs,true)||DB::table('catalog_attributes')->where('category_id',$request->category_id)->where('slug',$slug)->exists())return Redirect::back()->withInput()->with('exception','Duplicate attribute: '.$attribute['name'].'. Remove it or rename it before saving.');
                $slugs[]=$slug;$options=in_array($attribute['input_type'],['select','multiselect'],true)?$this->parseList(isset($attribute['options'])?$attribute['options']:''):[];
                if(in_array($attribute['input_type'],['select','multiselect'],true)&&!$options)return Redirect::back()->withInput()->with('exception','Add at least one option for '.$attribute['name'].'.');
                $rows[]=['category_id'=>(int)$request->category_id,'name'=>trim($attribute['name']),'slug'=>$slug,'input_type'=>$attribute['input_type'],'options'=>json_encode($options),'is_filterable'=>!empty($attribute['is_filterable'])?1:0,'is_comparable'=>!empty($attribute['is_comparable'])?1:0,'display_order'=>max(0,(int)(isset($attribute['display_order'])?$attribute['display_order']:(($index+1)*10))),'created_at'=>now(),'updated_at'=>now()];
            }
            DB::transaction(function()use($rows){DB::table('catalog_attributes')->insert($rows);});
            return Redirect::to('/catalog-attributes?category_id='.$request->category_id)->with('message',count($rows).' attributes created.');
        }
        $this->validate($request,['category_id'=>'required|integer','name'=>'required|max:100','input_type'=>'required|in:select,multiselect,text','display_order'=>'nullable|integer|min:0']);
        $slug=str_slug($request->name);
        $exists=DB::table('catalog_attributes')->where('category_id',$request->category_id)->where('slug',$slug)->exists();
        if($exists) return Redirect::back()->withInput()->with('exception','That attribute already exists for this category.');
        $options=in_array($request->input_type,['select','multiselect'],true)?$this->parseList($request->options):[];
        if(in_array($request->input_type,['select','multiselect'],true)&&!$options)return Redirect::back()->withInput()->with('exception','Add at least one option for a selection attribute.');
        DB::table('catalog_attributes')->insert(['category_id'=>$request->category_id,'name'=>$request->name,'slug'=>$slug,'input_type'=>$request->input_type,'options'=>json_encode($options),'is_filterable'=>$request->has('is_filterable')?1:0,'is_comparable'=>$request->has('is_comparable')?1:0,'display_order'=>max(0,(int)$request->display_order),'created_at'=>now(),'updated_at'=>now()]);
        return Redirect::to('/catalog-attributes?category_id='.$request->category_id)->with('message','Attribute created.');
    }

    public function updateCatalogAttribute(Request $request, $id)
    {
        $this->authCheck();
        $attribute=DB::table('catalog_attributes')->where('id',$id)->first();
        if(!$attribute)return Redirect::to('/catalog-attributes')->with('exception','Attribute not found.');
        $this->validate($request,['category_id'=>'required|integer|exists:category,category_id','name'=>'required|string|max:120','input_type'=>'required|in:select,multiselect,text','display_order'=>'nullable|integer|min:0']);
        $slug=str_slug($request->name);
        if(DB::table('catalog_attributes')->where('category_id',$request->category_id)->where('slug',$slug)->where('id','<>',$id)->exists())return Redirect::back()->with('exception','That attribute already exists in this category.');
        $options=in_array($request->input_type,['select','multiselect'],true)?$this->parseList($request->options):[];
        if(in_array($request->input_type,['select','multiselect'],true)&&!$options)return Redirect::back()->with('exception','Add at least one option for a selection attribute.');
        DB::table('catalog_attributes')->where('id',$id)->update(['category_id'=>$request->category_id,'name'=>trim($request->name),'slug'=>$slug,'input_type'=>$request->input_type,'options'=>json_encode($options),'is_filterable'=>$request->has('is_filterable')?1:0,'is_comparable'=>$request->has('is_comparable')?1:0,'display_order'=>max(0,(int)$request->display_order),'updated_at'=>now()]);
        return Redirect::to('/catalog-attributes?category_id='.$request->category_id)->with('message','Attribute updated.');
    }

    public function toggleCatalogAttribute(Request $request, $id)
    {
        $this->authCheck();
        $field=$request->input('field');
        if(!in_array($field,['is_filterable','is_comparable'],true))return Redirect::back()->with('exception','Invalid attribute setting.');
        $attribute=DB::table('catalog_attributes')->where('id',$id)->first();
        if(!$attribute)return Redirect::back()->with('exception','Attribute not found.');
        DB::table('catalog_attributes')->where('id',$id)->update([$field=>$attribute->{$field}?0:1,'updated_at'=>now()]);
        return Redirect::back()->with('message','Attribute setting updated.');
    }

    public function duplicateCatalogAttribute($id)
    {
        $this->authCheck();
        $attribute=DB::table('catalog_attributes')->where('id',$id)->first();
        if(!$attribute)return Redirect::back()->with('exception','Attribute not found.');
        $name=$attribute->name.' Copy';$slug=str_slug($name);$suffix=2;
        while(DB::table('catalog_attributes')->where('category_id',$attribute->category_id)->where('slug',$slug)->exists()){$name=$attribute->name.' Copy '.$suffix++;$slug=str_slug($name);}
        DB::table('catalog_attributes')->insert(['category_id'=>$attribute->category_id,'name'=>$name,'slug'=>$slug,'input_type'=>$attribute->input_type,'options'=>$attribute->options,'is_filterable'=>$attribute->is_filterable,'is_comparable'=>$attribute->is_comparable,'display_order'=>$attribute->display_order+1,'created_at'=>now(),'updated_at'=>now()]);
        return Redirect::back()->with('message','Attribute duplicated.');
    }

    public function bulkDeleteCatalogAttributes(Request $request)
    {
        $this->authCheck();
        $this->validate($request,['attribute_ids'=>'required|array|min:1','attribute_ids.*'=>'integer|distinct|exists:catalog_attributes,id']);
        $count=DB::table('catalog_attributes')->whereIn('id',$request->attribute_ids)->delete();
        return Redirect::back()->with('message',$count.' attribute'.($count===1?'':'s').' deleted.');
    }

    public function reorderCatalogAttributes(Request $request)
    {
        $this->authCheck();
        $this->validate($request,['category_id'=>'required|integer','attribute_ids'=>'required|array','attribute_ids.*'=>'integer|distinct']);
        $valid=DB::table('catalog_attributes')->where('category_id',$request->category_id)->whereIn('id',$request->attribute_ids)->pluck('id')->map(function($id){return (int)$id;})->all();
        if(count($valid)!==count($request->attribute_ids))return response()->json(['message'=>'Invalid attribute order.'],422);
        DB::transaction(function()use($request){foreach($request->attribute_ids as $index=>$id)DB::table('catalog_attributes')->where('id',$id)->update(['display_order'=>($index+1)*10,'updated_at'=>now()]);});
        return response()->json(['message'=>'Order saved.']);
    }

    public function deleteCatalogAttribute($id)
    {
        $this->authCheck();
        DB::table('catalog_attributes')->where('id',$id)->delete();
        return Redirect::back()->with('message','Attribute deleted.');
    }

    public function deliveryZones()
    {
        $this->authCheck();
        $zones=DB::table('delivery_zones')->orderBy('display_order')->get();
        return view('admin.admin-pages.delivery-zones',compact('zones'));
    }

    public function saveDeliveryZone(Request $request)
    {
        $this->authCheck();
        $this->validate($request,['name'=>'required|max:120','charge'=>'required|numeric|min:0','free_delivery_minimum'=>'nullable|numeric|min:0','estimated_time'=>'nullable|max:120','display_order'=>'nullable|integer|min:0']);
        $data=['name'=>$request->name,'areas'=>$request->areas,'charge'=>$request->charge,'free_delivery_minimum'=>$request->free_delivery_minimum?:null,'estimated_time'=>$request->estimated_time,'is_active'=>(int)$request->input('is_active',0)===1?1:0,'display_order'=>max(0,(int)$request->display_order),'updated_at'=>now()];
        if($request->filled('id')) DB::table('delivery_zones')->where('id',$request->id)->update($data);
        else { $data['created_at']=now(); DB::table('delivery_zones')->insert($data); }
        return Redirect::to('/delivery-zones')->with('message','Delivery zone saved.');
    }

    public function toggleDeliveryZone($id)
    {
        $this->authCheck();
        $zone=DB::table('delivery_zones')->where('id',$id)->first();
        if($zone) DB::table('delivery_zones')->where('id',$id)->update(['is_active'=>$zone->is_active?0:1,'updated_at'=>now()]);
        return Redirect::back()->with('message','Delivery zone status updated.');
    }

    public function deleteDeliveryZone($id)
    {
        $this->authCheck();
        $used=DB::table('orders')->where('delivery_zone_id',$id)->exists();
        if($used) return Redirect::back()->with('exception','This zone is used by existing orders and cannot be deleted. Disable it instead.');
        DB::table('delivery_zones')->where('id',$id)->delete();
        return Redirect::back()->with('message','Delivery zone deleted.');
    }

    public function coupons()
    {
        $coupons=DB::table('coupons')->orderByDesc('id')->get();
        return view('admin.admin-pages.coupons',compact('coupons'));
    }

    public function saveCoupon(Request $request)
    {
        $this->validate($request,['code'=>'required|max:60','discount_type'=>'required|in:fixed,percent','discount_value'=>'required|numeric|min:0.01','minimum_order'=>'nullable|numeric|min:0','maximum_discount'=>'nullable|numeric|min:0','usage_limit'=>'nullable|integer|min:1','starts_at'=>'nullable|date','expires_at'=>'nullable|date|after:starts_at']);
        if($request->discount_type==='percent' && (float)$request->discount_value>100) return Redirect::back()->withInput()->with('exception','Percentage discount cannot exceed 100%.');
        $code=strtoupper(trim($request->code));
        $duplicate=DB::table('coupons')->where('code',$code)->when($request->id,function($query) use($request){$query->where('id','<>',$request->id);})->exists();
        if($duplicate) return Redirect::back()->withInput()->with('exception','That coupon code already exists.');
        $data=['code'=>$code,'description'=>$request->description,'discount_type'=>$request->discount_type,'discount_value'=>$request->discount_value,'minimum_order'=>$request->minimum_order?:0,'maximum_discount'=>$request->maximum_discount?:null,'usage_limit'=>$request->usage_limit?:null,'starts_at'=>$request->starts_at?:null,'expires_at'=>$request->expires_at?:null,'is_active'=>(int)$request->input('is_active',0)===1?1:0,'updated_at'=>now()];
        if($request->filled('id')) DB::table('coupons')->where('id',$request->id)->update($data); else {$data['created_at']=now();DB::table('coupons')->insert($data);}
        return Redirect::to('/coupons')->with('message','Coupon saved.');
    }

    public function toggleCoupon($id)
    {
        $coupon=DB::table('coupons')->where('id',$id)->first();
        if($coupon) DB::table('coupons')->where('id',$id)->update(['is_active'=>$coupon->is_active?0:1,'updated_at'=>now()]);
        return Redirect::back()->with('message','Coupon status updated.');
    }

    public function deleteCoupon($id)
    {
        if(DB::table('orders')->where('coupon_id',$id)->exists()) return Redirect::back()->with('exception','This coupon was used in an order. Disable it instead.');
        DB::table('coupons')->where('id',$id)->delete();
        return Redirect::back()->with('message','Coupon deleted.');
    }

    public function adminNotifications()
    {
        $notifications=DB::table('store_notifications')->where('recipient_type','admin')->latest()->paginate(30);
        return view('admin.admin-pages.notifications',compact('notifications'));
    }

    public function readAdminNotification($id)
    {
        $notification=DB::table('store_notifications')->where('id',$id)->where('recipient_type','admin')->first();
        abort_unless($notification,404);
        DB::table('store_notifications')->where('id',$id)->update(['read_at'=>now(),'updated_at'=>now()]);
        return $notification->action_url?redirect($notification->action_url):Redirect::to('/admin-notifications');
    }

    public function readAllAdminNotifications()
    {
        DB::table('store_notifications')->where('recipient_type','admin')->whereNull('read_at')->update(['read_at'=>now(),'updated_at'=>now()]);
        return Redirect::back()->with('message','All notifications marked as read.');
    }

    public function stockAlerts(Request $request)
    {
        $query=DB::table('stock_alerts')->join('product','product.id','=','stock_alerts.product_id')->select('stock_alerts.*','product.product_name','product.product_condition')->latest('stock_alerts.created_at');
        if($request->filled('status'))$query->where('stock_alerts.status',$request->status);
        $alerts=$query->paginate(30)->appends($request->query());
        $counts=['waiting'=>DB::table('stock_alerts')->where('status','waiting')->count(),'notified'=>DB::table('stock_alerts')->where('status','notified')->count()];
        return view('admin.admin-pages.stock-alerts',compact('alerts','counts'));
    }

    public function deleteStockAlert($id)
    {
        DB::table('stock_alerts')->where('id',$id)->delete();return Redirect::back()->with('message','Stock alert deleted.');
    }

    public function serviceClaims(Request $request)
    {
        $query=DB::table('service_claims')->latest();if($request->filled('status'))$query->where('status',$request->status);if($request->filled('search')){$term=$request->search;$query->where(function($q)use($term){$q->where('claim_number','like','%'.$term.'%')->orWhere('customer_name','like','%'.$term.'%')->orWhere('product_name','like','%'.$term.'%')->orWhere('phone','like','%'.$term.'%');});}$claims=$query->paginate(30)->appends($request->query());return view('admin.admin-pages.service-claims',compact('claims'));
    }

    public function viewServiceClaim($id)
    {
        $claim=DB::table('service_claims')->where('id',$id)->first();abort_unless($claim,404);return view('admin.admin-pages.service-claim',compact('claim'));
    }

    public function updateServiceClaim(Request $request,$id)
    {
        $this->validate($request,['status'=>'required|in:submitted,reviewing,approved,item_received,in_service,ready,completed,rejected','admin_note'=>'nullable|max:4000']);$claim=DB::table('service_claims')->where('id',$id)->first();abort_unless($claim,404);DB::table('service_claims')->where('id',$id)->update(['status'=>$request->status,'admin_note'=>$request->admin_note,'updated_at'=>now()]);if($claim->status!==$request->status&&$claim->user_id)DB::table('store_notifications')->insert(['recipient_type'=>'customer','user_id'=>$claim->user_id,'order_id'=>$claim->order_id,'email'=>$claim->email,'title'=>'Service request '.$claim->claim_number.' updated','message'=>'Your service request is now '.ucwords(str_replace('_',' ',$request->status)).'.'.($request->admin_note?' '.$request->admin_note:''),'action_url'=>url('/service-request/'.$claim->id),'email_status'=>'not_requested','created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Service request updated.');
    }

    public function paymentMethods(){ $methods=\App\PaymentMethod::with('emiPlans')->orderBy('display_order')->get();return view('admin.admin-pages.payment-methods',compact('methods')); }
    public function savePaymentMethod(Request $request){$this->validate($request,['name'=>'required|max:120','code'=>'required|max:50','type'=>'required|in:cash,bank,card,mobile,offline','display_order'=>'nullable|integer|min:0']);$code=str_slug($request->code,'_');$duplicate=DB::table('payment_methods')->where('code',$code)->when($request->id,function($q)use($request){$q->where('id','<>',$request->id);})->exists();if($duplicate)return Redirect::back()->with('exception','Payment method code already exists.');$data=['name'=>$request->name,'code'=>$code,'type'=>$request->type,'instructions'=>$request->instructions,'supports_emi'=>$request->has('supports_emi')?1:0,'is_active'=>$request->has('is_active')?1:0,'display_order'=>max(0,(int)$request->display_order),'updated_at'=>now()];if($request->id)DB::table('payment_methods')->where('id',$request->id)->update($data);else{$data['created_at']=now();DB::table('payment_methods')->insert($data);}return Redirect::back()->with('message','Payment method saved.');}
    public function togglePaymentMethod($id){$method=DB::table('payment_methods')->where('id',$id)->first();if($method)DB::table('payment_methods')->where('id',$id)->update(['is_active'=>$method->is_active?0:1,'updated_at'=>now()]);return Redirect::back()->with('message','Payment method status updated.');}
    public function saveEmiPlan(Request $request){$this->validate($request,['payment_method_id'=>'required|integer|exists:payment_methods,id','months'=>'required|integer|min:2|max:60','interest_rate'=>'required|numeric|min:0|max:100','processing_fee'=>'nullable|numeric|min:0','minimum_order'=>'nullable|numeric|min:0']);DB::table('emi_plans')->updateOrInsert(['payment_method_id'=>$request->payment_method_id,'months'=>$request->months],['interest_rate'=>$request->interest_rate,'processing_fee'=>$request->processing_fee?:0,'minimum_order'=>$request->minimum_order?:0,'is_active'=>1,'updated_at'=>now(),'created_at'=>now()]);DB::table('payment_methods')->where('id',$request->payment_method_id)->update(['supports_emi'=>1,'updated_at'=>now()]);return Redirect::back()->with('message','EMI plan saved.');}
    public function deleteEmiPlan($id){DB::table('emi_plans')->where('id',$id)->delete();return Redirect::back()->with('message','EMI plan deleted.');}

    public function abandonedCarts(Request $request){$query=DB::table('abandoned_carts')->latest('last_activity_at');if($request->filled('status'))$query->where('status',$request->status);$carts=$query->paginate(30)->appends($request->query());$counts=['active'=>DB::table('abandoned_carts')->where('status','active')->count(),'reminded'=>DB::table('abandoned_carts')->where('status','reminded')->count(),'recovered'=>DB::table('abandoned_carts')->whereIn('status',['recovered','converted'])->count()];return view('admin.admin-pages.abandoned-carts',compact('carts','counts'));}
    public function remindAbandonedCart($id){$sent=app(\App\Services\CartRecoveryService::class)->remind($id);return Redirect::back()->with($sent?'message':'exception',$sent?'Reminder processed.':'Cart has no recovery email or is no longer active.');}
    public function deleteAbandonedCart($id){DB::table('abandoned_carts')->where('id',$id)->delete();return Redirect::back()->with('message','Saved cart deleted.');}

    public function salesReports(Request $request)
    {
        $from=$request->input('from',date('Y-m-01'));$to=$request->input('to',date('Y-m-d'));$this->validate($request,['from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from']);
        $orders=DB::table('orders')->whereBetween('created_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('status','<>','cancelled');$summary=(clone $orders)->selectRaw('COUNT(*) orders_count, COALESCE(SUM(subtotal-discount),0) net_sales, COALESCE(SUM(delivery_charge),0) delivery_income, COALESCE(AVG(total),0) average_order')->first();
        $costs=DB::table('order_items as i')->join('orders as o','o.id','=','i.order_id')->whereBetween('o.created_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('o.status','<>','cancelled')->selectRaw('COALESCE(SUM(i.unit_purchase_price*i.quantity),0) purchase_cost')->first();
        $discounts=(float)(clone $orders)->sum('discount');
        $profitBeforeRefunds=(float)DB::table('order_items as i')->join('orders as o','o.id','=','i.order_id')->whereBetween('o.created_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('o.status','<>','cancelled')->sum('i.profit')-$discounts;
        $daily=DB::table('orders')->whereBetween('created_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('status','<>','cancelled')->selectRaw('DATE(created_at) sale_date, COUNT(*) orders_count, SUM(subtotal-discount) sales')->groupBy(DB::raw('DATE(created_at)'))->orderBy('sale_date')->get();
        $topProducts=DB::table('order_items as i')->join('orders as o','o.id','=','i.order_id')->whereBetween('o.created_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('o.status','<>','cancelled')->select('i.product_name',DB::raw('SUM(i.quantity) units'),DB::raw('SUM(i.subtotal) sales'),DB::raw('SUM(i.profit) profit'))->groupBy('i.product_name')->orderByDesc('sales')->limit(15)->get();
        $refundTotal=(float)DB::table('refunds')->where('status','completed')->whereBetween('refunded_at',[$from.' 00:00:00',$to.' 23:59:59'])->sum('amount');
        $recoveredPurchaseCost=(float)DB::table('refunds as r')->join('order_returns as returns','returns.id','=','r.order_return_id')->join('order_return_items as ri','ri.order_return_id','=','returns.id')->join('order_items as i','i.id','=','ri.order_item_id')->where('r.status','completed')->whereBetween('r.refunded_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('ri.restock',1)->whereNotNull('ri.inventory_restored_at')->sum(DB::raw('i.unit_purchase_price*ri.quantity'));
        $netAfterRefunds=(float)$summary->net_sales-$refundTotal;
        $purchaseCostAfterReturns=(float)$costs->purchase_cost-$recoveredPurchaseCost;
        $profitAfterRefunds=$profitBeforeRefunds-$refundTotal+$recoveredPurchaseCost;
        $statuses=DB::table('orders')->whereBetween('created_at',[$from.' 00:00:00',$to.' 23:59:59'])->select('status',DB::raw('COUNT(*) total'))->groupBy('status')->get();
        return view('admin.admin-pages.sales-reports',compact('from','to','summary','costs','discounts','profitBeforeRefunds','daily','topProducts','statuses','refundTotal','recoveredPurchaseCost','netAfterRefunds','purchaseCostAfterReturns','profitAfterRefunds'));
    }

    public function exportSalesReport(Request $request)
    {
        $from=$request->input('from',date('Y-m-01'));$to=$request->input('to',date('Y-m-d'));$this->validate($request,['from'=>'required|date','to'=>'required|date|after_or_equal:from']);$rows=DB::table('orders as o')->leftJoin('order_items as i','i.order_id','=','o.id')->whereBetween('o.created_at',[$from.' 00:00:00',$to.' 23:59:59'])->where('o.status','<>','cancelled')->select('o.order_number','o.created_at','o.customer_name','o.phone','o.status','o.payment_method','o.subtotal','o.discount','o.delivery_charge','o.total',DB::raw('COALESCE(SUM(i.unit_purchase_price*i.quantity),0) purchase_cost'),DB::raw('COALESCE(SUM(i.profit),0)-o.discount profit_before_refunds'))->groupBy('o.id','o.order_number','o.created_at','o.customer_name','o.phone','o.status','o.payment_method','o.subtotal','o.discount','o.delivery_charge','o.total')->orderBy('o.created_at')->get();
        return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fputcsv($out,['Order','Date','Customer','Phone','Status','Payment','Subtotal','Discount','Delivery','Total','Purchase Cost','Profit Before Refunds']);foreach($rows as $r)fputcsv($out,[$r->order_number,$r->created_at,$r->customer_name,$r->phone,$r->status,$r->payment_method,$r->subtotal,$r->discount,$r->delivery_charge,$r->total,$r->purchase_cost,$r->profit_before_refunds]);fclose($out);},'sales-report-'.$from.'-to-'.$to.'.csv',['Content-Type'=>'text/csv']);
    }

    public function marketingCampaigns(){ $segments=DB::table('customer_segments')->where('is_active',1)->orderBy('name')->get();$campaigns=DB::table('marketing_campaigns as c')->join('customer_segments as s','s.id','=','c.customer_segment_id')->leftJoin('coupons as p','p.id','=','c.coupon_id')->select('c.*','s.name as segment_name','p.code as coupon_code')->latest('c.id')->get();$coupons=DB::table('coupons')->where('is_active',1)->orderBy('code')->get();foreach($segments as $segment)$segment->audience_count=app(\App\Services\CampaignService::class)->audience($segment)->count();return view('admin.admin-pages.marketing-campaigns',compact('segments','campaigns','coupons'));}
    public function saveCustomerSegment(Request $request){$this->validate($request,['name'=>'required|max:120','minimum_orders'=>'nullable|integer|min:0','minimum_spend'=>'nullable|numeric|min:0','registered_within_days'=>'nullable|integer|min:1','inactive_for_days'=>'nullable|integer|min:1']);DB::table('customer_segments')->insert(['name'=>$request->name,'description'=>$request->description,'minimum_orders'=>max(0,(int)$request->minimum_orders),'minimum_spend'=>max(0,(float)$request->minimum_spend),'registered_within_days'=>$request->registered_within_days?:null,'inactive_for_days'=>$request->inactive_for_days?:null,'registered_only'=>1,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Customer segment created.');}
    public function deleteCustomerSegment($id){if(DB::table('marketing_campaigns')->where('customer_segment_id',$id)->exists())return Redirect::back()->with('exception','This segment is used by a campaign and cannot be deleted.');DB::table('customer_segments')->where('id',$id)->delete();return Redirect::back()->with('message','Customer segment deleted.');}
    public function saveMarketingCampaign(Request $request){$this->validate($request,['name'=>'required|max:120','subject'=>'required|max:180','message'=>'required|min:10|max:5000','customer_segment_id'=>'required|integer|exists:customer_segments,id','coupon_id'=>'nullable|integer|exists:coupons,id']);DB::table('marketing_campaigns')->insert(['name'=>$request->name,'subject'=>$request->subject,'message'=>$request->message,'customer_segment_id'=>$request->customer_segment_id,'coupon_id'=>$request->coupon_id?:null,'status'=>'draft','created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Campaign draft created. Prepare its audience before sending.');}
    public function prepareMarketingCampaign($id){$campaign=DB::table('marketing_campaigns')->where('id',$id)->first();abort_unless($campaign,404);if($campaign->status==='sent')return Redirect::back()->with('exception','A sent campaign cannot be prepared again.');$count=app(\App\Services\CampaignService::class)->prepare($id);return Redirect::back()->with('message',$count.' recipient(s) prepared. Review the count before sending.');}
    public function sendMarketingCampaign($id){$campaign=DB::table('marketing_campaigns')->where('id',$id)->first();abort_unless($campaign,404);if($campaign->status!=='ready')return Redirect::back()->with('exception','Prepare the audience before sending.');$count=app(\App\Services\CampaignService::class)->send($id);return Redirect::back()->with('message',$count.' campaign notification(s) processed.');}
    public function deleteMarketingCampaign($id){$campaign=DB::table('marketing_campaigns')->where('id',$id)->first();if($campaign&&$campaign->status==='sent')return Redirect::back()->with('exception','Sent campaigns are retained for delivery history.');DB::table('campaign_recipients')->where('campaign_id',$id)->delete();DB::table('marketing_campaigns')->where('id',$id)->delete();return Redirect::back()->with('message','Campaign deleted.');}

    public function adminUsers(){ $roles=DB::table('admin_roles')->orderBy('name')->get();$admins=DB::table('tbl_admin as a')->leftJoin('admin_roles as r','r.id','=','a.role_id')->select('a.*','r.name as role_name')->orderBy('a.admin_name')->get();return view('admin.admin-pages.admin-users',compact('roles','admins'));}
    public function saveAdminRole(Request $request){$permissions=['dashboard','catalog','inventory','orders','customers','marketing','reports','settings','staff'];$this->validate($request,['name'=>'required|max:100']);$selected=array_values(array_intersect($permissions,(array)$request->permissions));if(!in_array('dashboard',$selected,true))$selected[]='dashboard';if(DB::table('admin_roles')->where('name',$request->name)->exists())return Redirect::back()->with('exception','A role with that name already exists.');DB::table('admin_roles')->insert(['name'=>$request->name,'permissions'=>json_encode($selected),'is_system'=>0,'created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Administrator role created.');}
    public function updateAdminRole(Request $request,$id){
        $allowed=['dashboard','catalog','inventory','orders','customers','marketing','reports','settings','staff'];
        $this->validate($request,['name'=>'required|max:100']);
        $role=DB::table('admin_roles')->where('id',$id)->first();
        abort_unless($role,404);
        if(DB::table('admin_roles')->where('name',$request->name)->where('id','<>',$id)->exists())return Redirect::back()->with('exception','A role with that name already exists.');
        $selected=array_values(array_intersect($allowed,(array)$request->permissions));
        if(!in_array('dashboard',$selected,true))$selected[]='dashboard';
        if($role->name==='Super Admin')$selected=$allowed;
        DB::table('admin_roles')->where('id',$id)->update(['name'=>$request->name,'permissions'=>json_encode($selected),'updated_at'=>now()]);
        return Redirect::back()->with('message','Administrator role updated.');
    }
    public function deleteAdminRole($id){$role=DB::table('admin_roles')->where('id',$id)->first();if(!$role)return Redirect::back();if($role->is_system||DB::table('tbl_admin')->where('role_id',$id)->exists())return Redirect::back()->with('exception','System roles and roles assigned to staff cannot be deleted.');DB::table('admin_roles')->where('id',$id)->delete();return Redirect::back()->with('message','Role deleted.');}
    public function saveAdminUser(Request $request){$this->validate($request,['admin_name'=>'required|max:30','full_name'=>'nullable|max:120','admin_email'=>'nullable|email|max:150','role_id'=>'required|integer|exists:admin_roles,id','password'=>'required|min:8|max:255']);if(DB::table('tbl_admin')->where('admin_name',$request->admin_name)->exists())return Redirect::back()->withInput()->with('exception','That administrator username already exists.');DB::table('tbl_admin')->insert(['admin_name'=>$request->admin_name,'full_name'=>$request->full_name,'admin_email'=>$request->admin_email,'role_id'=>$request->role_id,'is_active'=>1,'admin_password'=>\Hash::make($request->password),'created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Administrator account created.');}
    public function updateAdminUser(Request $request,$id){
        $this->validate($request,['admin_name'=>'required|max:30','full_name'=>'nullable|max:120','admin_email'=>'nullable|email|max:150','role_id'=>'required|integer|exists:admin_roles,id']);
        $admin=DB::table('tbl_admin')->where('admin_id',$id)->first();
        abort_unless($admin,404);
        if(DB::table('tbl_admin')->where('admin_name',$request->admin_name)->where('admin_id','<>',$id)->exists())return Redirect::back()->with('exception','That administrator username already exists.');
        if($request->admin_email&&DB::table('tbl_admin')->where('admin_email',$request->admin_email)->where('admin_id','<>',$id)->exists())return Redirect::back()->with('exception','That administrator email is already in use.');
        $roleId=(int)$request->role_id;
        if((int)$id===(int)session('admin_id'))$roleId=(int)$admin->role_id;
        DB::table('tbl_admin')->where('admin_id',$id)->update(['admin_name'=>$request->admin_name,'full_name'=>$request->full_name?:null,'admin_email'=>$request->admin_email?:null,'role_id'=>$roleId,'updated_at'=>now()]);
        if((int)$id===(int)session('admin_id'))$request->session()->put(['admin_name'=>$request->admin_name,'admin_display_name'=>$request->full_name ?: $request->admin_name]);
        return Redirect::back()->with('message','Administrator account updated.');
    }
    public function toggleAdminUser($id){if((int)$id===(int)session('admin_id'))return Redirect::back()->with('exception','You cannot disable your own account.');$admin=DB::table('tbl_admin')->where('admin_id',$id)->first();if($admin)DB::table('tbl_admin')->where('admin_id',$id)->update(['is_active'=>$admin->is_active?0:1,'updated_at'=>now()]);return Redirect::back()->with('message','Administrator status updated.');}
    public function resetAdminPassword(Request $request,$id){$this->validate($request,['password'=>'required|min:8|max:255|confirmed']);abort_unless(DB::table('tbl_admin')->where('admin_id',$id)->exists(),404);DB::table('tbl_admin')->where('admin_id',$id)->update(['admin_password'=>\Hash::make($request->password),'updated_at'=>now()]);return Redirect::back()->with('message','Administrator password updated.');}
    public function adminActivity(Request $request){$query=DB::table('admin_activity_logs')->latest('created_at');if($request->filled('admin_id'))$query->where('admin_id',$request->admin_id);if($request->filled('search'))$query->where(function($q)use($request){$q->where('action','like','%'.$request->search.'%')->orWhere('path','like','%'.$request->search.'%');});$logs=$query->paginate(50)->appends($request->query());$admins=DB::table('tbl_admin')->select('admin_id','admin_name')->orderBy('admin_name')->get();return view('admin.admin-pages.admin-activity',compact('logs','admins'));}

    public function systemHealth(){try{DB::select('SELECT 1');$database=['ok'=>true,'message'=>'Connected'];}catch(\Throwable $e){$database=['ok'=>false,'message'=>$e->getMessage()];}$storageWritable=is_writable(storage_path('app'))&&is_writable(storage_path('framework'))&&is_writable(storage_path('logs'));$free=@disk_free_space(base_path());$total=@disk_total_space(base_path());$health=['database'=>$database,'storage'=>['ok'=>$storageWritable,'message'=>$storageWritable?'Writable':'One or more storage directories are not writable'],'php'=>['ok'=>version_compare(PHP_VERSION,'7.2.5','>='),'message'=>PHP_VERSION],'laravel'=>['ok'=>true,'message'=>app()->version()],'disk'=>['ok'=>$free!==false&&$free>536870912,'message'=>$free===false?'Unknown':$this->formatBytes($free).' free of '.$this->formatBytes($total)],'environment'=>['ok'=>config('app.env')==='production'&&!config('app.debug'),'message'=>config('app.env').' · debug '.(config('app.debug')?'ON':'off')]];$backups=DB::table('system_backups')->latest()->limit(30)->get();$lastBackup=$backups->first();return view('admin.admin-pages.system-health',compact('health','backups','lastBackup'));}
    public function createSystemBackup(){try{$id=app(\App\Services\DatabaseBackupService::class)->create(session('admin_name'));return Redirect::back()->with('message','Database backup completed successfully. Reference '.$id.'.');}catch(\Throwable $e){return Redirect::back()->with('exception','Backup failed: '.$e->getMessage());}}
    public function downloadSystemBackup($id){$backup=DB::table('system_backups')->where('id',$id)->where('status','completed')->first();abort_unless($backup,404);$path=storage_path('app/backups/'.$backup->filename);abort_unless(is_file($path),404);return response()->download($path,$backup->filename,['Content-Type'=>'application/gzip']);}
    public function deleteSystemBackup($id){$backup=DB::table('system_backups')->where('id',$id)->first();if($backup){\Storage::disk($backup->disk)->delete('backups/'.$backup->filename);DB::table('system_backups')->where('id',$id)->delete();}return Redirect::back()->with('message','Backup deleted.');}
    public function clearSystemCache(){\Artisan::call('cache:clear');\Artisan::call('view:clear');\Artisan::call('config:clear');\Artisan::call('route:clear');return Redirect::back()->with('message','Application, view, configuration, and route caches cleared.');}
    public function systemMonitor(Request $request){$query=DB::table('system_events')->latest('last_occurred_at');if($request->filled('type'))$query->where('event_type',$request->type);if($request->input('status')==='open')$query->whereNull('resolved_at');if($request->input('status')==='resolved')$query->whereNotNull('resolved_at');$events=$query->paginate(40)->appends($request->query());$runs=DB::table('scheduled_task_runs')->latest('started_at')->limit(20)->get();$stats=['open_errors'=>DB::table('system_events')->where('event_type','application_error')->whereNull('resolved_at')->count(),'security_24h'=>DB::table('system_events')->where('event_type','admin_security')->where('last_occurred_at','>=',now()->subDay())->count(),'failed_logins'=>DB::table('system_events')->where('title','like','Failed administrator login%')->where('last_occurred_at','>=',now()->subDay())->count(),'failed_tasks'=>DB::table('scheduled_task_runs')->where('status','failed')->where('started_at','>=',now()->subDays(7))->count()];return view('admin.admin-pages.system-monitor',compact('events','runs','stats'));}
    public function resolveSystemEvent($id){$event=DB::table('system_events')->where('id',$id)->first();abort_unless($event,404);DB::table('system_events')->where('id',$id)->update(['resolved_at'=>now(),'resolved_by'=>session('admin_name'),'updated_at'=>now()]);return Redirect::back()->with('message','System event marked as resolved.');}
    public function integrations(){ $clients=DB::table('api_clients')->latest()->get();$webhooks=DB::table('webhook_endpoints')->latest()->get();$deliveries=DB::table('webhook_deliveries as d')->join('webhook_endpoints as w','w.id','=','d.webhook_endpoint_id')->select('d.*','w.name as webhook_name')->latest('d.created_at')->limit(50)->get();return view('admin.admin-pages.integrations',compact('clients','webhooks','deliveries'));}
    public function saveApiClient(Request $request){$allowed=['catalog.read','orders.read','inventory.write'];$this->validate($request,['name'=>'required|max:120']);$scopes=array_values(array_intersect($allowed,(array)$request->scopes));if(!$scopes)return Redirect::back()->with('exception','Select at least one API scope.');$token='ltbd_'.bin2hex(random_bytes(24));DB::table('api_clients')->insert(['name'=>$request->name,'token_prefix'=>substr($token,0,12),'token_hash'=>hash('sha256',$token),'scopes'=>json_encode($scopes),'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','API key created. Copy it now; it will not be shown again.')->with('new_api_token',$token);}
    public function toggleApiClient($id){$client=DB::table('api_clients')->where('id',$id)->first();if($client)DB::table('api_clients')->where('id',$id)->update(['is_active'=>$client->is_active?0:1,'updated_at'=>now()]);return Redirect::back()->with('message','API client status updated.');}
    public function deleteApiClient($id){DB::table('api_clients')->where('id',$id)->delete();return Redirect::back()->with('message','API client deleted.');}
    public function saveWebhookEndpoint(Request $request, \App\Services\SafeExternalUrl $safeUrl){$this->validate($request,['name'=>'required|max:120','url'=>'required|url|max:1000']);if(!$safeUrl->isAllowed($request->url))return Redirect::back()->withInput()->with('exception','Webhook URL must use HTTPS and resolve only to public internet addresses.');$allowed=['order.created','order.updated','inventory.updated'];$events=array_values(array_intersect($allowed,(array)$request->events));if(!$events)return Redirect::back()->with('exception','Select at least one webhook event.');$secret=$request->secret?:bin2hex(random_bytes(24));DB::table('webhook_endpoints')->insert(['name'=>$request->name,'url'=>$request->url,'secret'=>encrypt($secret),'events'=>json_encode($events),'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Webhook created. Copy the signing secret now.')->with('new_webhook_secret',$secret);}
    public function toggleWebhookEndpoint($id){$hook=DB::table('webhook_endpoints')->where('id',$id)->first();if($hook)DB::table('webhook_endpoints')->where('id',$id)->update(['is_active'=>$hook->is_active?0:1,'updated_at'=>now()]);return Redirect::back()->with('message','Webhook status updated.');}
    public function deleteWebhookEndpoint($id){DB::table('webhook_deliveries')->where('webhook_endpoint_id',$id)->delete();DB::table('webhook_endpoints')->where('id',$id)->delete();return Redirect::back()->with('message','Webhook deleted.');}

    public function purchasing(){ $suppliers=DB::table('suppliers')->orderBy('name')->get();$products=DB::table('product')->select('id','product_name','sku','purchase_price')->orderBy('product_name')->get();$purchaseOrders=DB::table('purchase_orders as p')->join('suppliers as s','s.id','=','p.supplier_id')->select('p.*','s.name as supplier_name')->latest('p.id')->paginate(25);return view('admin.admin-pages.purchasing',compact('suppliers','products','purchaseOrders'));}
    public function saveSupplier(Request $request){$this->validate($request,['name'=>'required|max:150','email'=>'nullable|email|max:150','phone'=>'nullable|max:40']);DB::table('suppliers')->insert(['name'=>$request->name,'contact_person'=>$request->contact_person,'phone'=>$request->phone,'email'=>$request->email,'address'=>$request->address,'tax_id'=>$request->tax_id,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);return Redirect::back()->with('message','Supplier created.');}
    public function toggleSupplier($id){$supplier=DB::table('suppliers')->where('id',$id)->first();if($supplier)DB::table('suppliers')->where('id',$id)->update(['is_active'=>$supplier->is_active?0:1,'updated_at'=>now()]);return Redirect::back()->with('message','Supplier status updated.');}
    public function savePurchaseOrder(Request $request){$this->validate($request,['supplier_id'=>'required|integer|exists:suppliers,id','expected_at'=>'nullable|date','other_cost'=>'nullable|numeric|min:0','product_id'=>'required|array|min:1','product_id.*'=>'required|integer|distinct|exists:product,id','quantity.*'=>'required|integer|min:1|max:100000','unit_cost.*'=>'required|numeric|min:0']);$products=DB::table('product')->whereIn('id',$request->product_id)->get()->keyBy('id');$subtotal=0;$lines=[];foreach($request->product_id as $index=>$productId){$product=$products[$productId];$quantity=(int)$request->quantity[$index];$cost=(float)$request->unit_cost[$index];$line=$quantity*$cost;$subtotal+=$line;$lines[]=['product_id'=>$product->id,'product_name'=>$product->product_name,'sku'=>$product->sku,'ordered_quantity'=>$quantity,'received_quantity'=>0,'unit_cost'=>$cost,'subtotal'=>$line,'created_at'=>now(),'updated_at'=>now()];}$id=DB::transaction(function()use($request,$subtotal,$lines){$id=DB::table('purchase_orders')->insertGetId(['po_number'=>'PO-'.date('ymd').'-'.strtoupper(str_random(5)),'supplier_id'=>$request->supplier_id,'status'=>'draft','expected_at'=>$request->expected_at,'subtotal'=>$subtotal,'other_cost'=>$request->other_cost?:0,'total'=>$subtotal+(float)$request->other_cost,'notes'=>$request->notes,'created_by'=>session('admin_name'),'created_at'=>now(),'updated_at'=>now()]);foreach($lines as $line){$line['purchase_order_id']=$id;DB::table('purchase_order_items')->insert($line);}return $id;});return Redirect::to('/purchase-orders/'.$id)->with('message','Purchase order created as a draft.');}
    public function viewPurchaseOrder($id){$order=DB::table('purchase_orders as p')->join('suppliers as s','s.id','=','p.supplier_id')->where('p.id',$id)->select('p.*','s.name as supplier_name','s.contact_person','s.phone as supplier_phone','s.email as supplier_email')->first();abort_unless($order,404);$items=DB::table('purchase_order_items')->where('purchase_order_id',$id)->get();$receipts=DB::table('stock_receipts')->where('purchase_order_id',$id)->latest('received_at')->get();return view('admin.admin-pages.purchase-order',compact('order','items','receipts'));}
    public function updatePurchaseOrderStatus(Request $request,$id){$this->validate($request,['status'=>'required|in:ordered,cancelled']);$order=DB::table('purchase_orders')->where('id',$id)->first();abort_unless($order,404);if($order->status!=='draft')return Redirect::back()->with('exception','Only draft purchase orders can be ordered or cancelled.');DB::table('purchase_orders')->where('id',$id)->update(['status'=>$request->status,'updated_at'=>now()]);return Redirect::back()->with('message','Purchase order status updated.');}
    public function receivePurchaseOrder(Request $request,$id)
    {
        $order=DB::table('purchase_orders')->where('id',$id)->first();
        abort_unless($order,404);
        if(!in_array($order->status,['ordered','partial'],true)) return Redirect::back()->with('exception','This purchase order is not open for receiving.');
        $location=DB::table('inventory_locations')->where('is_default',1)->where('is_active',1)->first();
        if(!$location) return Redirect::back()->with('exception','Configure an active default warehouse before receiving stock.');

        $received=0;$updatedProducts=[];
        try {
            DB::transaction(function()use($request,$id,$location,&$received,&$updatedProducts){
                $items=DB::table('purchase_order_items')->where('purchase_order_id',$id)->lockForUpdate()->get();
                foreach($items as $item){
                    $quantity=max(0,(int)$request->input('received.'.$item->id,0));
                    $remaining=$item->ordered_quantity-$item->received_quantity;
                    if($quantity>$remaining) throw new \RuntimeException('Received quantity exceeds the remaining quantity for '.$item->product_name.'.');
                    if(!$quantity) continue;
                    $product=DB::table('product')->where('id',$item->product_id)->lockForUpdate()->first();
                    $oldQuantity=max(0,(int)$product->stock_quantity);$newQuantity=$oldQuantity+$quantity;
                    $newCost=(float)$product->purchase_price>0&&$oldQuantity>0?(($oldQuantity*(float)$product->purchase_price)+($quantity*(float)$item->unit_cost))/$newQuantity:(float)$item->unit_cost;
                    $balance=DB::table('product_location_stock')->where('location_id',$location->id)->where('product_id',$product->id)->lockForUpdate()->first();
                    if($balance) DB::table('product_location_stock')->where('id',$balance->id)->increment('quantity',$quantity);
                    else DB::table('product_location_stock')->insert(['location_id'=>$location->id,'product_id'=>$product->id,'quantity'=>$quantity,'created_at'=>now(),'updated_at'=>now()]);
                    DB::table('product')->where('id',$product->id)->update(['stock_quantity'=>$newQuantity,'stock_tracking'=>1,'product_condition'=>'In Stock','purchase_price'=>round($newCost,2),'updated_at'=>now()]);
                    DB::table('purchase_order_items')->where('id',$item->id)->increment('received_quantity',$quantity);
                    DB::table('stock_receipts')->insert(['purchase_order_id'=>$id,'purchase_order_item_id'=>$item->id,'product_id'=>$product->id,'location_id'=>$location->id,'quantity'=>$quantity,'unit_cost'=>$item->unit_cost,'received_by'=>session('admin_name'),'received_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
                    $received+=$quantity;$updatedProducts[$product->id]=['product_id'=>$product->id,'sku'=>$product->sku,'stock_quantity'=>$newQuantity,'condition'=>'In Stock'];
                }
                $remaining=DB::table('purchase_order_items')->where('purchase_order_id',$id)->whereRaw('received_quantity < ordered_quantity')->exists();
                DB::table('purchase_orders')->where('id',$id)->update(['status'=>$remaining?'partial':'received','updated_at'=>now()]);
            });
        } catch(\RuntimeException $e) { return Redirect::back()->withInput()->with('exception',$e->getMessage()); }
        foreach($updatedProducts as $payload){app(\App\Services\StockAlertService::class)->process($payload['product_id']);app(\App\Services\WebhookService::class)->dispatch('inventory.updated',$payload);}
        return Redirect::back()->with('message',$received.' inventory unit(s) received into '.$location->name.'.');
    }

    public function stockLocations(Request $request){$locations=DB::table('inventory_locations')->orderByDesc('is_default')->orderBy('name')->get();$editLocation=$request->filled('edit')?DB::table('inventory_locations')->where('id',$request->edit)->first():null;$products=DB::table('product')->select('id','product_name','sku','stock_quantity')->orderBy('product_name')->get();$balances=DB::table('product_location_stock as s')->join('inventory_locations as l','l.id','=','s.location_id')->select('s.*','l.name as location_name')->get()->groupBy('product_id');$transfers=DB::table('stock_transfers as t')->join('inventory_locations as f','f.id','=','t.from_location_id')->join('inventory_locations as d','d.id','=','t.to_location_id')->select('t.*','f.name as from_name','d.name as to_name')->latest('t.id')->limit(30)->get();return view('admin.admin-pages.stock-locations',compact('locations','editLocation','products','balances','transfers'));}
    public function saveStockLocation(Request $request)
    {
        $this->validateStockLocation($request);
        $data=$this->stockLocationData($request);
        $data['is_default']=0;$data['is_active']=1;$data['created_at']=now();$data['updated_at']=now();
        DB::table('inventory_locations')->insert($data);
        return Redirect::to('/stock-locations')->with('message','Inventory location created.');
    }
    public function updateStockLocation(Request $request,$id)
    {
        $location=DB::table('inventory_locations')->where('id',$id)->first();
        if(!$location)return Redirect::to('/stock-locations')->with('exception','Inventory location not found.');
        $this->validateStockLocation($request,$id);
        $data=$this->stockLocationData($request);$data['updated_at']=now();
        DB::table('inventory_locations')->where('id',$id)->update($data);
        return Redirect::to('/stock-locations')->with('message','Inventory location updated.');
    }
    public function toggleStockLocation($id){$location=DB::table('inventory_locations')->where('id',$id)->first();if(!$location)return Redirect::back();if($location->is_default)return Redirect::back()->with('exception','The default warehouse cannot be disabled.');DB::table('inventory_locations')->where('id',$id)->update(['is_active'=>$location->is_active?0:1,'updated_at'=>now()]);return Redirect::back()->with('message','Location status updated.');}
    public function saveStockTransfer(Request $request){$this->validate($request,['from_location_id'=>'required|integer|exists:inventory_locations,id','to_location_id'=>'required|integer|different:from_location_id|exists:inventory_locations,id','product_id'=>'required|array|min:1','product_id.*'=>'required|integer|distinct|exists:product,id','quantity.*'=>'required|integer|min:1|max:100000']);$products=DB::table('product')->whereIn('id',$request->product_id)->get()->keyBy('id');try{$id=DB::transaction(function()use($request,$products){$id=DB::table('stock_transfers')->insertGetId(['transfer_number'=>'TR-'.date('ymd').'-'.strtoupper(str_random(5)),'from_location_id'=>$request->from_location_id,'to_location_id'=>$request->to_location_id,'status'=>'completed','notes'=>$request->notes,'created_by'=>session('admin_name'),'completed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);foreach($request->product_id as $index=>$productId){$quantity=(int)$request->quantity[$index];$source=DB::table('product_location_stock')->where('location_id',$request->from_location_id)->where('product_id',$productId)->lockForUpdate()->first();if(!$source||$source->quantity<$quantity)throw new \RuntimeException($products[$productId]->product_name.' has insufficient stock at the source location.');DB::table('product_location_stock')->where('id',$source->id)->decrement('quantity',$quantity);$destination=DB::table('product_location_stock')->where('location_id',$request->to_location_id)->where('product_id',$productId)->lockForUpdate()->first();if($destination)DB::table('product_location_stock')->where('id',$destination->id)->increment('quantity',$quantity);else DB::table('product_location_stock')->insert(['location_id'=>$request->to_location_id,'product_id'=>$productId,'quantity'=>$quantity,'created_at'=>now(),'updated_at'=>now()]);DB::table('stock_transfer_items')->insert(['stock_transfer_id'=>$id,'product_id'=>$productId,'product_name'=>$products[$productId]->product_name,'sku'=>$products[$productId]->sku,'quantity'=>$quantity,'created_at'=>now(),'updated_at'=>now()]);}return $id;});}catch(\RuntimeException $e){return Redirect::back()->withInput()->with('exception',$e->getMessage());}return Redirect::back()->with('message','Stock transfer completed. Reference '.$id.'.');}






























    public function authCheck() {
        return true;
    }

    private function parseList($value)
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value))));
    }

    private function storeProductImage($image)
    {
        $path='asset/front-end/img/Product_image/';
        if(!is_dir(public_path($path)))mkdir(public_path($path),0755,true);
        $name=str_random(20).'.'.strtolower($image->getClientOriginalExtension());
        $image->move(public_path($path),$name);
        return $path.$name;
    }

    private function storeProductImages(array $images)
    {
        $paths=[];foreach($images as $image)if($image)$paths[]=$this->storeProductImage($image);return $paths;
    }

    private function deleteOwnedProductImage($path)
    {
        $path=ltrim((string)$path,'/');
        if(strpos($path,'asset/front-end/img/Product_image/')===0&&is_file(public_path($path)))unlink(public_path($path));
    }

    private function validateStockLocation(Request $request, $ignoreId = null)
    {
        $rules = [
            'name'=>'required|string|max:150','code'=>'required|string|max:30|unique:inventory_locations,code'.($ignoreId?','.$ignoreId:''),
            'type'=>'required|in:warehouse,branch,store,distribution_center,office','address'=>'nullable|string|max:1000',
            'phone'=>'nullable|string|max:40','contact_person'=>'nullable|string|max:120','email'=>'nullable|email|max:150',
            'country'=>'nullable|string|max:100','division'=>'nullable|string|max:100','city'=>'nullable|string|max:100',
            'postal_code'=>'nullable|string|max:20','latitude'=>'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude'=>'nullable|numeric|between:-180,180|required_with:latitude','google_maps_url'=>'nullable|url|max:255',
            'operating_hours'=>'nullable|string|max:150','notes'=>'nullable|string|max:2000'
        ];
        $request->merge(['code'=>strtoupper(trim((string)$request->code))]);
        $this->validate($request,$rules);
    }

    private function bulkDeleteResult($path,$deleted,$skipped,$word,$reason)
    {
        $message=$deleted.' '.$word.($deleted===1?'y':'ies').' deleted.';
        if($word==='manufacturer')$message=$deleted.' manufacturer'.($deleted===1?'':'s').' deleted.';
        if($word==='product')$message=$deleted.' product'.($deleted===1?'':'s').' deleted.';
        if($skipped)$message.=' '.$skipped.' skipped because '.($skipped===1?'it is':'they are').' referenced by '.$reason.'.';
        return Redirect::to($path)->with($deleted?'message':'exception',$message);
    }

    private function stockLocationData(Request $request)
    {
        $fields=['name','code','type','address','country','division','city','postal_code','phone','contact_person','email','operating_hours','google_maps_url','notes'];
        $data=[];foreach($fields as $field)$data[$field]=$request->filled($field)?trim($request->input($field)):null;
        $data['latitude']=$request->filled('latitude')?(float)$request->latitude:null;
        $data['longitude']=$request->filled('longitude')?(float)$request->longitude:null;
        $data['pickup_available']=$request->has('pickup_available')?1:0;
        $data['delivery_hub']=$request->has('delivery_hub')?1:0;
        return $data;
    }

    private function formatBytes($bytes)
    {
        if(!$bytes)return '0 B';$units=['B','KB','MB','GB','TB'];$power=min((int)floor(log($bytes,1024)),count($units)-1);return round($bytes/pow(1024,$power),1).' '.$units[$power];
    }

    private function syncProductAttributes($productId, Request $request)
    {
        $allowed=DB::table('catalog_attributes')->where('category_id',$request->category_id)->pluck('id')->map(function($id){return (int)$id;})->all();
        DB::table('product_attribute_values')->where('product_id',$productId)->delete();
        foreach((array)$request->input('attributes',[]) as $attributeId=>$value) {
            $stored=is_array($value)?json_encode(array_values(array_filter(array_map('trim',$value)))):trim((string)$value);
            if(in_array((int)$attributeId,$allowed,true) && $stored!=='' && $stored!=='[]') DB::table('product_attribute_values')->insert(['product_id'=>$productId,'attribute_id'=>$attributeId,'value'=>$stored,'created_at'=>now(),'updated_at'=>now()]);
        }
    }

    private function parseSpecifications($value)
    {
        $specifications = [];
        $section = null;
        foreach ($this->parseList($value) as $line) {
            if (preg_match('/^\[(.+)\]$/', trim($line), $matches)) {
                $section = trim($matches[1]);
                if ($section !== '' && !isset($specifications[$section])) {
                    $specifications[$section] = [];
                }
                continue;
            }
            $parts = array_map('trim', explode(':', $line, 2));
            if (count($parts) === 2 && $parts[0] !== '') {
                if ($section) {
                    $specifications[$section][$parts[0]] = $parts[1];
                } else {
                    $specifications[$parts[0]] = $parts[1];
                }
            }
        }
        return $specifications;
    }

}
