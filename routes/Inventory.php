<?php

use App\Http\Controllers\inv\BomController;
use App\Http\Controllers\inv\CheckerController;
use App\Http\Controllers\inv\InvAccessController;
use App\Http\Controllers\inv\InvDashboardController;
use App\Http\Controllers\inv\MaterialController;
use App\Http\Controllers\inv\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory Module
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/inventory')->name('inv.')->middleware(['auth', 'verified', 'module.access:INV'])->group(function () {
    Route::get('/', [InvDashboardController::class, 'managerDashboard'])->name('dashboard');
    Route::get('/materials', [MaterialController::class, 'material'])->name('materials');
    Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store');
    Route::delete('/materials/{id}', [MaterialController::class, 'destroy'])->name('materials.destroy');

    Route::get('/products', [ProductController::class, 'product'])->name('products');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::post('/products/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::delete('/products/image/{imageId}', [ProductController::class, 'destroyImage'])->name('products.image.destroy');

    Route::get('/bom', [BomController::class, 'index'])->name('bom');
    Route::post('/bom', [BomController::class, 'store'])->name('bom.store');
    Route::put('/bom/{id}', [BomController::class, 'update'])->name('bom.update');
    Route::delete('/bom/{id}', [BomController::class, 'destroy'])->name('bom.destroy');

    Route::get('/checker', [CheckerController::class, 'index'])->name('checker');
    // ✅ FIXED: Use MaterialController@procurement for creating procurement requests
    Route::post('/checker/procurement/{material}', [MaterialController::class, 'procurement'])->name('checker.procurement');
    Route::post('/checker/order/{order}', [CheckerController::class, 'checkOrder'])->name('checker.order');

    Route::get('/access', [InvAccessController::class, 'index'])->name('access');
    Route::post('/access/update', [InvAccessController::class, 'update'])->name('access.update');

    Route::get('/product', [ProductController::class, 'product'])->name('manager.product');
    Route::post('/product', [ProductController::class, 'store'])->name('manager.product.store');
    Route::post('/product/{id}/update', [ProductController::class, 'update'])->name('manager.product.update');
    Route::delete('/product/image/{imageId}', [ProductController::class, 'destroyImage'])->name('manager.product.image.destroy');
    Route::delete('/product/{id}', [ProductController::class, 'destroy'])->name('manager.product.destroy');

    // Edit view for material
    Route::patch('/materials/{material}', [MaterialController::class, 'update'])->name('materials.update');
});