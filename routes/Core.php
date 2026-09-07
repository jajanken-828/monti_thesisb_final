<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\hrm\ApplicantController as HrmApplicantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\trainee\TraineeAttendanceController;
use App\Http\Controllers\trainee\TraineePayslipController;
use App\Http\Controllers\trainee\TraineeTimeKeepingController;
use App\Http\Controllers\users\AppController;
use App\Http\Controllers\users\ClockController;
use App\Http\Controllers\users\leaveController as UserLeaveController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return inertia('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => app()->version(),
        'phpVersion' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Public Career Application Routes
|--------------------------------------------------------------------------
*/
Route::get('/apply', function () {
    return inertia('Auth/apply');
})->name('apply');

Route::post('/apply/store', [HrmApplicantController::class, 'store'])->name('applicants.public.store');

/*
|--------------------------------------------------------------------------
| Authenticated User Core Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, '350'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Unified Employee UI Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'position:staff,manager'])->prefix('dashboard/employee-ui')->group(function () {
    Route::get('/', [AppController::class, 'index'])->name('employee.ui.dashboard');
    Route::get('/clock', [ClockController::class, 'clock'])->name('employee.ui.clock');
    Route::post('/clock/toggle', [ClockController::class, 'toggle'])->name('employee.attendance.toggle');
    Route::get('/leave', [UserLeaveController::class, 'leave'])->name('employee.ui.leave');
    Route::post('/leave', [UserLeaveController::class, 'store'])->name('employee.leave.store');
    Route::get('/payslip', function () {
        return inertia('Dashboard/USERS/payslip');
    })->name('employee.ui.payslip');
});

/*
|--------------------------------------------------------------------------
| Unified Trainee Portal Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/trainee')->middleware(['auth', 'verified', 'position:trainee'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('trainee.dashboard');
    Route::get('/timekeeping', [TraineeTimeKeepingController::class, 'index'])->name('trainee.timekeeping');
    Route::post('/timekeeping/clock', [TraineeTimeKeepingController::class, 'clockInOut'])->name('trainee.timekeeping.clock');
    Route::get('/attendance', [TraineeAttendanceController::class, 'index'])->name('trainee.attendance');
    Route::get('/payslip', [TraineePayslipController::class, 'index'])->name('trainee.payslip');
    Route::get('/payslip/{payroll}', [TraineePayslipController::class, 'show'])->name('trainee.payslip.show');
});