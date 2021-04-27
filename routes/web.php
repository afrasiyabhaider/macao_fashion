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

use Illuminate\Support\Facades\Auth;

include_once('install_r.php');

Route::middleware(['IsInstalled'])->group(function () {
    Route::get('/', function () {
        return redirect('/login');
    });

    Auth::routes();

    Route::get('/business/register', 'BusinessController@getRegister')->name('business.getRegister');
    Route::post('/business/register', 'BusinessController@postRegister')->name('business.postRegister');
    Route::post('/business/register/check-username', 'BusinessController@postCheckUsername')->name('business.postCheckUsername');
    Route::post('/business/register/check-email', 'BusinessController@postCheckEmail')->name('business.postCheckEmail');

    Route::get('/invoice/{token}', 'SellPosController@showInvoice')
        ->name('show_invoice');
});

//Routes for authenticated users only
Route::middleware(['IsInstalled', 'auth', 'SetSessionData', 'language', 'timezone'])->group(function () {

    Route::get('/logout', 'Auth\LoginController@logout')->name('logout');

    Route::get('portal/home', 'HomeController@index')->name('home');
    Route::get('/home', 'HomeController@index')->name('home');
    Route::get('/home/get-totals', 'HomeController@getTotals');
    Route::get('/home/product-stock-alert', 'HomeController@getProductStockAlert');
    Route::get('/home/purchase-payment-dues', 'HomeController@getPurchasePaymentDues');
    Route::get('/home/sales-payment-dues', 'HomeController@getSalesPaymentDues');

    Route::get('/load-more-notifications', 'HomeController@loadMoreNotifications');

    Route::get('/business/settings', 'BusinessController@getBusinessSettings')->name('business.getBusinessSettings');
    Route::post('/business/update', 'BusinessController@postBusinessSettings')->name('business.postBusinessSettings');
    Route::get('/user/profile', 'UserController@getProfile')->name('user.getProfile');
    Route::post('/user/update', 'UserController@updateProfile')->name('user.updateProfile');
    Route::post('/user/update-password', 'UserController@updatePassword')->name('user.updatePassword');

    Route::resource('brands', 'BrandController');

    Route::resource('payment-account', 'PaymentAccountController');

    Route::resource('tax-rates', 'TaxRateController');

    Route::resource('units', 'UnitController');

    Route::get('/contacts/import', 'ContactController@getImportContacts')->name('contacts.import');
    Route::post('/contacts/import', 'ContactController@postImportContacts');
    Route::post('/contacts/check-contact-id', 'ContactController@checkContactId');
    Route::get('/contacts/customers', 'ContactController@getCustomers');
    Route::get('/contacts/saleReport/{id}', 'ContactController@saleReport');
    Route::resource('contacts', 'ContactController');

    Route::get('fraudster', 'ContactController@fraudster');
    Route::get('change_fraudster', 'ContactController@change_fraudster');
    Route::get('categories/createSubCategory', 'CategoryController@createSubCategory');
    Route::get('categories/createCategory', 'CategoryController@createCategory');
    Route::resource('categories', 'CategoryController');

    Route::resource('variation-templates', 'VariationTemplateController');

    Route::get('/products', 'ProductController@index');
    Route::post('/products/bulkUpdate', 'ProductController@bulkUpdate');
    Route::post('/products/update-all', 'ProductController@updateAll');
    Route::get('/products/addUpdatedDate', 'ProductController@addDateinNull');
    Route::get('/products/addinlocation', 'ProductController@addProductZeroQtyInLocation');
    // Route::get('/products/addinlocation/{barcode}/{location}', 'ProductController@addProductZeroQtyInLocation');
    Route::post('/products/mass-deactivate', 'ProductController@massDeactivate');
    Route::get('/products/transfer', 'ProductController@transfer');
    Route::post('/products/mass-print', 'ProductController@massBulkPrint');
    Route::post('/products/selected-mass-print', 'ProductController@selectedBulkPrint');
    Route::post('/products/mass-transfer', 'ProductController@massTransfer');
    Route::get('/products/activate/{id}', 'ProductController@activate');
    Route::get('/products/view-product-group-price/{id}', 'ProductController@viewGroupPrice');
    Route::get('/products/add-selling-prices/{id}', 'ProductController@addSellingPrices');
    Route::post('/products/save-selling-prices', 'ProductController@saveSellingPrices');
    Route::post('/products/mass-delete', 'ProductController@massDestroy');
    Route::get('/products/view/{id}', 'ProductController@view');
    Route::get('/products/nothing/{id}', 'ProductController@nothing');
    Route::get('/products/viewBulkPackage/{id}', 'ProductController@viewBulkPackage');
    Route::get('/products/list', 'ProductController@getProducts');
    Route::get('/products/list-no-variation', 'ProductController@getProductsWithoutVariations');

    Route::post('/product_name_category/storeExcell', 'ProductNameCategoryController@storeExcell');
    Route::get('/product_name_category/addExcell', 'ProductNameCategoryController@addExcell');
    Route::resource('/product_name_category', 'ProductNameCategoryController');
    Route::post('/products/get_sub_categories', 'ProductController@getSubCategories');
    Route::post('/products/get_sub_sizes', 'ProductController@getSubSizes');
    Route::get('/sizes/getSupplierDetails/{id}', 'ProductController@getSupplierDetails');
    Route::post('/products/product_form_part', 'ProductController@getProductVariationFormPart');
    Route::post('/products/get_product_variation_row', 'ProductController@getProductVariationRow');
    Route::post('/products/get_variation_template', 'ProductController@getVariationTemplate');
    Route::get('/products/get_variation_value_row', 'ProductController@getVariationValueRow');
    Route::post('/products/check_product_sku', 'ProductController@checkProductSku');
    Route::get('/products/quick_add', 'ProductController@quickAdd');
    Route::get('/products/quick_add_only', 'ProductController@quickAddOnly');
    Route::get('/gift/quick_add', 'GiftCardController@quickAdd');
    Route::get('/coupon/quick_add', 'CouponController@quickAdd');
    Route::post('/coupon/save_quick_product', 'CouponController@saveQuickProduct');
    Route::post('/gift/save_quick_product', 'GiftCardController@saveQuickProduct');
    Route::post('/products/save_quick_product', 'ProductController@saveQuickProduct');
    Route::post('/products/save_quick_product_only', 'ProductController@saveQuickProductOnly');
    Route::get('/products/bulk_add', 'ProductController@bulkAdd');
    Route::get('/products/updateProductId', 'ProductController@updateProductId');
    Route::get('/products/getProductId', 'ProductController@getProductId');
    Route::post('/products/bulk_add_store', 'ProductController@bulkAddStore');

    Route::resource('products', 'ProductController');
    Route::resource('suppliers', 'SupplierController');
    // Route::get('suppliers', 'SupplierController@index');
    Route::get('/sizes/getSubSize/{id}', 'SizeController@getSubSize');
    Route::resource('sizes', 'SizeController');
    // Route::put('sizes/{id}', 'SizesController@update');
    Route::resource('colors', 'ColorController');

    Route::resource('gift', 'GiftCardController');
    Route::get('/gift/{id}', 'GiftCardController@view');
    Route::get('/gift/{id}/edit', 'GiftCardController@edit');
    Route::post('/gift/mass-deactivate', 'GiftCardController@massDeactivate');
    Route::post('/gift/mass-delete', 'GiftCardController@massDestroy');
    Route::get('/gift/activate/{id}', 'GiftCardController@activate');
    Route::get('/gift/reports', 'GiftCardController@reports');

    Route::resource('coupon', 'CouponController');
    Route::get('/coupon/{id}', 'CouponController@view');
    Route::get('/coupon/{id}/edit', 'CouponController@edit');
    Route::post('/coupon/mass-deactivate', 'CouponController@massDeactivate');
    Route::post('/coupon/mass-delete', 'CouponController@massDestroy');
    Route::get('/coupon/activate/{id}', 'CouponController@activate');
    Route::get('/coupon/reports', 'CouponController@reports');

    Route::get('/purchases/get_products', 'PurchaseController@getProducts');
    Route::get('/purchases/get_suppliers', 'PurchaseController@getSuppliers');
    Route::post('/purchases/get_purchase_entry_row', 'PurchaseController@getPurchaseEntryRow');
    Route::post('/purchases/check_ref_number', 'PurchaseController@checkRefNumber');
    Route::get('/purchases/print/{id}', 'PurchaseController@printInvoice');
    Route::resource('purchases', 'PurchaseController');

    Route::get('/toggle-subscription/{id}', 'SellPosController@toggleRecurringInvoices');

    // Route::get('/pos/{id}', 'SellPosController@destroy');

    Route::get('/sells/subscriptions', 'SellPosController@listSubscriptions');
    Route::get('/sells/invoice-url/{id}', 'SellPosController@showInvoiceUrl');
    Route::get('/sells/duplicate/{id}', 'SellController@duplicateSell');
    Route::get('/sells/drafts', 'SellController@getDrafts');
    Route::get('/sells/quotations', 'SellController@getQuotations');
    Route::get('/sells/draft-dt', 'SellController@getDraftDatables');
    Route::get('/sells/showThis/{id}', 'SellController@showThis');
    Route::resource('sells', 'SellController');

    Route::get('/sells/pos/get_product_row/{variation_id}/{location_id}', 'SellPosController@getProductRow');
    Route::get('/sells/pos/get_bulk_product_detail/{variation_id}', 'SellPosController@getBulkProductDetails');
    Route::get('/sells/pos/get_bulk_product_detail/{variation_id}/{location_id}', 'SellPosController@getBulkProductLocationDetails');
    Route::get('/sells/pos/verifyGiftCard/{variation_id}', 'SellPosController@verifyGiftCard');
    Route::get('/sells/pos/verifyCoupon/{variation_id}', 'SellPosController@verifyCoupon');
    Route::get('/sells/pos/getCustDiscount/{variation_id}', 'SellPosController@getCustDiscount');
    Route::post('/sells/pos/get_payment_row', 'SellPosController@getPaymentRow');
    Route::get('/sells/pos/get-recent-transactions', 'SellPosController@getRecentTransactions');
    Route::get('/sells/{transaction_id}/print', 'SellPosController@printInvoice')->name('sell.printInvoice');
    Route::get('/sells/pos/get-product-suggestion', 'SellPosController@getProductSuggestion');
    Route::get('/sells/pos/get-product-refference-suggestion', 'SellPosController@getProductRefferenceSuggestion');
    // Route::post('/ajaxSaleGiftCard', 'SellPosController@verifyCoupon');
    Route::get('/pos/return/{transaction_id}', 'SellPosController@returnAndAdjust');
    Route::post('/pos/returnCreate', 'SellPosController@returnCreate');
    Route::post('/pos/bulkHide', 'SellPosController@bulkHide')->name("bulkHide");
    Route::post('/pos/bulkUnHide', 'SellPosController@bulkUnHide')->name("bulkUnHide");
    Route::post('/pos/hide/{transaction_id}', 'SellPosController@hide');
    Route::post('/pos/unhide/{transaction_id}', 'SellPosController@unhide');

    Route::resource('pos', 'SellPosController');

    Route::post('/pos/transaction/{id}/delete', 'SellPosController@delete_transaction');

    Route::resource('roles', 'RoleController');

    Route::post('/users/getLocations', 'ManageUserController@getLocations');

    Route::resource('users', 'ManageUserController');

    Route::resource('group-taxes', 'GroupTaxController');

    Route::get('/barcodes/set_default/{id}', 'BarcodeController@setDefault');
    Route::resource('barcodes', 'BarcodeController');

    //Invoice schemes..
    Route::get('/invoice-schemes/set_default/{id}', 'InvoiceSchemeController@setDefault');
    Route::resource('invoice-schemes', 'InvoiceSchemeController');

    //Print Labels
    Route::get('/labels/show', 'LabelsController@show');
    Route::get('/labels/add-product-row', 'LabelsController@addProductRow');
    Route::post('/labels/preview', 'LabelsController@preview');

    //Reports...
    Route::get('/reports/service-staff-report', 'ReportController@getServiceStaffReport');
    Route::get('/reports/service-staff-line-orders', 'ReportController@serviceStaffLineOrders');
    Route::get('/reports/table-report', 'ReportController@getTableReport');
    Route::get('/reports/profit-loss', 'ReportController@getProfitLoss');
    Route::get('/reports/get-opening-stock', 'ReportController@getOpeningStock');
    Route::get('/reports/purchase-sell', 'ReportController@getPurchaseSell');
    Route::get('/reports/customer-supplier', 'ReportController@getCustomerSuppliers');
    Route::get('/reports/stock-report', 'ReportController@getStockReport');
    Route::get('/reports/grouped-stock-report', 'ReportController@getGroupedStockReport');
    Route::get('/reports/supplier-report', 'ReportController@supplier_report');
    Route::get('/reports/subcategory-report', 'ReportController@sub_category_report');
    Route::get('/reports/product-first-report', 'ReportController@product_first_report');
    Route::get('/reports/stock-details', 'ReportController@getStockDetails');
    Route::get('/reports/tax-report', 'ReportController@getTaxReport');
    Route::get('/reports/trending-products', 'ReportController@getTrendingProducts');
    Route::get('/reports/expense-report', 'ReportController@getExpenseReport');
    Route::get('/reports/stock-adjustment-report', 'ReportController@getStockAdjustmentReport');
    Route::get('/reports/register-report', 'ReportController@getRegisterReport');
    Route::get('/reports/sales-representative-report', 'ReportController@getSalesRepresentativeReport');
    Route::get('/reports/sales-representative-total-expense', 'ReportController@getSalesRepresentativeTotalExpense');
    Route::get('/reports/sales-representative-total-sell', 'ReportController@getSalesRepresentativeTotalSell');
    Route::get('/reports/sales-representative-total-commission', 'ReportController@getSalesRepresentativeTotalCommission');
    Route::get('/reports/stock-expiry', 'ReportController@getStockExpiryReport');
    Route::get('/reports/stock-expiry-edit-modal/{purchase_line_id}', 'ReportController@getStockExpiryReportEditModal');
    Route::post('/reports/stock-expiry-update', 'ReportController@updateStockExpiryReport')->name('updateStockExpiryReport');
    Route::get('/reports/customer-group', 'ReportController@getCustomerGroup');
    Route::get('/reports/product-purchase-report', 'ReportController@getproductPurchaseReport');
    Route::get('/reports/product-sell-report', 'ReportController@getproductSellReport');
    Route::get('/reports/product-sell-grouped-report', 'ReportController@getproductSellGroupedReport');
    Route::get('/reports/lot-report', 'ReportController@getLotReport');
    Route::get('/reports/purchase-payment-report', 'ReportController@purchasePaymentReport');
    Route::get('/reports/sell-payment-report', 'ReportController@sellPaymentReport');
    Route::get('/reports/product-stock-details', 'ReportController@productStockDetails');
    Route::get('/reports/adjust-product-stock', 'ReportController@adjustProductStock');
    Route::get('/reports/get-profit/{by?}', 'ReportController@getProfit');
    Route::get('/daily/sales', 'ReportController@dailySales');
    Route::get('/monthly/sales', 'ReportController@monthlySales');

    //Business Location Settings...
    Route::prefix('business-location/{location_id}')->name('location.')->group(function () {
        Route::get('settings', 'LocationSettingsController@index')->name('settings');
        Route::post('settings', 'LocationSettingsController@updateSettings')->name('settings_update');
    });

    //Business Locations...
    Route::post('business-location/check-location-id', 'BusinessLocationController@checkLocationId');
    Route::resource('business-location', 'BusinessLocationController');

    //Invoice layouts..
    Route::resource('invoice-layouts', 'InvoiceLayoutController');

    //Expense Categories...
    Route::resource('expense-categories', 'ExpenseCategoryController');

    //Expenses...
    Route::resource('expenses', 'ExpenseController');

    //Transaction payments...
    Route::get('/payments/opening-balance/{contact_id}', 'TransactionPaymentController@getOpeningBalancePayments');
    Route::get('/payments/show-child-payments/{payment_id}', 'TransactionPaymentController@showChildPayments');
    Route::get('/payments/view-payment/{payment_id}', 'TransactionPaymentController@viewPayment');
    Route::get('/payments/add_payment/{transaction_id}', 'TransactionPaymentController@addPayment');
    Route::get('/payments/pay-contact-due/{contact_id}', 'TransactionPaymentController@getPayContactDue');
    Route::post('/payments/pay-contact-due', 'TransactionPaymentController@postPayContactDue');
    Route::resource('payments', 'TransactionPaymentController');

    //Printers...
    Route::resource('printers', 'PrinterController');

    Route::get('/stock-adjustments/remove-expired-stock/{purchase_line_id}', 'StockAdjustmentController@removeExpiredStock');
    Route::post('/stock-adjustments/get_product_row', 'StockAdjustmentController@getProductRow');
    Route::resource('stock-adjustments', 'StockAdjustmentController');

    Route::get('/cash-register/register-details', 'CashRegisterController@getRegisterDetails');

    // Route::get('/cash-register/close-register', 'CashRegisterController@getCloseRegister');
    // Route::post('/cash-register/close-register', 'CashRegisterController@postCloseRegister');

    Route::get('/cash-register/auto-close', 'CashRegisterController@autoCloseRegister');


    Route::resource('cash-register', 'CashRegisterController');

    //Import products
    Route::get('/import-products', 'ImportProductsController@index');
    Route::post('/import-products/store', 'ImportProductsController@store');

    //Sales Commission Agent
    Route::resource('sales-commission-agents', 'SalesCommissionAgentController');

    //Stock Transfer
    Route::get('stock-transfers/print/{id}', 'StockTransferController@printInvoice');
    Route::resource('stock-transfers', 'StockTransferController');

    Route::get('/opening-stock/add/{product_id}', 'OpeningStockController@add');
    Route::post('/opening-stock/save', 'OpeningStockController@save');

    //Customer Groups
    Route::resource('customer-group', 'CustomerGroupController');

    //Import opening stock
    Route::get('/import-opening-stock', 'ImportOpeningStockController@index');
    Route::post('/import-opening-stock/store', 'ImportOpeningStockController@store');

    //Sell return
    Route::get('/sell-return/add', 'SellReturnController@add');
    Route::resource('sell-return', 'SellReturnController');
    Route::get('sell-return/get-product-row', 'SellReturnController@getProductRow');
    Route::get('/sell-return/add/{id}', 'SellReturnController@addWithId');

    Route::get('sell-return/invoice/{transaction_id}', 'SellReturnController@getInvoiceData');

    Route::get('/sell-return/print/{id}', 'SellReturnController@printInvoice');

    //Backup
    Route::get('backup/download/{file_name}', 'BackUpController@download');
    Route::get('backup/delete/{file_name}', 'BackUpController@delete');
    Route::resource('backup', 'BackUpController', ['only' => [
        'index', 'create', 'store'
    ]]);


    Route::resource('selling-price-group', 'SellingPriceGroupController');

    Route::resource('notification-templates', 'NotificationTemplateController')->only(['index', 'store']);
    Route::get('notification/get-template/{transaction_id}/{template_for}', 'NotificationController@getTemplate');
    Route::post('notification/send', 'NotificationController@send');

    Route::post('/purchase-return/update', 'CombinedPurchaseReturnController@update');
    Route::get('/purchase-return/edit/{id}', 'CombinedPurchaseReturnController@edit');
    Route::post('/purchase-return/save', 'CombinedPurchaseReturnController@save');
    Route::post('/purchase-return/get_product_row', 'CombinedPurchaseReturnController@getProductRow');
    Route::get('/purchase-return/create', 'CombinedPurchaseReturnController@create');
    Route::get('/purchase-return/add/{id}', 'PurchaseReturnController@add');
    Route::resource('/purchase-return', 'PurchaseReturnController', ['except' => ['create']]);

    Route::get('/discount/activate/{id}', 'DiscountController@activate');
    Route::post('/discount/mass-deactivate', 'DiscountController@massDeactivate');
    Route::resource('discount', 'DiscountController');

    Route::group(['prefix' => 'account'], function () {
        Route::resource('/account', 'AccountController');
        Route::get('/fund-transfer/{id}', 'AccountController@getFundTransfer');
        Route::post('/fund-transfer', 'AccountController@postFundTransfer');
        Route::get('/deposit/{id}', 'AccountController@getDeposit');
        Route::post('/deposit', 'AccountController@postDeposit');
        Route::get('/close/{id}', 'AccountController@close');
        Route::get('/delete-account-transaction/{id}', 'AccountController@destroyAccountTransaction');
        Route::get('/get-account-balance/{id}', 'AccountController@getAccountBalance');
        Route::get('/balance-sheet', 'AccountReportsController@balanceSheet');
        Route::get('/trial-balance', 'AccountReportsController@trialBalance');
        Route::get('/payment-account-report', 'AccountReportsController@paymentAccountReport');
        Route::get('/link-account/{id}', 'AccountReportsController@getLinkAccount');
        Route::post('/link-account', 'AccountReportsController@postLinkAccount');
        Route::get('/cash-flow', 'AccountController@cashFlow');
    });


    //Restaurant module
    Route::group(['prefix' => 'modules'], function () {
        Route::resource('tables', 'Restaurant\TableController');
        Route::resource('modifiers', 'Restaurant\ModifierSetsController');

        //Map modifier to products
        Route::get('/product-modifiers/{id}/edit', 'Restaurant\ProductModifierSetController@edit');

        Route::post('/product-modifiers/{id}/update', 'Restaurant\ProductModifierSetController@update');
        Route::get('/product-modifiers/product-row/{product_id}', 'Restaurant\ProductModifierSetController@product_row');

        Route::get('/add-selected-modifiers', 'Restaurant\ProductModifierSetController@add_selected_modifiers');

        Route::get('/kitchen', 'Restaurant\KitchenController@index');
        Route::get('/kitchen/mark-as-cooked/{id}', 'Restaurant\KitchenController@markAsCooked');
        Route::post('/refresh-orders-list', 'Restaurant\KitchenController@refreshOrdersList');
        Route::post('/refresh-line-orders-list', 'Restaurant\KitchenController@refreshLineOrdersList');

        Route::get('/orders', 'Restaurant\OrderController@index');
        Route::get('/orders/mark-as-served/{id}', 'Restaurant\OrderController@markAsServed');
        Route::get('/data/get-pos-details', 'Restaurant\DataController@getPosDetails');
        Route::get('/orders/mark-line-order-as-served/{id}', 'Restaurant\OrderController@markLineOrderAsServed');
    });

    Route::get('bookings/get-todays-bookings', 'Restaurant\BookingController@getTodaysBookings');
    Route::resource('bookings', 'Restaurant\BookingController');

    /***
     *  Data Migrating Routes
     * 
     ***/

    Route::get('location-transfer-details/data', 'DataMigrationController@location_transfer_detail_data');
    Route::get('location-transfer-details/product_data', 'DataMigrationController@location_transfer_detail_product_data');
    Route::get('transaction_sell_lines/product_data', 'DataMigrationController@transaction_sell_lines_product_data');
    Route::get('variation_location_details/product_data', 'DataMigrationController@variation_location_details_product_data');
    Route::get('variation_location_details/web_shop', 'DataMigrationController@variation_location_details_web_shop');

    // Website Routes
    Route::post('website/product/', 'WebsiteController@addToWebsite');
    Route::post('pos/product/', 'ProductController@showPos');
    Route::post('pos/bottom/product/', 'ProductController@showBottomPos');
    Route::get('website/product/ajax', 'WebsiteController@websiteAjaxProducts');
    Route::get('website/product/{id}/delete', 'WebsiteController@destroy');
    Route::get('website/product/list', 'WebsiteController@index');
    Route::get('website/product/{id}/special_category', 'WebsiteController@specialCategoriesForm');
    Route::post('website/product/special_category', 'WebsiteController@addspecialCategories');
    Route::get('website/product/{id}/images', 'WebsiteController@addImagesForm');
    Route::post('website/product/images', 'WebsiteController@addImages');
    Route::delete('website/product/{id}/images', 'WebsiteController@deleteImage');
    Route::get('website/product/priority', 'WebsiteController@setPriority');
    Route::post('website/product/priority', 'WebsiteController@savePriority');

    /**
     * Site Images for banners
     *  
     **/
    Route::get('website/banner/images', 'SiteImageController@create');
    Route::get('website/page/images', 'SiteImageController@create_page');
    Route::post('website/slider/images', 'SiteImageController@storeSlider');
    Route::post('website/category/images', 'SiteImageController@categoryImage');
    Route::delete('website/slider/images/{id}', 'SiteImageController@destroySlider');
});

Route::get('migrate-fresh', function () {
    \Artisan::call('migrate:fresh');
    dd("Migration Freshed");
});
Route::get('migrate', function () {
    \Artisan::call('migrate');
    dd("Migration Completed");
});
Route::get('permission-reset', function () {
    \Artisan::call('permission:cache-reset');
    dd("Permission Cache Resetted");
});
Route::get('route-clear', function () {
    \Artisan::call('route:clear');
    dd("Route Cleared");
});
Route::get('cache-clear', function () {
    \Artisan::call('cache:clear');
    dd("Cache Cleared");
});
Route::get('view-clear', function () {
    \Artisan::call('view:clear');
    dd("View Cleared");
});
Route::get('config-cache', function () {
    \Artisan::call('config:cache');
    dd("Config Cached");
});
Route::get('optimize-clear', function () {
    \Artisan::call('optimize:clear');
    dd("Optimized");
});
Route::get('force-logout', function () {
    Auth::logout();
    dd("Logged Out");
});


/**
 *  WebSite Routes Starts from here
 * 
 */

// Route::get('/', 'HomeController@index')->name('site.home');
// Route::get('/', 'website\SiteController@home')->name('site.home');
Route::get('product/{id}/detail', 'website\SiteController@detail')->name('product.detail');

// Route::get('customer/login','')

Route::get('product/{ref}/color/{id}', 'website\SiteController@get_color_sizes');
Route::get('product/{ref}/color/{color}/size/{size}', 'website\SiteController@get_color_size_qty');
Route::get('product/{ref}/size/{size}', 'website\SiteController@get_size_qty');
Route::get('product/list', 'website\SiteController@all_products');
Route::get('products/category/{id}', 'website\SiteController@products_by_category');
Route::get('products/nulldate/{date}', 'website\SiteController@update_null_product_date');

/**
 *  Cart
 *
 * */
Route::group(['prefix' => 'cart'], function () {
    Route::post('/', 'website\CartController@addToCart');
    Route::get('/view', 'website\CartController@viewCart');
    Route::get('/remove/{id}', 'website\CartController@removeItem');
    Route::get('/update/{id}/{qty}', 'website\CartController@updateCartItem');
    Route::get('/empty', 'website\CartController@emptyCart');
});
/**
 * Contact Us 
 * 
 **/
Route::get('/contact-us', 'website\SiteController@contactUs');
Route::post('/contact-us', 'website\SiteController@sendMail');

/**
 * Viva Payments
 *  
 **/
Route::get('checkout', function ()
{
    return redirect(url('/'));
});
// Route::get('checkout', 'CheckoutController@create');
Route::post('checkout', 'CheckoutController@store');