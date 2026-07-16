<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', "WelcomeController@index");
Route::get('/computers', "WelcomeController@computers");
Route::get('/monitor', "WelcomeController@monitor");
Route::get('/motherboard', "WelcomeController@motherboard");
Route::get('/processor', "WelcomeController@processor");
Route::get('/hard-disk', "WelcomeController@hard_disk");
Route::get('/dvd-writer', "WelcomeController@dvd_writer");
Route::get('/power-supply', "WelcomeController@power_supply");
Route::get('/casing', "WelcomeController@casing");
Route::get('/product-details/{id}', "WelcomeController@product_details");
Route::get('/use-pc', "WelcomeController@use_pc");
Route::get('/use-laptop', "WelcomeController@use_laptop");
Route::get('/laptop', "WelcomeController@laptop");
Route::get('/use-monitor', "WelcomeController@use_monitor");
Route::get('/use-router', "WelcomeController@use_router");
Route::get('/router', "WelcomeController@router");
Route::get('/use-printer', "WelcomeController@use_printer");
Route::get('/printer', "WelcomeController@printer");
Route::get('/pendrive', "WelcomeController@pendrive");
Route::get('/physiotherapy', "WelcomeController@physiotherapy");
Route::get('/gift-item', "WelcomeController@gift_item");
Route::get('/about-us', "WelcomeController@about_us");
Route::get('/contact-us', "WelcomeController@contact_us");
Route::get('/terms&conditions', "WelcomeController@termsandconditions");




//For Admin
Route::get('/xyz', "AdminController@index");
Route::post('/admin-login', "AdminController@admin_login");


//for Super Admin
Route::get('/dashboard', "SuperAdminController@index");
Route::get('/logout', "SuperAdminController@logout");

Route::get('/add-category', "SuperAdminController@addCategory");
Route::get('/manage-category', "SuperAdminController@manageCategory");
Route::post('/save-category', "SuperAdminController@saveCategory");
Route::get('/unpublished-category/{id}', "SuperAdminController@unpublishedCategory");
Route::get('/published-category/{id}', "SuperAdminController@publishedCategory");
Route::get('/delete-category/{id}', "SuperAdminController@deleteCategory");
Route::get('/edit-category/{id}', "SuperAdminController@editCategory");
Route::post('/update-category/', "SuperAdminController@updateCategory");

Route::get('/add-subCategory', "SuperAdminController@addSubCategory");
Route::post('/save-subCategory', "SuperAdminController@saveSubCategory");
Route::get('/manage-subCategory', "SuperAdminController@manageSubCategory");
Route::get('/unpublished-subCategory/{id}', "SuperAdminController@unpublishedSubCategory");
Route::get('/published-subCategory/{id}', "SuperAdminController@publishedSubCategory");
Route::get('/delete-subCategory/{id}', "SuperAdminController@deleteSubCategory");
Route::get('/edit-subCategory/{id}', "SuperAdminController@editSubCategory");
Route::post('/update-subCategory/', "SuperAdminController@updateSubCategory");

Route::get('/add-manufacturer', "SuperAdminController@addManufacturer");
Route::post('/save-manufacturer', "SuperAdminController@saveManufacturer");
Route::get('/manage-manufacturer', "SuperAdminController@manageManufacturer");
Route::get('/unpublished-manufacturer/{id}', "SuperAdminController@unpublishedManufacturer");
Route::get('/published-manufacturer/{id}', "SuperAdminController@publishedManufacturer");
Route::get('/delete-manufacturer/{id}', "SuperAdminController@deleteManufacturer");
Route::get('/edit-manufacturer/{id}', "SuperAdminController@editManufacturer");
Route::post('/update-manufacturer/', "SuperAdminController@updateManufacturer");


Route::get('/add-product', "SuperAdminController@addProduct");
Route::post('/save-product', "SuperAdminController@saveProduct");
Route::get('/manage-product', "SuperAdminController@manageProduct");

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
