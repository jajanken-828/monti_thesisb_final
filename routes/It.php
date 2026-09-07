<?php

use App\Http\Controllers\hrm\AccessController;
use App\Http\Controllers\hrm\InterviewController;
use App\Http\Controllers\hrm\TraineeController;
use App\Http\Controllers\it\ItDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| IT & Systems Admin (IT) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/it')->name('it.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/interview', [InterviewController::class, 'index'])->name('interview.index');
    Route::get('/trainee', [TraineeController::class, 'index'])->name('trainee.index');
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');

    Route::get('/manager', [ItDashboardController::class, 'managerDashboard'])
        ->middleware(['module.access:IT', 'position:manager'])
        ->name('manager.dashboard');

    Route::get('/staff', [ItDashboardController::class, 'staffDashboard'])
        ->middleware(['module.access:IT', 'position:staff'])
        ->name('employee.dashboard');
});