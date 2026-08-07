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
Route::get('/sitemap.xml', 'SeoController@sitemap')->name('sitemap');
Route::get('/product-by-category/{id}', "WelcomeController@productByCategory")->name('store.category.show');
Route::get('/product-by-sub-category/{id}', "WelcomeController@productBySubCategory");
Route::post('/search-product', "WelcomeController@searchProduct");
Route::get('/search-product', "WelcomeController@searchProduct");
Route::get('/search-suggestions', "WelcomeController@searchSuggestions")->name('store.search.suggestions');
Route::get('/all-manufacturer-by-id/{id}', "WelcomeController@allManufacturerById");

Route::get('/product-details/{id}', "WelcomeController@product_details")->name('store.product.show');

Route::get('/cart', 'ShopController@cart')->name('cart.index');
Route::post('/cart/add/{id}', 'ShopController@addToCart')->name('cart.add');
Route::post('/cart/update', 'ShopController@updateCart')->name('cart.update');
Route::post('/cart/remove/{id}', 'ShopController@removeFromCart')->name('cart.remove');
Route::post('/cart/recovery-email', 'CartRecoveryController@saveEmail')->name('cart.recovery-email');
Route::get('/recover-cart/{token}', 'CartRecoveryController@restore')->name('cart.restore');
Route::get('/compare', 'ShopController@compare')->name('compare.index');
Route::get('/pc-builder', 'PcBuilderController@index')->name('pc-builder.index');
Route::post('/pc-builder/select', 'PcBuilderController@select')->name('pc-builder.select');
Route::post('/pc-builder/remove/{slot}', 'PcBuilderController@remove')->name('pc-builder.remove');
Route::post('/pc-builder/add-to-cart', 'PcBuilderController@addToCart')->name('pc-builder.cart');
Route::get('/wishlist', 'SavedItemsController@wishlist')->name('wishlist.index');
Route::post('/wishlist/{id}', 'SavedItemsController@addWishlist')->name('wishlist.add');
Route::post('/wishlist/{id}/remove', 'SavedItemsController@removeWishlist')->name('wishlist.remove');
Route::get('/saved-builds', 'SavedItemsController@builds')->name('saved-builds.index');
Route::post('/saved-builds', 'SavedItemsController@saveBuild')->name('saved-builds.store');
Route::post('/saved-builds/{id}/restore', 'SavedItemsController@restoreBuild')->name('saved-builds.restore');
Route::post('/saved-builds/{id}/cart', 'SavedItemsController@addBuildToCart')->name('saved-builds.cart');
Route::post('/saved-builds/{id}/delete', 'SavedItemsController@deleteBuild')->name('saved-builds.delete');
Route::post('/compare/add/{id}', 'ShopController@addToCompare')->name('compare.add');
Route::post('/compare/remove/{id}', 'ShopController@removeFromCompare')->name('compare.remove');
Route::get('/checkout', 'CheckoutController@index')->name('checkout.index');
Route::post('/checkout', 'CheckoutController@store')->name('checkout.store');
Route::post('/checkout/check-coupon', 'CheckoutController@checkCoupon')->name('checkout.coupon');
Route::get('/order-success/{id}', 'CheckoutController@success')->name('checkout.success');
Route::get('/track-order', 'OrderTrackingController@form')->name('orders.track.form');
Route::post('/track-order', 'OrderTrackingController@track')->name('orders.track');
Route::get('/invoice/{id}', 'OrderTrackingController@invoice')->name('orders.invoice');
Route::get('/my-orders', 'OrderController@index')->name('account.orders');
Route::get('/my-orders/{id}', 'OrderController@show')->name('account.orders.show');
Route::get('/my-returns', 'ReturnController@index')->name('account.returns');
Route::get('/my-orders/{id}/return', 'ReturnController@create')->name('account.returns.create');
Route::post('/my-orders/{id}/return', 'ReturnController@store')->name('account.returns.store');
Route::get('/my-returns/{id}', 'ReturnController@show')->name('account.returns.show');
Route::get('/credit-notes/{id}', 'AdminReturnController@creditNote')->name('credit-notes.show');
Route::get('/notifications', 'NotificationController@index')->name('account.notifications');
Route::get('/notifications/{id}/read', 'NotificationController@read')->name('account.notifications.read');
Route::post('/notifications/read-all', 'NotificationController@readAll')->name('account.notifications.readAll');
Route::post('/products/{id}/reviews', 'CommunityController@review')->name('reviews.store');
Route::post('/products/{id}/questions', 'CommunityController@question')->name('questions.store');
Route::post('/products/{id}/stock-alert', 'StockAlertController@subscribe')->name('stock-alerts.subscribe');
Route::post('/support-request', 'CommunityController@support')->name('support.store');
Route::get('/service-request', 'ServiceClaimController@form')->name('service-claims.form');
Route::post('/service-request/products', 'ServiceClaimController@products')->name('service-claims.products');
Route::post('/service-request', 'ServiceClaimController@store')->name('service-claims.store');
Route::get('/service-request/{id}', 'ServiceClaimController@show')->name('service-claims.show');

Route::get('/physiotherapy', "WelcomeController@physiotherapy");
Route::get('/gift-item', "WelcomeController@gift_item");
Route::get('/about-us', "WelcomeController@about_us");
Route::get('/contact-us', "WelcomeController@contact_us");
Route::get('/terms&conditions', "WelcomeController@termsandconditions");




//For Admin
Route::get('/admin/login', 'AdminController@index')->name('admin.login');
Route::get('/admin/index.html', function () {
    return session()->has('admin_id') ? redirect()->route('admin.dashboard') : redirect()->route('admin.login');
})->name('admin.legacy-index');
Route::post('/admin/login', 'AdminController@login')->middleware('throttle:10,1')->name('admin.login.submit');


//for Super Admin
Route::middleware('admin.auth')->group(function () {
Route::get('/dashboard', "SuperAdminController@index")->name('admin.dashboard');
Route::post('/admin/logout', 'AdminController@logout')->name('admin.logout');
Route::get('/storefront-navbar', 'StorefrontNavbarController@index')->name('admin.storefront-navbar.index');
Route::get('/admin/storefront-navbar', 'StorefrontNavbarController@index');
Route::post('/storefront-navbar/save', 'StorefrontNavbarController@save')->name('admin.storefront-navbar.save');
Route::post('/admin/storefront-navbar/save', 'StorefrontNavbarController@save');
Route::post('/storefront-navbar/reset', 'StorefrontNavbarController@reset')->name('admin.storefront-navbar.reset');
Route::post('/admin/storefront-navbar/reset', 'StorefrontNavbarController@reset');
Route::post('/storefront-navbar/reset-items', 'StorefrontNavbarController@resetItems')->name('admin.storefront-navbar.reset-items');
Route::post('/admin/storefront-navbar/reset-items', 'StorefrontNavbarController@resetItems');
Route::post('/storefront-navbar/reset-design', 'StorefrontNavbarController@resetDesign')->name('admin.storefront-navbar.reset-design');
Route::post('/admin/storefront-navbar/reset-design', 'StorefrontNavbarController@resetDesign');
Route::get('/admin-data/{resource}/template', 'AdminDataTransferController@template')->name('admin-data.template');
Route::get('/admin-data/{resource}/export', 'AdminDataTransferController@export')->name('admin-data.export');
Route::post('/admin-data/{resource}/import', 'AdminDataTransferController@import')->name('admin-data.import');

Route::get('/add-category', "SuperAdminController@addCategory");
Route::get('/manage-category', "SuperAdminController@manageCategory");
Route::post('/manage-category/bulk-delete', "SuperAdminController@bulkDeleteCategories");
Route::post('/save-category', "SuperAdminController@saveCategory");
Route::post('/unpublished-category/{id}', "SuperAdminController@unpublishedCategory");
Route::post('/published-category/{id}', "SuperAdminController@publishedCategory");
Route::post('/delete-category/{id}', "SuperAdminController@deleteCategory");
Route::get('/edit-category/{id}', "SuperAdminController@editCategory");
Route::post('/update-category/', "SuperAdminController@updateCategory");

Route::get('/add-subCategory', "SuperAdminController@addSubCategory");
Route::post('/save-subCategory', "SuperAdminController@saveSubCategory");
Route::get('/manage-subCategory', "SuperAdminController@manageSubCategory");
Route::post('/manage-subCategory/bulk-delete', "SuperAdminController@bulkDeleteSubCategories");
Route::post('/unpublished-subCategory/{id}', "SuperAdminController@unpublishedSubCategory");
Route::post('/published-subCategory/{id}', "SuperAdminController@publishedSubCategory");
Route::post('/delete-subCategory/{id}', "SuperAdminController@deleteSubCategory");
Route::get('/edit-subCategory/{id}', "SuperAdminController@editSubCategory");
Route::post('/update-subCategory/', "SuperAdminController@updateSubCategory");

Route::get('/add-manufacturer', "SuperAdminController@addManufacturer");
Route::post('/save-manufacturer', "SuperAdminController@saveManufacturer");
Route::get('/manage-manufacturer', "SuperAdminController@manageManufacturer");
Route::get('/catalog-hierarchy', 'CatalogHierarchyController@index')->name('catalog-hierarchy.index');
Route::get('/catalog-imports', 'CatalogHierarchyController@imports')->name('catalog-imports.index');
Route::post('/catalog-hierarchy/startech-import', 'CatalogHierarchyController@importStarTechCatalog')->name('catalog-hierarchy.startech-import');
Route::post('/catalog-hierarchy/companies', 'CatalogHierarchyController@storeCompany')->name('catalog-companies.store');
Route::patch('/catalog-hierarchy/companies/{id}', 'CatalogHierarchyController@updateCompany')->name('catalog-companies.update');
Route::delete('/catalog-hierarchy/companies/{id}', 'CatalogHierarchyController@deleteCompany')->name('catalog-companies.destroy');
Route::post('/catalog-hierarchy/companies/bulk-delete', 'CatalogHierarchyController@bulkDeleteCompanies')->name('catalog-companies.bulk-delete');
Route::post('/catalog-hierarchy/brands', 'CatalogHierarchyController@storeBrand')->name('catalog-brands.store');
Route::patch('/catalog-hierarchy/brands/{id}', 'CatalogHierarchyController@updateBrand')->name('catalog-brands.update');
Route::delete('/catalog-hierarchy/brands/{id}', 'CatalogHierarchyController@deleteBrand')->name('catalog-brands.destroy');
Route::post('/catalog-hierarchy/brands/bulk-delete', 'CatalogHierarchyController@bulkDeleteBrands')->name('catalog-brands.bulk-delete');
Route::post('/catalog-hierarchy/series', 'CatalogHierarchyController@storeSeries')->name('catalog-series.store');
Route::patch('/catalog-hierarchy/series/{id}', 'CatalogHierarchyController@updateSeries')->name('catalog-series.update');
Route::delete('/catalog-hierarchy/series/{id}', 'CatalogHierarchyController@deleteSeries')->name('catalog-series.destroy');
Route::post('/catalog-hierarchy/series/bulk-delete', 'CatalogHierarchyController@bulkDeleteSeries')->name('catalog-series.bulk-delete');
Route::post('/manage-manufacturer/bulk-delete', "SuperAdminController@bulkDeleteManufacturers");
Route::post('/unpublished-manufacturer/{id}', "SuperAdminController@unpublishedManufacturer");
Route::post('/published-manufacturer/{id}', "SuperAdminController@publishedManufacturer");
Route::post('/delete-manufacturer/{id}', "SuperAdminController@deleteManufacturer");
Route::get('/edit-manufacturer/{id}', "SuperAdminController@editManufacturer");
Route::post('/update-manufacturer/', "SuperAdminController@updateManufacturer");


Route::get('/add-product', "SuperAdminController@addProduct");
Route::post('/save-product', "SuperAdminController@saveProduct");
Route::get('/manage-product', "SuperAdminController@manageProduct");
Route::post('/manage-product/bulk-delete', "SuperAdminController@bulkDeleteProducts");
Route::post('/unpublished-product/{id}', "SuperAdminController@unpublishedProduct");
Route::post('/published-product/{id}', "SuperAdminController@publishedProduct");
Route::post('/delete-product/{id}', "SuperAdminController@deleteProduct");
Route::get('/edit-product/{id}', "SuperAdminController@editProduct");
Route::post('/update-product/', "SuperAdminController@updateProduct");
Route::get('/product-code-configuration', 'ProductCodeConfigurationController@index')->name('product-code-configuration.index');
Route::post('/product-code-configuration', 'ProductCodeConfigurationController@save')->name('product-code-configuration.save');
Route::post('/product-code-configuration/preview', 'ProductCodeConfigurationController@preview')->name('product-code-configuration.preview');
Route::get('/product-code-configuration/configuration', 'ProductCodeConfigurationController@configuration')->name('product-code-configuration.configuration');
Route::post('/product-code-configuration/{id}/reset-sequence', 'ProductCodeConfigurationController@resetSequence')->name('product-code-configuration.reset-sequence');
 Route::get('/site-customization', 'SuperAdminController@siteCustomization');
 Route::get('/homepage-feature-cards', 'SuperAdminController@homepageFeatureCards')->name('admin.homepage-feature-cards');
 Route::post('/homepage-feature-cards', 'SuperAdminController@updateHomepageFeatureCards')->name('admin.homepage-feature-cards.update');
 Route::get('/banner-management', 'SuperAdminController@bannerManagement')->name('banner.index');
 Route::post('/site-settings', 'SuperAdminController@updateSiteSettings');
 Route::get('/top-bar-management', 'TopBarAdminController@index')->name('admin.top-bar.index');
 Route::post('/top-bar/settings', 'TopBarAdminController@updateSettings')->name('admin.top-bar.settings.update');
Route::post('/top-bar/announcements', 'TopBarAdminController@storeAnnouncement')->name('admin.announcements.store');
Route::patch('/top-bar/announcements/{id}', 'TopBarAdminController@updateAnnouncement')->name('admin.announcements.update');
Route::post('/top-bar/announcements/{id}/toggle', 'TopBarAdminController@toggleAnnouncement')->name('admin.announcements.toggle');
Route::delete('/top-bar/announcements/{id}', 'TopBarAdminController@deleteAnnouncement')->name('admin.announcements.destroy');
Route::post('/top-bar/contacts', 'TopBarAdminController@storeContact')->name('admin.contact-items.store');
Route::patch('/top-bar/contacts/{id}', 'TopBarAdminController@updateContact')->name('admin.contact-items.update');
Route::post('/top-bar/contacts/{id}/toggle', 'TopBarAdminController@toggleContact')->name('admin.contact-items.toggle');
Route::delete('/top-bar/contacts/{id}', 'TopBarAdminController@deleteContact')->name('admin.contact-items.destroy');
Route::post('/save-banner', 'SuperAdminController@saveBanner')->name('banner.store');
Route::post('/update-banner/{id}', 'SuperAdminController@updateBanner')->name('banner.update');
Route::get('/banner-product-preview/{id}', 'SuperAdminController@bannerProductPreview')->name('banner.product-preview');
Route::post('/toggle-banner/{id}', 'SuperAdminController@toggleBanner')->name('banner.toggle');
Route::post('/delete-banner/{id}', 'SuperAdminController@deleteBanner')->name('banner.destroy');
Route::get('/customer-inbox', 'SuperAdminController@customerInbox');
Route::post('/review/{id}/moderate', 'SuperAdminController@moderateReview');
Route::post('/question/{id}/answer', 'SuperAdminController@answerQuestion');
Route::post('/support/{id}/update', 'SuperAdminController@updateSupportRequest');
Route::get('/manage-orders', 'SuperAdminController@manageOrders');
Route::get('/manage-orders/{id}', 'SuperAdminController@viewOrder');
Route::post('/manage-orders/{id}/status', 'SuperAdminController@updateOrderStatus');
Route::get('/returns', 'AdminReturnController@index')->middleware('admin.auth');
Route::get('/returns/{id}', 'AdminReturnController@show')->middleware('admin.auth');
Route::post('/returns/{id}', 'AdminReturnController@update')->middleware('admin.auth');
Route::get('/inventory', 'SuperAdminController@inventory');
Route::post('/inventory/{id}', 'SuperAdminController@updateInventory');
Route::get('/catalog-attributes', 'SuperAdminController@catalogAttributes');
Route::post('/catalog-attributes', 'SuperAdminController@saveCatalogAttribute');
Route::post('/catalog-attributes/reorder', 'SuperAdminController@reorderCatalogAttributes');
Route::post('/catalog-attributes/bulk-delete', 'SuperAdminController@bulkDeleteCatalogAttributes');
Route::post('/catalog-attributes/{id}', 'SuperAdminController@updateCatalogAttribute');
Route::post('/catalog-attributes/{id}/toggle', 'SuperAdminController@toggleCatalogAttribute');
Route::post('/catalog-attributes/{id}/duplicate', 'SuperAdminController@duplicateCatalogAttribute');
Route::post('/catalog-attributes/{id}/delete', 'SuperAdminController@deleteCatalogAttribute');
Route::get('/delivery-zones', 'SuperAdminController@deliveryZones');
Route::post('/delivery-zones', 'SuperAdminController@saveDeliveryZone');
Route::post('/delivery-zones/{id}/toggle', 'SuperAdminController@toggleDeliveryZone');
Route::post('/delivery-zones/{id}/delete', 'SuperAdminController@deleteDeliveryZone');
Route::get('/coupons', 'SuperAdminController@coupons');
Route::post('/coupons', 'SuperAdminController@saveCoupon');
Route::post('/coupons/{id}/toggle', 'SuperAdminController@toggleCoupon');
Route::post('/coupons/{id}/delete', 'SuperAdminController@deleteCoupon');
Route::get('/admin-notifications', 'SuperAdminController@adminNotifications');
Route::get('/admin-notifications/{id}/read', 'SuperAdminController@readAdminNotification');
Route::get('/stock-alerts', 'SuperAdminController@stockAlerts');
Route::post('/stock-alerts/{id}/delete', 'SuperAdminController@deleteStockAlert');
Route::get('/service-claims', 'SuperAdminController@serviceClaims');
Route::get('/service-claims/{id}', 'SuperAdminController@viewServiceClaim');
Route::post('/service-claims/{id}', 'SuperAdminController@updateServiceClaim');
Route::get('/payment-methods', 'PaymentMethodAdminController@index')->name('payment-methods.index');
Route::get('/payment-methods/{id}/edit', 'PaymentMethodAdminController@edit')->name('payment-methods.edit');
Route::post('/payment-methods', 'PaymentMethodAdminController@save')->name('payment-methods.store');
Route::post('/payment-methods/reorder', 'PaymentMethodAdminController@reorder')->name('payment-methods.reorder');
Route::post('/payment-methods/{id}/toggle', 'PaymentMethodAdminController@toggle')->name('payment-methods.toggle');
Route::post('/payment-methods/{id}/duplicate', 'PaymentMethodAdminController@duplicate')->name('payment-methods.duplicate');
Route::post('/payment-methods/{id}/test', 'PaymentMethodAdminController@test')->name('payment-methods.test');
Route::get('/payment-methods/{id}/preview', 'PaymentMethodAdminController@preview')->name('payment-methods.preview');
Route::delete('/payment-methods/{id}', 'PaymentMethodAdminController@delete')->name('payment-methods.destroy');
Route::post('/payment-transactions/{id}/verify', 'PaymentMethodAdminController@verifyTransaction')->name('payment-transactions.verify');
Route::post('/emi-plans', 'SuperAdminController@saveEmiPlan');
Route::post('/emi-plans/{id}/delete', 'SuperAdminController@deleteEmiPlan');
Route::get('/abandoned-carts', 'SuperAdminController@abandonedCarts');
Route::post('/abandoned-carts/{id}/remind', 'SuperAdminController@remindAbandonedCart');
Route::post('/abandoned-carts/{id}/delete', 'SuperAdminController@deleteAbandonedCart');
Route::get('/sales-reports', 'SuperAdminController@salesReports');
Route::get('/sales-reports/export', 'SuperAdminController@exportSalesReport');
Route::get('/marketing-campaigns', 'SuperAdminController@marketingCampaigns');
Route::post('/customer-segments', 'SuperAdminController@saveCustomerSegment');
Route::post('/customer-segments/{id}/delete', 'SuperAdminController@deleteCustomerSegment');
Route::post('/marketing-campaigns', 'SuperAdminController@saveMarketingCampaign');
Route::post('/marketing-campaigns/{id}/prepare', 'SuperAdminController@prepareMarketingCampaign');
Route::post('/marketing-campaigns/{id}/send', 'SuperAdminController@sendMarketingCampaign');
Route::post('/marketing-campaigns/{id}/delete', 'SuperAdminController@deleteMarketingCampaign');
Route::get('/admin-users', 'SuperAdminController@adminUsers');
Route::post('/admin-roles', 'SuperAdminController@saveAdminRole');
Route::post('/admin-roles/{id}/update', 'SuperAdminController@updateAdminRole');
Route::post('/admin-roles/{id}/delete', 'SuperAdminController@deleteAdminRole');
Route::post('/admin-users', 'SuperAdminController@saveAdminUser');
Route::post('/admin-users/{id}/update', 'SuperAdminController@updateAdminUser');
Route::post('/admin-users/{id}/toggle', 'SuperAdminController@toggleAdminUser');
Route::post('/admin-users/{id}/password', 'SuperAdminController@resetAdminPassword');
Route::get('/admin-activity', 'SuperAdminController@adminActivity');
Route::get('/recycle-bin', 'RecycleBinController@index')->name('recycle-bin.index');
Route::post('/recycle-bin/{type}/{id}/restore', 'RecycleBinController@restore')->name('recycle-bin.restore');
Route::delete('/recycle-bin/{type}/{id}', 'RecycleBinController@destroy')->name('recycle-bin.destroy');
Route::post('/recycle-bin/{type}/bulk-restore', 'RecycleBinController@bulkRestore')->name('recycle-bin.bulk-restore');
Route::post('/recycle-bin/{type}/bulk-delete', 'RecycleBinController@bulkDestroy')->name('recycle-bin.bulk-delete');
Route::post('/recycle-bin/empty', 'RecycleBinController@empty')->name('recycle-bin.empty');
Route::get('/orphan-media', 'OrphanMediaController@index')->name('orphan-media.index');
Route::post('/orphan-media/cleanup', 'OrphanMediaController@cleanup')->name('orphan-media.cleanup');
Route::get('/system-health', 'SuperAdminController@systemHealth');
Route::post('/system-health/backup', 'SuperAdminController@createSystemBackup');
Route::post('/system-health/media-backup', 'SuperAdminController@createMediaSystemBackup');
Route::post('/system-health/full-backup', 'SuperAdminController@createFullSystemBackup');
Route::post('/system-health/restore', 'SuperAdminController@restoreSystemBackup');
Route::post('/system-health/media-restore', 'SuperAdminController@restoreMediaSystemBackup');
Route::post('/system-health/full-restore', 'SuperAdminController@restoreFullSystemBackup');
Route::get('/system-health/backups/{id}/download', 'SuperAdminController@downloadSystemBackup');
Route::post('/system-health/backups/{id}/delete', 'SuperAdminController@deleteSystemBackup');
Route::post('/system-health/clear-cache', 'SuperAdminController@clearSystemCache');
Route::get('/system-monitor', 'SuperAdminController@systemMonitor');
Route::post('/system-monitor/events/{id}/resolve', 'SuperAdminController@resolveSystemEvent');
Route::get('/integrations', 'SuperAdminController@integrations');
Route::post('/api-clients', 'SuperAdminController@saveApiClient');
Route::post('/api-clients/{id}/toggle', 'SuperAdminController@toggleApiClient');
Route::post('/api-clients/{id}/delete', 'SuperAdminController@deleteApiClient');
Route::post('/webhook-endpoints', 'SuperAdminController@saveWebhookEndpoint');
Route::post('/webhook-endpoints/{id}/toggle', 'SuperAdminController@toggleWebhookEndpoint');
Route::post('/webhook-endpoints/{id}/delete', 'SuperAdminController@deleteWebhookEndpoint');
Route::get('/purchasing', 'SuperAdminController@purchasing');
Route::post('/suppliers', 'SuperAdminController@saveSupplier');
Route::post('/suppliers/{id}/toggle', 'SuperAdminController@toggleSupplier');
Route::post('/purchase-orders', 'SuperAdminController@savePurchaseOrder');
Route::get('/purchase-orders/{id}', 'SuperAdminController@viewPurchaseOrder');
Route::post('/purchase-orders/{id}/status', 'SuperAdminController@updatePurchaseOrderStatus');
Route::post('/purchase-orders/{id}/receive', 'SuperAdminController@receivePurchaseOrder');
Route::get('/stock-locations', 'SuperAdminController@stockLocations');
Route::post('/stock-locations', 'SuperAdminController@saveStockLocation');
Route::post('/stock-locations/{id}', 'SuperAdminController@updateStockLocation');
Route::post('/stock-locations/{id}/toggle', 'SuperAdminController@toggleStockLocation');
Route::post('/stock-transfers', 'SuperAdminController@saveStockTransfer');
Route::post('/admin-notifications/read-all', 'SuperAdminController@readAllAdminNotifications');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
