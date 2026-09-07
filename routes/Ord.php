<?php

use App\Http\Controllers\hrm\AccessController;
use App\Http\Controllers\hrm\InterviewController;
use App\Http\Controllers\hrm\TraineeController;
use App\Http\Controllers\ord\OrdAccessController;
use App\Http\Controllers\ord\OrdDeliveryController;
use App\Http\Controllers\ord\OrdOrdersController;
use App\Http\Controllers\ord\OrdProductionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Order Processing (ORD) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/ord')->name('ord.')->middleware(['auth', 'verified', 'module.access:ORD'])->group(function () {
    // Legacy HRM-style routes (keep for backward compatibility)
    Route::get('/interview', [InterviewController::class, 'index'])->name('interview.index');
    Route::get('/trainee', [TraineeController::class, 'index'])->name('trainee.index');
    // Staff access control page (keep original name for sidebar)
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');
    Route::post('/access/update', [AccessController::class, 'update'])->name('access.update');

    // NEW Order Management Core Pages
    Route::get('/orders', [OrdOrdersController::class, 'index'])->name('orders');
    Route::post('/orders/payment', [OrdOrdersController::class, 'updatePayment'])->name('orders.payment');
    Route::get('/orders/receipt/{type}/{id}', [OrdOrdersController::class, 'downloadReceipt'])->name('orders.download-receipt');

    Route::get('/productions', [OrdProductionsController::class, 'index'])->name('productions');
    Route::get('/delivery', [OrdDeliveryController::class, 'index'])->name('delivery');
    Route::get('/delivery/{id}/track', [OrdDeliveryController::class, 'track'])->name('delivery.track');

    // CEO Access Control - unique name to avoid conflict
    Route::get('/access-control', [OrdAccessController::class, 'index'])->name('ceo-access.index');
    Route::post('/access-control/update', [OrdAccessController::class, 'update'])->name('ceo-access.update');
});