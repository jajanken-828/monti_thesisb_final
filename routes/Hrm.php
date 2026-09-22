<?php

use App\Http\Controllers\Hrm\AnalyticsController;
use App\Http\Controllers\Hrm\ApplicantController as HrmApplicantController;
use App\Http\Controllers\Hrm\EmployeeController;
use App\Http\Controllers\Hrm\HrmDashboardController;
use App\Http\Controllers\Hrm\InterviewController;
use App\Http\Controllers\Hrm\OnboardingController;
use App\Http\Controllers\Hrm\PayrollController;
use App\Http\Controllers\Hrm\PayrollRatesController;
use App\Http\Controllers\Hrm\PositionController;
use App\Http\Controllers\Hrm\TraineeController;
// HRM_NEW bridge controllers
use App\Http\Controllers\Hrm\Workforce\DepartmentController as NewDepartmentController;
use App\Http\Controllers\Hrm\Workforce\PositionController as NewPositionController;
use App\Http\Controllers\Hrm\Workforce\EmploymentTypeController as NewEmploymentTypeController;
use App\Http\Controllers\Hrm\Workforce\EmployeeDirectoryController as NewEmployeeDirectoryController;
use App\Http\Controllers\Hrm\Workforce\LeaveManagementController as NewLeaveManagementController;
use App\Http\Controllers\Hrm\Workforce\AttendanceController as NewAttendanceController;
use App\Http\Controllers\Hrm\Workforce\TrainingController as NewTrainingController;
use App\Http\Controllers\Hrm\Recruitment\JobPostingController as NewJobPostingController;
use App\Http\Controllers\Hrm\Recruitment\ApplicationController as NewApplicationController;
use App\Http\Controllers\Hrm\Recruitment\InterviewController as NewInterviewController;
use App\Http\Controllers\Hrm\Onboarding\StatusController as NewOnboardingStatusController;
use App\Http\Controllers\Hrm\Onboarding\TemplateController as NewOnboardingTemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Human Resources Management (HRM) Routes
|--------------------------------------------------------------------------
*/
Route::get('/active-positions', [PositionController::class, 'getActivePositions'])->name('positions.active');
Route::get('/active-positions', [PositionController::class, 'getActivePositions'])->name('active.positions');
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
    Route::patch('/employees/{id}/role-position', [EmployeeController::class, 'updateRolePosition'])
        ->middleware('page.permission:employee,edit')
        ->name('employees.update-role-position');

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

    // Payroll (page: payroll)
    Route::get('/payroll', [PayrollController::class, 'index'])
        ->middleware('page.permission:payroll,view')
        ->name('payroll');
    Route::get('/payroll/employees.json', [PayrollController::class, 'employeesJson'])
        ->middleware('page.permission:payroll,view')
        ->name('payroll.employees.json');

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
    Route::prefix('payroll-rates')->name('payroll-rates.')->middleware(['role:HRM'])->group(function () {
        Route::get('/', [PayrollRatesController::class, 'index'])->name('index');
        Route::post('/set', [PayrollRatesController::class, 'storePayrollSet'])->name('store-set');
        Route::put('/set/{payrollSet}', [PayrollRatesController::class, 'updatePayrollSet'])->name('update-set');
        Route::post('/set/{payrollSet}/toggle', [PayrollRatesController::class, 'togglePayrollSet'])->name('toggle-set');
        Route::post('/contribution', [PayrollRatesController::class, 'storeContributionRate'])->name('store-contribution');
        Route::put('/contribution/{rate}', [PayrollRatesController::class, 'updateContributionRate'])->name('update-contribution');
        Route::post('/contribution/{rate}/toggle', [PayrollRatesController::class, 'toggleContributionRate'])->name('toggle-contribution');
    });

    // ==================================================================
    // HRM_NEW bridge (replaces old HRM UI, keeps old routes above for BC)
    // Workforce / Recruitment / Onboarding expected by HRM_NEW/*.vue
    // Permission mapping reuses canonical keys so IT/AccessGate keep working:
    //   workforce pages -> employee, recruitment -> application/interview,
    //   onboarding -> onboarding
    // ==================================================================
    Route::prefix('workforce')->name('workforce.')->group(function () {
        Route::get('/departments', [NewDepartmentController::class, 'index'])
            ->middleware('page.permission:employee,view')->name('departments.index');
        Route::post('/departments', [NewDepartmentController::class, 'store'])
            ->middleware('page.permission:employee,edit')->name('departments.store');
        Route::put('/departments/{department}', [NewDepartmentController::class, 'update'])
            ->middleware('page.permission:employee,edit')->name('departments.update');
        Route::post('/departments/{department}/archive', [NewDepartmentController::class, 'archive'])
            ->middleware('page.permission:employee,edit')->name('departments.archive');
        Route::post('/departments/{department}/reactivate', [NewDepartmentController::class, 'reactivate'])
            ->middleware('page.permission:employee,edit')->name('departments.reactivate');
        Route::get('/departments/export', [NewDepartmentController::class, 'export'])
            ->middleware('page.permission:employee,view')->name('departments.export');

        Route::get('/positions', [NewPositionController::class, 'index'])
            ->middleware('page.permission:employee,view')->name('positions.index');
        Route::post('/positions', [NewPositionController::class, 'store'])
            ->middleware('page.permission:employee,edit')->name('positions.store');
        Route::put('/positions/{position}', [NewPositionController::class, 'update'])
            ->middleware('page.permission:employee,edit')->name('positions.update');
        Route::delete('/positions/{position}', [NewPositionController::class, 'destroy'])
            ->middleware('page.permission:employee,edit')->name('positions.destroy');
        Route::post('/positions/{position}/archive', [NewPositionController::class, 'archive'])
            ->middleware('page.permission:employee,edit')->name('positions.archive');
        Route::post('/positions/{position}/reactivate', [NewPositionController::class, 'reactivate'])
            ->middleware('page.permission:employee,edit')->name('positions.reactivate');
        Route::get('/positions/{position}/employees', [NewPositionController::class, 'employees'])
            ->middleware('page.permission:employee,view')->name('positions.employees');

        Route::get('/employment-types', [NewEmploymentTypeController::class, 'index'])
            ->middleware('page.permission:employee,view')->name('employment-types.index');
        Route::post('/employment-types', [NewEmploymentTypeController::class, 'store'])
            ->middleware('page.permission:employee,edit')->name('employment-types.store');
        Route::patch('/employment-types/{employmentType}', [NewEmploymentTypeController::class, 'update'])
            ->middleware('page.permission:employee,edit')->name('employment-types.update');
        Route::delete('/employment-types/{employmentType}', [NewEmploymentTypeController::class, 'destroy'])
            ->middleware('page.permission:employee,edit')->name('employment-types.destroy');
        Route::post('/employment-types/{employmentType}/restore', [NewEmploymentTypeController::class, 'restore'])
            ->middleware('page.permission:employee,edit')->name('employment-types.restore');

        Route::get('/employees', [NewEmployeeDirectoryController::class, 'index'])
            ->middleware('page.permission:employee,view')->name('employees.index');
        Route::patch('/employees/{employee}', [NewEmployeeDirectoryController::class, 'update'])
            ->middleware('page.permission:employee,edit')->name('employees.update');
        Route::get('/employees/export', [NewEmployeeDirectoryController::class, 'export'])
            ->middleware('page.permission:employee,view')->name('employees.export');

        Route::get('/leave', [NewLeaveManagementController::class, 'index'])
            ->middleware('page.permission:leave,view')->name('leave.index');
        Route::post('/leave', [NewLeaveManagementController::class, 'store'])
            ->middleware('page.permission:leave,edit')->name('leave.store');
        Route::post('/leave/{leave}/approve', [NewLeaveManagementController::class, 'approve'])
            ->middleware('page.permission:leave,edit')->name('leave.approve');
        Route::post('/leave/{leave}/reject', [NewLeaveManagementController::class, 'reject'])
            ->middleware('page.permission:leave,edit')->name('leave.reject');

        Route::get('/attendance', [NewAttendanceController::class, 'index'])
            ->middleware('page.permission:attendance,view')->name('attendance.index');
        Route::get('/attendance/export', [NewAttendanceController::class, 'export'])
            ->middleware('page.permission:attendance,view')->name('attendance.export');

        Route::get('/training', [NewTrainingController::class, 'index'])
            ->middleware('page.permission:training,view')->name('training.index');
        Route::post('/training', [NewTrainingController::class, 'store'])
            ->middleware('page.permission:training,edit')->name('training.store');
        Route::put('/training/{training}', [NewTrainingController::class, 'update'])
            ->middleware('page.permission:training,edit')->name('training.update');
        Route::delete('/training/{training}', [NewTrainingController::class, 'destroy'])
            ->middleware('page.permission:training,edit')->name('training.destroy');
        Route::post('/training/{training}/enroll', [NewTrainingController::class, 'enroll'])
            ->middleware('page.permission:training,edit')->name('training.enroll');
        Route::patch('/training/{training}/enrollments/{enrollment}', [NewTrainingController::class, 'enrollmentStatus'])
            ->middleware('page.permission:training,edit')->name('training.enrollments.status');
    });

    Route::prefix('recruitment')->name('recruitment.')->group(function () {
        Route::get('/job-postings', [NewJobPostingController::class, 'index'])
            ->middleware('page.permission:application,view')->name('job-postings.index');
        Route::post('/job-postings', [NewJobPostingController::class, 'store'])
            ->middleware('page.permission:application,edit')->name('job-postings.store');
        Route::put('/job-postings/{jobPosting}', [NewJobPostingController::class, 'update'])
            ->middleware('page.permission:application,edit')->name('job-postings.update');
        Route::delete('/job-postings/{jobPosting}', [NewJobPostingController::class, 'destroy'])
            ->middleware('page.permission:application,edit')->name('job-postings.destroy');
        Route::post('/job-postings/{jobPosting}/publish', [NewJobPostingController::class, 'publish'])
            ->middleware('page.permission:application,edit')->name('job-postings.publish');
        Route::post('/job-postings/{jobPosting}/close', [NewJobPostingController::class, 'close'])
            ->middleware('page.permission:application,edit')->name('job-postings.close');
        Route::post('/job-postings/{jobPosting}/reopen', [NewJobPostingController::class, 'reopen'])
            ->middleware('page.permission:application,edit')->name('job-postings.reopen');
        Route::post('/job-postings/{jobPosting}/duplicate', [NewJobPostingController::class, 'duplicate'])
            ->middleware('page.permission:application,edit')->name('job-postings.duplicate');
        Route::post('/job-postings/{jobPosting}/archive', [NewJobPostingController::class, 'archive'])
            ->middleware('page.permission:application,edit')->name('job-postings.archive');
        Route::post('/job-postings/{jobPosting}/reactivate', [NewJobPostingController::class, 'reactivate'])
            ->middleware('page.permission:application,edit')->name('job-postings.reactivate');
        Route::get('/job-postings/export', [NewJobPostingController::class, 'export'])
            ->middleware('page.permission:application,view')->name('job-postings.export');

        Route::get('/applications', [NewApplicationController::class, 'index'])
            ->middleware('page.permission:application,view')->name('applications.index');
        Route::get('/applications/export', [NewApplicationController::class, 'export'])
            ->middleware('page.permission:application,view')->name('applications.export');

        Route::get('/interviews', [NewInterviewController::class, 'index'])
            ->middleware('page.permission:interview,view')->name('interviews.index');
        Route::get('/interviews/list', [NewInterviewController::class, 'list'])
            ->middleware('page.permission:interview,view')->name('interviews.list');
    });

    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/status', [NewOnboardingStatusController::class, 'index'])
            ->middleware('page.permission:onboarding,view')->name('status.index');
        Route::get('/templates', [NewOnboardingTemplateController::class, 'index'])
            ->middleware('page.permission:onboarding,view')->name('templates.index');
        Route::post('/templates', [NewOnboardingTemplateController::class, 'store'])
            ->middleware('page.permission:onboarding,edit')->name('templates.store');
        Route::get('/templates/{template}', [NewOnboardingTemplateController::class, 'show'])
            ->middleware('page.permission:onboarding,view')->name('templates.show');
        Route::put('/templates/{template}', [NewOnboardingTemplateController::class, 'update'])
            ->middleware('page.permission:onboarding,edit')->name('templates.update');
        Route::post('/templates/{template}/duplicate', [NewOnboardingTemplateController::class, 'duplicate'])
            ->middleware('page.permission:onboarding,edit')->name('templates.duplicate');
        Route::post('/templates/{template}/set-status', [NewOnboardingTemplateController::class, 'setStatus'])
            ->middleware('page.permission:onboarding,edit')->name('templates.set-status');
        Route::delete('/templates/{template}', [NewOnboardingTemplateController::class, 'destroy'])
            ->middleware('page.permission:onboarding,edit')->name('templates.destroy');
        Route::post('/templates/{template}/items', [NewOnboardingTemplateController::class, 'itemStore'])
            ->middleware('page.permission:onboarding,edit')->name('templates.items.store');
        Route::put('/templates/{template}/items/{item}', [NewOnboardingTemplateController::class, 'itemUpdate'])
            ->middleware('page.permission:onboarding,edit')->name('templates.items.update');
        Route::delete('/templates/{template}/items/{item}', [NewOnboardingTemplateController::class, 'itemDestroy'])
            ->middleware('page.permission:onboarding,edit')->name('templates.items.destroy');
        Route::patch('/templates/{template}/reorder', [NewOnboardingTemplateController::class, 'reorder'])
            ->middleware('page.permission:onboarding,edit')->name('templates.reorder');
        Route::post('/templates/{template}/activities', [NewOnboardingTemplateController::class, 'activityStore'])
            ->middleware('page.permission:onboarding,edit')->name('templates.activities.store');
        Route::put('/templates/{template}/activities/{activity}', [NewOnboardingTemplateController::class, 'activityUpdate'])
            ->middleware('page.permission:onboarding,edit')->name('templates.activities.update');
        Route::delete('/templates/{template}/activities/{activity}', [NewOnboardingTemplateController::class, 'activityDestroy'])
            ->middleware('page.permission:onboarding,edit')->name('templates.activities.destroy');
        Route::patch('/templates/{template}/activities-reorder', [NewOnboardingTemplateController::class, 'activityReorder'])
            ->middleware('page.permission:onboarding,edit')->name('templates.activities.reorder');
    });

    // Onboarding instance routes (HRM_NEW OnboardingDetail.vue contract)
    Route::get('/onboarding/status/{onboarding}', [NewOnboardingStatusController::class, 'show'])
        ->middleware('page.permission:onboarding,view')->name('onboarding.show');
    Route::patch('/onboarding/{onboarding}', [NewOnboardingStatusController::class, 'update'])
        ->middleware('page.permission:onboarding,edit')->name('onboarding.update');
    Route::patch('/onboarding/{onboarding}/items/{item}/status', [NewOnboardingStatusController::class, 'itemStatus'])
        ->middleware('page.permission:onboarding,edit')->name('onboarding.items.status');
    Route::post('/onboarding/{onboarding}/items/{item}/review', [NewOnboardingStatusController::class, 'itemReview'])
        ->middleware('page.permission:onboarding,edit')->name('onboarding.items.review');
    Route::post('/onboarding/{onboarding}/notes', [NewOnboardingStatusController::class, 'noteStore'])
        ->middleware('page.permission:onboarding,edit')->name('onboarding.notes.store');
    Route::delete('/onboarding/{onboarding}/notes/{note}', [NewOnboardingStatusController::class, 'noteDestroy'])
        ->middleware('page.permission:onboarding,edit')->name('onboarding.notes.destroy');
    Route::post('/onboarding/{onboarding}/activities', [NewOnboardingStatusController::class, 'activityStore'])
        ->middleware('page.permission:onboarding,edit')->name('onboarding.activities.store');
    Route::patch('/onboarding/{onboarding}/activities/{activity}', [NewOnboardingStatusController::class, 'activityUpdate'])
        ->middleware('page.permission:onboarding,edit')->name('onboarding.activities.update');
    Route::delete('/onboarding/{onboarding}/activities/{activity}', [NewOnboardingStatusController::class, 'activityDestroy'])
        ->middleware('page.permission:onboarding,edit')->name('onboarding.activities.destroy');
    Route::post('/onboarding/{onboarding}/complete', [NewOnboardingStatusController::class, 'complete'])
        ->middleware('page.permission:onboarding,edit')->name('onboarding.complete');
    Route::post('/onboarding/{onboarding}/create-employee', [NewOnboardingStatusController::class, 'createEmployee'])
        ->middleware('page.permission:onboarding,edit')->name('onboarding.create-employee');
});

// ==================================================================
// HRM_NEW raw-URL aliases (Interviews.vue / Applications.vue use hard
// /hrm/... paths via axios instead of route()). Same controllers,
// same permission mapping. Keeps new UI functional without rewrite.
// ==================================================================
Route::prefix('hrm')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/recruitment/applications/{application}/profile', [NewApplicationController::class, 'profile'])
        ->middleware('page.permission:application,view');
    Route::post('/recruitment/applications/{application}/start-screening', [NewApplicationController::class, 'startScreening'])
        ->middleware('page.permission:application,edit');
    Route::post('/recruitment/applications/{application}/screening', [NewApplicationController::class, 'screening'])
        ->middleware('page.permission:application,edit');
    Route::post('/recruitment/applications/{application}/reject', [NewApplicationController::class, 'reject'])
        ->middleware('page.permission:application,edit');
    Route::post('/recruitment/interviews', [NewInterviewController::class, 'store'])
        ->middleware('page.permission:interview,edit');
    Route::post('/recruitment/interviews/{interview}/reschedule', [NewInterviewController::class, 'reschedule'])
        ->middleware('page.permission:interview,edit');
    Route::post('/recruitment/interviews/{interview}/cancel', [NewInterviewController::class, 'cancel'])
        ->middleware('page.permission:interview,edit');
    Route::post('/recruitment/interviews/{interview}/no-show', [NewInterviewController::class, 'noShow'])
        ->middleware('page.permission:interview,edit');
    Route::post('/recruitment/interviews/{interview}/complete', [NewInterviewController::class, 'complete'])
        ->middleware('page.permission:interview,edit');
    Route::post('/recruitment/interviews/{interview}/advance-to-onboarding', [NewInterviewController::class, 'advanceToOnboarding'])
        ->middleware('page.permission:interview,edit');
    Route::post('/recruitment/interviews/{interview}/select', [NewInterviewController::class, 'select'])
        ->middleware('page.permission:interview,edit');
    Route::get('/recruitment/interviewers', [NewInterviewController::class, 'interviewers'])
        ->middleware('page.permission:interview,view');
    Route::get('/recruitment/interviews/list', [NewInterviewController::class, 'list'])
        ->middleware('page.permission:interview,view');
    Route::get('/recruitment/interviews', [NewInterviewController::class, 'fetch'])
        ->middleware('page.permission:interview,view');

    // Temp public application form (HRM_NEW Temp_applicationForm.vue)
    Route::get('/temp/application', [HrmApplicantController::class, 'tempForm'])->name('hrm.temp.application.index');
    Route::post('/temp/application', [HrmApplicantController::class, 'store'])->name('hrm.temp.application.submit');
});