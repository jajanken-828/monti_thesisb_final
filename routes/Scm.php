<?php

use App\Http\Controllers\scm\ScmAccessController;
use App\Http\Controllers\scm\ScmProcurementOrderController;
use App\Http\Controllers\scm\ScmSalesOrderController;
use App\Http\Controllers\scm\ScmVendorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Supply Chain Management (SCM) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/scm')->name('scm.')->middleware(['auth', 'verified', 'module.access:SCM'])->group(function () {
    // Sales Orders (from ECO)
    Route::get('/sales-orders', [ScmSalesOrderController::class, 'index'])->name('sales-orders');
    Route::post('/sales-orders/{order}/check-inventory', [ScmSalesOrderController::class, 'checkInventory'])->name('sales-order.check-inventory');
    Route::post('/sales-orders/{order}/push-to-production', [ScmSalesOrderController::class, 'pushToProduction'])->name('sales-order.push-to-production');
    Route::post('/sales-orders/sales/{salesOrder}/check-inventory', [ScmSalesOrderController::class, 'checkInventorySalesOrder'])->name('sales-order.check-inventory-sales');
    Route::post('/sales-orders/sales/{salesOrder}/push-to-production', [ScmSalesOrderController::class, 'pushToProductionSalesOrder'])->name('sales-order.push-to-production-sales');
    Route::get('/sales-orders/check-inventory-instant/{type}/{id}', [ScmSalesOrderController::class, 'checkInventoryInstant'])
        ->name('sales-order.check-inventory-instant');

    // Procurement Orders (requests from Inventory)
    Route::get('/procurement-orders', [ScmProcurementOrderController::class, 'index'])->name('procurement-orders');
    Route::post('/procurement-orders/{materialRequest}/send', [ScmProcurementOrderController::class, 'sendToProcurementModule'])->name('procurement-order.send');

    // Vendor Management
    Route::get('/vendors', [ScmVendorController::class, 'index'])->name('vendors');
    Route::post('/vendors/{registration}/approve', [ScmVendorController::class, 'approve'])->name('vendors.approve');
    Route::post('/vendors/{registration}/reject', [ScmVendorController::class, 'reject'])->name('vendors.reject');

    // Access Control (CEO only)
    Route::get('/access', [ScmAccessController::class, 'index'])->name('access.index');
    Route::post('/access/update', [ScmAccessController::class, 'update'])->name('access.update');
});