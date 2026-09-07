<?php

use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\client\ClientConversationController;
use App\Http\Controllers\client\ClientDashboardController;
use App\Http\Controllers\client\ClientInvoiceController;
use App\Http\Controllers\client\ClientProductsController;
use App\Http\Controllers\client\ClientProfileController;
use App\Http\Controllers\client\ClientReceivingController;
use App\Http\Controllers\client\ClientSupportController;
use App\Http\Controllers\client\OrdersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| B2B Client Gateway Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest:client')->group(function () {
    Route::get('client/register', [ClientAuthController::class, 'create'])->name('client.register');
    Route::post('client/register', [ClientAuthController::class, 'store'])->name('client.register.store');
    Route::get('client/login', [ClientAuthController::class, 'showLogin'])->name('client.login');
    Route::post('client/login', [ClientAuthController::class, 'login'])->name('client.login.store');
});

Route::post('client/logout', [ClientAuthController::class, 'logout'])
    ->middleware('auth:client')
    ->name('client.logout');

/*
|--------------------------------------------------------------------------
| Protected Client B2B Portal Routes (Restructured)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:client')->prefix('partner')->name('client.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
    // Products & Inquiries
    Route::get('/products', [ClientProductsController::class, 'index'])->name('products');
    Route::post('/products/{product}/inquire', [ClientProductsController::class, 'inquire'])->name('products.inquire');
    Route::post('/products/bulk-inquire', [ClientProductsController::class, 'bulkInquire'])->name('products.bulk-inquire');
    // Conversations
    Route::get('/conversations', [ClientConversationController::class, 'index'])->name('conversations');
    Route::get('/conversations/{inquiry}', [ClientConversationController::class, 'show'])->name('conversation.show');
    Route::post('/conversations/{inquiry}/message', [ClientConversationController::class, 'sendMessage'])->name('conversation.message');
    Route::post('/quotations/{quotation}/accept', [ClientConversationController::class, 'acceptQuotation'])->name('client.quotation.accept');
    Route::post('/quotations/{quotation}/reject', [ClientConversationController::class, 'rejectQuotation'])->name('client.quotation.reject');
    Route::get('quotations/{quotation}/download', [ClientConversationController::class, 'downloadQuotation'])
        ->name('client.quotation.download');
    // CORRECTED: send-po route name
    Route::post('/conversation/{inquiry}/send-po', [ClientConversationController::class, 'sendPO'])->name('conversation.send-po');

    // NEW: Attachment approval by client
    Route::post('/conversation/attachment/{attachment}/approve', [ClientConversationController::class, 'approveAttachment'])
        ->name('conversation.attachment.approve');

    // Orders & Invoices (legacy support)
    Route::get('/orders', [OrdersController::class, 'orders'])->name('orders');
    Route::post('/orders/{order}/accept', [OrdersController::class, 'acceptPurchaseOrder'])->name('orders.accept');
    Route::get('/invoices', [ClientInvoiceController::class, 'index'])->name('invoices');
    // Receiving (delivery confirmation)
    Route::get('/receiving', [ClientReceivingController::class, 'index'])->name('receiving');
    Route::post('/receiving/{order}/mark', [ClientReceivingController::class, 'markReceived'])->name('receiving.mark');
    // Profile
    Route::get('/profile', [ClientProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ClientProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/logo', [ClientProfileController::class, 'destroyLogo'])->name('profile.logo.destroy');
    Route::post('/profile/logo', [ClientProfileController::class, 'storeLogo'])->name('profile.logo.store');
    // Support
    Route::get('/support', [ClientSupportController::class, 'index'])->name('support');
    Route::post('/support/complaint', [ClientSupportController::class, 'storeComplaint'])->name('support.complaint');

    // Legacy routes (preserved for backward compatibility)
    Route::post('/purchase-order', [ClientDashboardController::class, 'placeOrder'])->name('purchase-order.store');
    Route::post('/quotations/{quotation}/accept', [ClientConversationController::class, 'acceptQuotation'])->name('quotation.accept');
    Route::post('/quotations/{quotation}/reject', [ClientConversationController::class, 'rejectQuotation'])->name('quotation.reject');
});