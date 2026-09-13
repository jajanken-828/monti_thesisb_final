<?php

use App\Http\Controllers\Fin\FinDashboardController;
use App\Http\Controllers\Hrm\AccessController;
use App\Http\Controllers\Hrm\InterviewController;
use App\Http\Controllers\Hrm\TraineeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Financial Operations (FIN) Routes
|--------------------------------------------------------------------------
| Read-only dashboards carry page.permission:dashboard/receivables/... so
| explicit per-page grants are enforced. FIN views perform no API writes.
| (Top interview/trainee/access links render HRM pages and are untouched.)
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/fin')->name('fin.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/interview', [InterviewController::class, 'index'])->name('interview.index');
    Route::get('/trainee', [TraineeController::class, 'index'])->name('trainee.index');
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');
    Route::post('/access/update', [AccessController::class, 'update'])->name('access.update');

    Route::get('/manager', [FinDashboardController::class, 'managerDashboard'])
        ->middleware(['module.access:FIN', 'position:manager', 'page.permission:dashboard,view'])
        ->name('manager.dashboard');
    Route::get('/manager/receivables', [FinDashboardController::class, 'receivables'])
        ->middleware(['module.access:FIN', 'position:manager', 'page.permission:receivables,view'])
        ->name('manager.receivables');
    Route::get('/manager/payables', [FinDashboardController::class, 'payables'])
        ->middleware(['module.access:FIN', 'position:manager', 'page.permission:payables,view'])
        ->name('manager.payables');
    Route::get('/manager/expenses', [FinDashboardController::class, 'expenses'])
        ->middleware(['module.access:FIN', 'position:manager', 'page.permission:expenses,view'])
        ->name('manager.expenses');
    Route::get('/manager/payroll', [FinDashboardController::class, 'payroll'])
        ->middleware(['module.access:FIN', 'position:manager', 'page.permission:payroll,view'])
        ->name('manager.payroll');
    Route::get('/manager/reports', [FinDashboardController::class, 'reports'])
        ->middleware(['module.access:FIN', 'position:manager', 'page.permission:reports,view'])
        ->name('manager.reports');

    Route::get('/staff', [FinDashboardController::class, 'staffDashboard'])
        ->middleware(['module.access:FIN', 'position:staff', 'page.permission:dashboard,view'])
        ->name('employee.dashboard');
});
