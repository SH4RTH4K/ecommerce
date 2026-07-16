<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Session;
use DB;

session_start();

class SuperAdminController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $this->authCheck();
        $admin_home = view('admin.admin-pages.admin-home');
        return view('admin.admin-master')
                        ->with('admin_main_content', $admin_home);
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
        $data['publication_status'] = $request->publication_status;
        DB::table('category')->insert($data);
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
        return Redirect::to('/manage-category');
    }

    public function publishedCategory($category_id) {
        DB::table('category')
                ->where('category_id', $category_id)
                ->update(['publication_status' => 1]);
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
        $category_id = $request->category_id;
        DB::table('category')
                ->where('category_id', $category_id)
                ->update($data);
        return Redirect::to('/manage-category');
    }

    public function deleteCategory($category_id) {
        DB::table('category')
                ->where('category_id', $category_id)
                ->delete();
        return Redirect::to('/manage-category');
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

    public function addProduct() {
        $this->authCheck();
        $category = DB::table('category')
                ->get();
        $sub_category = DB::table('sub_category')
                ->get();
        $manufacturer = DB::table('manufacturer')
                ->get();
        $home = view('admin.admin-pages.add-product')
                ->with('category', $category)
                ->with('manufacturer', $manufacturer)
                ->with('sub_category', $sub_category);
        return view('admin.admin-master')
                        ->with('admin_main_content', $home);
    }

    public function saveProduct(Request $request) {
        $this->authCheck();
        $data = array();
        $data['product_id'] = $request->product_id;
        $data['category_id'] = $request->category_id;
        $data['sub_category'] = $request->sub_category_id;
        $data['manufacturer_id'] = $request->manufacturer_id;
        $data['product_model'] = $request->product_model;
        $data['product_name'] = $request->product_name;
        $data['product_description'] = $request->product_description;
        $data['product_price'] = $request->product_price;
        $data['product_condition'] = $request->product_condition;
        $data['publication_status'] = $request->publication_status;
//        upload Image
        $image = $request->file('product_image');
        if ($image) {
            $image_name = str_random(20);
            $ext = strtolower($image->getClientOriginalExtension());
            $image_full_name = $image_name . '.' . $ext;
            $upload_path = 'product_image/';
            $image_url = $upload_path . $image_full_name;
            $success = $image->move($upload_path, $image_full_name);
            if ($success) {
                $data['product_image'] = $image_url;
                DB::table('product')
                        ->insert($data);
                Session::put('message', 'Save Product Successfully');
                return Redirect::to('/add-product');
            }
        } else {
            DB::table('product')
                    ->insert($data);
            Session::put('message', 'Save Product Successfully');
            return Redirect::to('/add-product');
        }
    }
    
    public function manageProduct()
    {
        $this->authCheck();
        $all_product=DB::table('product')
                ->get();
        $manage_product = view('admin.admin-pages.manage-product')
                ->with('all_product', $all_product);
        return view('admin.admin-master')
                        ->with('admin_main_content', $manage_product);
    }

    






























    public function authCheck() {
        $admin_id = Session::get('admin_id');
        if ($admin_id) {
            return;
        } else {
            return Redirect::to('/xyz')->send();
        }
    }

}
