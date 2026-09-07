<?php

use App\Http\Controllers\hrm\AccessController;
use App\Http\Controllers\hrm\InterviewController;
use App\Http\Controllers\hrm\TraineeController;
use App\Http\Controllers\pro\ProAccessController;
use App\Http\Controllers\pro\ProcurementController;
use App\Http\Controllers\pro\ProDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Procurement Sub-System (PRO) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/pro')->name('pro.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/interview', [InterviewController::class, 'index'])->name('interview.index');
    Route::get('/trainee', [TraineeController::class, 'index'])->name('trainee.index');
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');

    Route::middleware(['module.access:PRO', 'position:manager'])->prefix('manager')->name('manager.')->group(function () {
        Route::get('/', [ProDashboardController::class, 'managerDashboard'])->name('dashboard');
        Route::get('/material-requests', [ProcurementController::class, 'materialRequests'])->name('material-requests');
        Route::post('/material-requests/rfq', [ProcurementController::class, 'createRFQ'])->name('rfq.store');
        Route::get('/supplier-quotations', [ProcurementController::class, 'supplierQuotations'])->name('supplier-quotations');
        Route::post('/quotations/{responseId}/accept', [ProcurementController::class, 'acceptQuotation'])->name('quotations.accept');
        Route::post('/quotations/{responseId}/decline', [ProcurementController::class, 'declineQuotation'])->name('quotations.decline');
        Route::get('/receipt', [ProcurementController::class, 'receipt'])->name('receipt');
        Route::post('/purchase-orders/{poId}/send', [ProcurementController::class, 'sendPurchaseOrder'])->name('purchase-orders.send');
        Route::post('/invoices/{invoiceId}/pay', [ProcurementController::class, 'payInvoice'])->name('invoices.pay');
        Route::get('/access', [ProAccessController::class, 'index'])->name('access.index')
            ->middleware('role:CEO');
        Route::post('/access/update', [ProAccessController::class, 'update'])->name('access.update')
            ->middleware('role:CEO');
    });
});