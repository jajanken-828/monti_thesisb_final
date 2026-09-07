<?php

use App\Http\Controllers\eco\EcoAccessController;
use App\Http\Controllers\eco\EcoCreditController;
use App\Http\Controllers\eco\EcoDashboardController;
use App\Http\Controllers\eco\EcoInquiryController;
use App\Http\Controllers\eco\EcoPushController;
use App\Http\Controllers\eco\EcoStoreController;
use App\Http\Controllers\eco\EcoSupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| E‑Commerce (ECO) Module
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/eco')->name('eco.')->middleware(['auth', 'verified', 'module.access:ECO'])->group(function () {
    // Dashboard
    Route::get('/', [EcoDashboardController::class, 'index'])->name('dashboard');
    // Store (product catalog)
    Route::get('/store', [EcoStoreController::class, 'index'])->name('store');
    // Inquiries & Conversations
    Route::get('/inquiries', [EcoInquiryController::class, 'index'])->name('inquiries');
    Route::get('/inquiries/{inquiry}', [EcoInquiryController::class, 'show'])->name('inquiry.show');
    Route::post('/inquiries/{inquiry}/message', [EcoInquiryController::class, 'sendMessage'])->name('inquiry.message');
    Route::post('/inquiries/{inquiry}/meeting', [EcoInquiryController::class, 'setMeeting'])->name('inquiry.meeting');
    Route::post('/inquiries/{inquiry}/quotation', [EcoInquiryController::class, 'issueQuotation'])->name('inquiry.quotation');
    Route::get('/clients/{client}/credit-check', [EcoInquiryController::class, 'creditCheck'])->name('credit.check');

    // NEW: Attachment actions for ECO
    Route::post('/attachment/{attachment}/create-recipe', [EcoInquiryController::class, 'createRecipeFromAttachment'])
        ->name('attachment.create-recipe');
    Route::post('/attachment/{attachment}/create-job-order', [EcoInquiryController::class, 'createJobOrderFromPO'])
        ->name('attachment.create-job-order');

    // Credit ledger
    Route::get('/credit', [EcoCreditController::class, 'index'])->name('credit');
    Route::post('/credit/approve/{order}', [EcoCreditController::class, 'approveCreditReview'])->name('credit.approve');
    // ✅ FIXED: renamed duplicate route to avoid conflict
    Route::post('/credit/approve-order/{order}', [EcoCreditController::class, 'approveOrder'])->name('credit.approve-order');
    Route::post('/credit/reject/{order}', [EcoCreditController::class, 'rejectOrder'])->name('credit.reject');
    // Push to SCM / Order Management
    Route::get('/push', [EcoPushController::class, 'index'])->name('push');
    Route::post('/push/scm/{order}', [EcoPushController::class, 'pushToScm'])->name('push.scm');
    Route::post('/push/order-mgmt/{order}', [EcoPushController::class, 'pushToOrderMgmt'])->name('push.ordermgmt');
    // Access control (only CEO)
    Route::get('/access', [EcoAccessController::class, 'index'])->name('access');
    Route::post('/access/update', [EcoAccessController::class, 'update'])->name('access.update');

    // Inside ECO route group
    Route::get('/suppliers', [EcoSupplierController::class, 'index'])->name('suppliers');
    Route::get('/suppliers/{supplier}/conversation', [EcoSupplierController::class, 'conversation'])->name('supplier.conversation');
    Route::post('/suppliers/{supplier}/message', [EcoSupplierController::class, 'sendMessage'])->name('supplier.message');
    Route::post('/suppliers/{supplier}/meeting', [EcoSupplierController::class, 'setMeeting'])->name('supplier.meeting');
    Route::get('/suppliers/{supplier}/credit-check', [EcoSupplierController::class, 'creditCheck'])->name('supplier.credit-check');
    Route::post('/suppliers/{supplier}/request', [EcoSupplierController::class, 'sendRequest'])->name('supplier.request');
    Route::get('/suppliers/{supplier}/conversation', [EcoSupplierController::class, 'conversation'])->name('supplier.conversation');

    Route::post('/push/manual-po', [EcoPushController::class, 'manualStore'])->name('po.manual_store');
    Route::post('/push/manual-jo', [EcoPushController::class, 'manualJobOrder'])->name('jo.manual_store');
});