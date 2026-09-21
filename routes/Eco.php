<?php

use App\Http\Controllers\Eco\EcoCreditController;
use App\Http\Controllers\Eco\EcoDashboardController;
use App\Http\Controllers\Eco\EcoInquiryController;
use App\Http\Controllers\Eco\EcoPushController;
use App\Http\Controllers\Eco\EcoStoreController;
use App\Http\Controllers\Eco\EcoSupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| E‑Commerce (ECO) Module
|--------------------------------------------------------------------------
| Every route carries page.permission so explicit per-page view/edit grants
| are enforced. Staff without explicit rows keep full access via the
| middleware's native shortcut.
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/eco')->name('eco.')->middleware(['auth', 'verified', 'module.access:ECO'])->group(function () {
    // Dashboard
    Route::get('/', [EcoDashboardController::class, 'index'])
        ->middleware('page.permission:dashboard,view')->name('dashboard');
    // Store (product catalog)
    Route::get('/store', [EcoStoreController::class, 'index'])
        ->middleware('page.permission:store,view')->name('store');
    // Inquiries & Conversations
    Route::get('/inquiries', [EcoInquiryController::class, 'index'])
        ->middleware('page.permission:inquiry,view')->name('inquiries');
    Route::get('/inquiries/{inquiry}', [EcoInquiryController::class, 'show'])
        ->middleware('page.permission:inquiry,view')->name('inquiry.show');
    Route::post('/inquiries/{inquiry}/message', [EcoInquiryController::class, 'sendMessage'])
        ->middleware('page.permission:inquiry,edit')->name('inquiry.message');
    Route::post('/inquiries/{inquiry}/meeting', [EcoInquiryController::class, 'setMeeting'])
        ->middleware('page.permission:inquiry,edit')->name('inquiry.meeting');
    Route::post('/inquiries/{inquiry}/quotation', [EcoInquiryController::class, 'issueQuotation'])
        ->middleware('page.permission:inquiry,edit')->name('inquiry.quotation');
    Route::get('/clients/{client}/credit-check', [EcoInquiryController::class, 'creditCheck'])
        ->middleware('page.permission:credit,view')->name('credit.check');

    // NEW: Attachment actions for ECO
    Route::post('/attachment/{attachment}/create-recipe', [EcoInquiryController::class, 'createRecipeFromAttachment'])
        ->middleware('page.permission:inquiry,edit')->name('attachment.create-recipe');
    Route::post('/attachment/{attachment}/create-job-order', [EcoInquiryController::class, 'createJobOrderFromPO'])
        ->middleware('page.permission:inquiry,edit')->name('attachment.create-job-order');

    // Credit ledger
    Route::get('/credit', [EcoCreditController::class, 'index'])
        ->middleware('page.permission:credit,view')->name('credit');
    Route::post('/credit/approve/{order}', [EcoCreditController::class, 'approveCreditReview'])
        ->middleware('page.permission:credit,edit')->name('credit.approve');
    // ✅ FIXED: renamed duplicate route to avoid conflict
    Route::post('/credit/approve-order/{order}', [EcoCreditController::class, 'approveOrder'])
        ->middleware('page.permission:credit,edit')->name('credit.approve-order');
    Route::post('/credit/reject/{order}', [EcoCreditController::class, 'rejectOrder'])
        ->middleware('page.permission:credit,edit')->name('credit.reject');
    // Push to SCM / Order Management
    Route::get('/push', [EcoPushController::class, 'index'])
        ->middleware('page.permission:push,view')->name('push');
    // DSS: sustainability preview BEFORE accepting (quick inventory check)
    Route::get('/push/dss/{order}', [EcoPushController::class, 'dssCheck'])
        ->middleware('page.permission:push,view')->name('push.dss');
    Route::post('/push/scm/{order}', [EcoPushController::class, 'pushToScm'])
        ->middleware('page.permission:push,edit')->name('push.scm');
    Route::post('/push/order-mgmt/{order}', [EcoPushController::class, 'pushToOrderMgmt'])
        ->middleware('page.permission:push,edit')->name('push.ordermgmt');
    // Access control is centralized in the CEO module (view/request)
    // and IT Access Control (fulfilment).

    // Inside ECO route group
    Route::get('/suppliers', [EcoSupplierController::class, 'index'])
        ->middleware('page.permission:supplier,view')->name('suppliers');
    Route::get('/suppliers/{supplier}/conversation', [EcoSupplierController::class, 'conversation'])
        ->middleware('page.permission:supplier,view')->name('supplier.conversation');
    Route::post('/suppliers/{supplier}/message', [EcoSupplierController::class, 'sendMessage'])
        ->middleware('page.permission:supplier,edit')->name('supplier.message');
    Route::post('/suppliers/{supplier}/meeting', [EcoSupplierController::class, 'setMeeting'])
        ->middleware('page.permission:supplier,edit')->name('supplier.meeting');
    Route::get('/suppliers/{supplier}/credit-check', [EcoSupplierController::class, 'creditCheck'])
        ->middleware('page.permission:supplier,view')->name('supplier.credit-check');
    Route::post('/suppliers/{supplier}/request', [EcoSupplierController::class, 'sendRequest'])
        ->middleware('page.permission:supplier,edit')->name('supplier.request');
    Route::get('/suppliers/{supplier}/conversation', [EcoSupplierController::class, 'conversation'])
        ->middleware('page.permission:supplier,view')->name('supplier.conversation');

    Route::post('/push/manual-po', [EcoPushController::class, 'manualStore'])
        ->middleware('page.permission:push,edit')->name('po.manual_store');
    Route::post('/push/manual-jo', [EcoPushController::class, 'manualJobOrder'])
        ->middleware('page.permission:push,edit')->name('jo.manual_store');
});
