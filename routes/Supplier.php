<?php

use App\Http\Controllers\Auth\SupplierAuthController;
use App\Http\Controllers\SUPPLIERS\SupplierDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vendor/Supplier Gateway Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest:supplier')->group(function () {
    Route::get('supplier/login', [SupplierAuthController::class, 'showLogin'])->name('supplier.login');
    Route::post('supplier/login', [SupplierAuthController::class, 'login'])->name('supplier.login.store');
    Route::get('supplier/register', [SupplierAuthController::class, 'create'])->name('supplier.register');
    Route::post('supplier/register', [SupplierAuthController::class, 'store'])->name('supplier.register.store');
});

Route::post('supplier/logout', [SupplierAuthController::class, 'logout'])
    ->middleware('auth:supplier')
    ->name('supplier.logout');

/*
|--------------------------------------------------------------------------
| Protected Supplier Portal Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:supplier')->prefix('supplier')->name('supplier.')->group(function () {
    Route::get('/dashboard', [SupplierDashboardController::class, 'index'])->name('dashboard');
    Route::post('/rfq/{id}/respond', [SupplierDashboardController::class, 'submitQuotation'])->name('rfq.respond');
    Route::get('/orders', [SupplierDashboardController::class, 'purchaseOrders'])->name('orders');
    Route::post('/orders/{id}/status', [SupplierDashboardController::class, 'updateOrderStatus'])->name('orders.update_status');
    Route::post('/orders/{id}/invoice', [SupplierDashboardController::class, 'createInvoice'])->name('orders.invoice');
});