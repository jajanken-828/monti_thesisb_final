<?php

use App\Http\Controllers\hrm\AccessController;
use App\Http\Controllers\hrm\AnalyticsController;
use App\Http\Controllers\hrm\ApplicantController as HrmApplicantController;
use App\Http\Controllers\hrm\EmployeeController;
use App\Http\Controllers\hrm\HrmDashboardController;
use App\Http\Controllers\hrm\InterviewController;
use App\Http\Controllers\hrm\OnboardingController;
use App\Http\Controllers\hrm\PayrollController;
use App\Http\Controllers\hrm\PayrollRatesController;
use App\Http\Controllers\hrm\PositionController;
use App\Http\Controllers\hrm\TraineeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Human Resources Management (HRM) Routes
|--------------------------------------------------------------------------
*/
Route::get('/active-positions', [PositionController::class, 'getActivePositions'])->name('positions.active');
Route::prefix('dashboard/hrm')->name('hrm.')->middleware(['auth', 'verified'])->group(function () {
    // Dashboard (page: dashboard)
    Route::get('/', [HrmDashboardController::class, 'index'])
        ->middleware('page.permission:dashboard,view')
        ->name('dashboard');

    // Employees (page: employee)
    Route::get('/employees', [EmployeeController::class, 'index'])
        ->middleware('page.permission:employee,view')
        ->name('employees.index');
    Route::get('/employees/{id}', [EmployeeController::class, 'show'])
        ->middleware('page.permission:employee,view')
        ->name('employees.show');
    Route::patch('/employees/{id}', [EmployeeController::class, 'update'])
        ->middleware('page.permission:employee,edit')
        ->name('employees.update');
    Route::delete('/employees/{id}', [EmployeeController::class, 'toggleStatus'])
        ->middleware('page.permission:employee,edit')
        ->name('employees.toggle-status');
    Route::post('/employees/{id}/promote-to-manager', [EmployeeController::class, 'promoteToManager'])
        ->middleware('page.permission:employee,edit')
        ->name('employees.promote-to-manager');
    Route::post('/employees/{id}/demote-to-staff', [EmployeeController::class, 'demoteToStaff'])
        ->middleware('page.permission:employee,edit')
        ->name('employees.demote-to-staff');

    // Applications (page: application)
    Route::get('/applications', [HrmApplicantController::class, 'index'])
        ->middleware('page.permission:application,view')
        ->name('applications.index');
    Route::post('/applications', [HrmApplicantController::class, 'store'])
        ->middleware('page.permission:application,edit')
        ->name('applications.store');
    Route::post('/applications/{id}/accept', [HrmApplicantController::class, 'accept'])
        ->middleware('page.permission:application,edit')
        ->name('applications.accept');
    Route::post('/applications/{id}/reject', [HrmApplicantController::class, 'reject'])
        ->middleware('page.permission:application,edit')
        ->name('applications.reject');
    Route::get('/rejected', [HrmApplicantController::class, 'rejected'])
        ->middleware('page.permission:application,view')
        ->name('applications.rejected');

    // Interview (page: interview) – HRM now manages all interviews across modules
    Route::get('/interview', [InterviewController::class, 'index'])
        ->middleware('page.permission:interview,view')
        ->name('interview.index');
    Route::post('/interview/{id}/schedule', [InterviewController::class, 'schedule'])
        ->middleware('page.permission:interview,edit')
        ->name('interview.schedule');
    Route::post('/interview/{id}/pass', [InterviewController::class, 'pass'])
        ->middleware('page.permission:interview,edit')
        ->name('interview.pass');
    Route::post('/interview/{id}/fail', [InterviewController::class, 'fail'])
        ->middleware('page.permission:interview,edit')
        ->name('interview.fail');
    Route::post('/interview/{id}/pass-to-other', [InterviewController::class, 'passToOtherModule'])
        ->middleware('page.permission:interview,edit')
        ->name('interview.pass-to-other');

    // Trainee (page: trainee) – HRM now manages all trainees across modules
    Route::get('/trainee', [TraineeController::class, 'index'])
        ->middleware('page.permission:trainee,view')
        ->name('trainee.index');
    Route::post('/trainee/{id}/grade', [TraineeController::class, 'grade'])
        ->middleware('page.permission:trainee,edit')
        ->name('trainee.grade');
    Route::post('/trainee/{id}/pass', [TraineeController::class, 'pass'])
        ->middleware('page.permission:trainee,edit')
        ->name('trainee.pass');
    Route::post('/trainee/{id}/fail', [TraineeController::class, 'fail'])
        ->middleware('page.permission:trainee,edit')
        ->name('trainee.fail');

    // Onboarding (page: onboarding)
    Route::get('/onboarding', [OnboardingController::class, 'index'])
        ->middleware('page.permission:onboarding,view')
        ->name('onboarding.index');
    Route::post('/onboarding/{id}/convert', [OnboardingController::class, 'convert'])
        ->middleware('page.permission:onboarding,edit')
        ->name('onboarding.convert');

    // Access Control (page: access)
    Route::get('/access', [AccessController::class, 'index'])
        ->middleware('page.permission:access,view')
        ->name('access.index');
    Route::post('/access/update', [AccessController::class, 'update'])
        ->middleware('page.permission:access,edit')
        ->name('access.update');

    // Payroll (page: payroll)
    Route::get('/payroll', [PayrollController::class, 'index'])
        ->middleware('page.permission:payroll,view')
        ->name('payroll');

    // Generate Payroll Form
    Route::get('/payroll/generate', [PayrollController::class, 'create'])->name('payroll.generate');

    // Generate Payroll Submission (POST)
    Route::post('/payroll/generate', [PayrollController::class, 'generate'])
        ->middleware('page.permission:payroll,edit')
        ->name('payroll.generate.store');

    Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])
        ->middleware('page.permission:payroll,view')
        ->name('payroll.show');

    Route::post('/payroll/{payroll}/approve', [PayrollController::class, 'approve'])
        ->middleware('page.permission:payroll,edit')
        ->name('payroll.approve');

    Route::post('/payroll/{payroll}/reject', [PayrollController::class, 'reject'])
        ->middleware('page.permission:payroll,edit')
        ->name('payroll.reject');

    // Payroll Rates (simple version)
    Route::get('/payroll.rates', [PayrollController::class, 'rates'])->name('payroll.rates');
    Route::post('/payroll-rates', [PayrollController::class, 'updateRates'])->name('payroll.rates.update');

    // Analytics (page: analytics)
    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->middleware('page.permission:analytics,view')
        ->name('analytics');

    // Positions (no page permission needed, used internally)
    Route::get('/positions', [PositionController::class, 'index']);
    Route::post('/positions', [PositionController::class, 'store'])->name('positions.store');
    Route::patch('/positions/{id}/toggle-status', [PositionController::class, 'toggleStatus'])->name('positions.toggle-status');
    Route::delete('/positions/{id}', [PositionController::class, 'destroy'])->name('positions.destroy');
    Route::patch('/positions/{id}', [PositionController::class, 'update'])->name('positions.update');

    // ------------------------------------------------------------------
    // NEW: Payroll Rates Configuration (HRM managers / CEO)
    // ------------------------------------------------------------------
    Route::prefix('payroll-rates')->name('payroll-rates.')->middleware(['role:CEO,HRM'])->group(function () {
        Route::get('/', [PayrollRatesController::class, 'index'])->name('index');
        Route::post('/set', [PayrollRatesController::class, 'storePayrollSet'])->name('store-set');
        Route::put('/set/{payrollSet}', [PayrollRatesController::class, 'updatePayrollSet'])->name('update-set');
        Route::post('/set/{payrollSet}/toggle', [PayrollRatesController::class, 'togglePayrollSet'])->name('toggle-set');
        Route::post('/contribution', [PayrollRatesController::class, 'storeContributionRate'])->name('store-contribution');
        Route::put('/contribution/{rate}', [PayrollRatesController::class, 'updateContributionRate'])->name('update-contribution');
        Route::post('/contribution/{rate}/toggle', [PayrollRatesController::class, 'toggleContributionRate'])->name('toggle-contribution');
    });
});