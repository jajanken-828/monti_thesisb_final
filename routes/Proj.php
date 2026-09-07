<?php

use App\Http\Controllers\hrm\AccessController;
use App\Http\Controllers\hrm\InterviewController;
use App\Http\Controllers\hrm\TraineeController;
use App\Http\Controllers\proj\ProjDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Project Automation (PROJ) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/proj')->name('proj.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/interview', [InterviewController::class, 'index'])->name('interview.index');
    Route::get('/trainee', [TraineeController::class, 'index'])->name('trainee.index');
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');

    Route::get('/manager', [ProjDashboardController::class, 'managerDashboard'])
        ->middleware(['module.access:PROJ', 'position:manager'])
        ->name('manager.dashboard');

    Route::get('/staff', [ProjDashboardController::class, 'staffDashboard'])
        ->middleware(['module.access:PROJ', 'position:staff'])
        ->name('employee.dashboard');
});