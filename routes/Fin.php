<?php

use App\Http\Controllers\fin\FinDashboardController;
use App\Http\Controllers\hrm\AccessController;
use App\Http\Controllers\hrm\InterviewController;
use App\Http\Controllers\hrm\TraineeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Financial Operations (FIN) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/fin')->name('fin.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/interview', [InterviewController::class, 'index'])->name('interview.index');
    Route::get('/trainee', [TraineeController::class, 'index'])->name('trainee.index');
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');
    Route::post('/access/update', [AccessController::class, 'update'])->name('access.update');

    Route::get('/manager', [FinDashboardController::class, 'managerDashboard'])
        ->middleware(['module.access:FIN', 'position:manager'])
        ->name('manager.dashboard');
    Route::get('/manager/receivables', [FinDashboardController::class, 'receivables'])
        ->middleware(['module.access:FIN', 'position:manager'])
        ->name('manager.receivables');
    Route::get('/manager/payables', [FinDashboardController::class, 'payables'])
        ->middleware(['module.access:FIN', 'position:manager'])
        ->name('manager.payables');
    Route::get('/manager/expenses', [FinDashboardController::class, 'expenses'])
        ->middleware(['module.access:FIN', 'position:manager'])
        ->name('manager.expenses');
    Route::get('/manager/payroll', [FinDashboardController::class, 'payroll'])
        ->middleware(['module.access:FIN', 'position:manager'])
        ->name('manager.payroll');
    Route::get('/manager/reports', [FinDashboardController::class, 'reports'])
        ->middleware(['module.access:FIN', 'position:manager'])
        ->name('manager.reports');

    Route::get('/staff', [FinDashboardController::class, 'staffDashboard'])
        ->middleware(['module.access:FIN', 'position:staff'])
        ->name('employee.dashboard');
});