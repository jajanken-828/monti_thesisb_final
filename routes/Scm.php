<?php

use App\Http\Controllers\Scm\ScmAccessController;
use App\Http\Controllers\Scm\ScmProcurementOrderController;
use App\Http\Controllers\Scm\ScmSalesOrderController;
use App\Http\Controllers\Scm\ScmVendorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Supply Chain Management (SCM) Routes
|--------------------------------------------------------------------------
| Every route carries page.permission so explicit per-page view/edit grants
| are enforced. Staff without explicit rows keep full access via the
| middleware's native shortcut.
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/scm')->name('scm.')->middleware(['auth', 'verified', 'module.access:SCM'])->group(function () {
    // Sales Orders (from ECO)
    Route::get('/sales-orders', [ScmSalesOrderController::class, 'index'])
        ->middleware('page.permission:sales,view')->name('sales-orders');
    Route::post('/sales-orders/{order}/check-inventory', [ScmSalesOrderController::class, 'checkInventory'])
        ->middleware('page.permission:sales,edit')->name('sales-order.check-inventory');
    Route::post('/sales-orders/{order}/push-to-production', [ScmSalesOrderController::class, 'pushToProduction'])
        ->middleware('page.permission:sales,edit')->name('sales-order.push-to-production');
    Route::post('/sales-orders/sales/{salesOrder}/check-inventory', [ScmSalesOrderController::class, 'checkInventorySalesOrder'])
        ->middleware('page.permission:sales,edit')->name('sales-order.check-inventory-sales');
    Route::post('/sales-orders/sales/{salesOrder}/push-to-production', [ScmSalesOrderController::class, 'pushToProductionSalesOrder'])
        ->middleware('page.permission:sales,edit')->name('sales-order.push-to-production-sales');
    Route::get('/sales-orders/check-inventory-instant/{type}/{id}', [ScmSalesOrderController::class, 'checkInventoryInstant'])
        ->middleware('page.permission:sales,view')
        ->name('sales-order.check-inventory-instant');

    // Procurement Orders (requests from Inventory)
    Route::get('/procurement-orders', [ScmProcurementOrderController::class, 'index'])
        ->middleware('page.permission:procurement,view')->name('procurement-orders');
    Route::post('/procurement-orders/{materialRequest}/send', [ScmProcurementOrderController::class, 'sendToProcurementModule'])
        ->middleware('page.permission:procurement,edit')->name('procurement-order.send');

    // Vendor Management
    Route::get('/vendors', [ScmVendorController::class, 'index'])
        ->middleware('page.permission:vendor,view')->name('vendors');
    Route::post('/vendors/{registration}/approve', [ScmVendorController::class, 'approve'])
        ->middleware('page.permission:vendor,edit')->name('vendors.approve');
    Route::post('/vendors/{registration}/reject', [ScmVendorController::class, 'reject'])
        ->middleware('page.permission:vendor,edit')->name('vendors.reject');

    // Access Control (CEO only)
    Route::get('/access', [ScmAccessController::class, 'index'])
        ->middleware('page.permission:access,view')->name('access.index');
    Route::post('/access/update', [ScmAccessController::class, 'update'])
        ->middleware('page.permission:access,edit')->name('access.update');
});
