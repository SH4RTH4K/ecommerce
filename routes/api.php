<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/v1/products','Api\StoreApiController@products')->middleware('api.client:catalog.read');
Route::get('/v1/orders','Api\StoreApiController@orders')->middleware('api.client:orders.read');
Route::get('/v1/orders/{id}','Api\StoreApiController@order')->middleware('api.client:orders.read');
Route::put('/v1/products/{id}/inventory','Api\StoreApiController@updateInventory')->middleware('api.client:inventory.write');
Route::get('/product-code/configuration', 'ProductCodeConfigurationController@configuration')->middleware('api.client:catalog.read');
Route::post('/product-code/preview', 'ProductCodeConfigurationController@preview')->middleware('api.client:catalog.read');
