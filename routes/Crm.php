<?php

use App\Http\Controllers\Crm\AccessController as CrmAccessController;
use App\Http\Controllers\Crm\ApprovalController;
use App\Http\Controllers\Crm\CrmDashboardController;
use App\Http\Controllers\Crm\CustomerProfileController;

use App\Http\Controllers\Crm\InvestigationController;
use App\Http\Controllers\Crm\LeadController;
use App\Http\Controllers\Crm\SocialsController;
use App\Http\Controllers\Crm\CrmLogoPartnerController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Relationship Management (CRM) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/crm')->name('crm.')->middleware(['auth', 'verified', 'module.access:CRM'])->group(function () {
    // Dashboard
    Route::get('/', [CrmDashboardController::class, 'index'])
        ->middleware('page.permission:dashboard,view')
        ->name('dashboard');

    // Leads
    Route::get('/lead', [LeadController::class, 'index'])
        ->middleware('page.permission:leads,view')
        ->name('lead');
    Route::post('/lead/store', [LeadController::class, 'store'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.store');
    Route::patch('/lead/{id}/status', [LeadController::class, 'updateStatus'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.status');
    Route::post('/lead/convert', [LeadController::class, 'convertToClient'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.convert');
    Route::post('/lead/{id}/note', [LeadController::class, 'addNote'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.add-note');
    Route::post('/lead/{id}/interview', [LeadController::class, 'scheduleInterview'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.schedule-interview');
    Route::post('/lead/{id}/file', [LeadController::class, 'uploadApprovalFile'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.upload-file');
    Route::post('/lead/{id}/accept', [LeadController::class, 'acceptLead'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.accept');
    Route::post('/lead/{id}/reject', [LeadController::class, 'rejectLead'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.reject');



    // Approvals
    Route::get('/approval', [ApprovalController::class, 'index'])
        ->middleware('page.permission:approvals,view')
        ->name('approval.index');
    Route::post('/approval/{id}/note', [ApprovalController::class, 'addNote'])
        ->middleware('page.permission:approvals,edit')
        ->name('approval.note');
    Route::post('/approval/{id}/meeting', [ApprovalController::class, 'scheduleMeeting'])
        ->middleware('page.permission:approvals,edit')
        ->name('approval.meeting');
    Route::patch('/approval/meeting/{meetingId}/status', [ApprovalController::class, 'updateMeetingStatus'])
        ->middleware('page.permission:approvals,edit')
        ->name('approval.meeting.update');
    Route::post('/approval/{id}/accept', [ApprovalController::class, 'accept'])
        ->middleware('page.permission:approvals,edit')
        ->name('approval.accept');
    Route::post('/approval/{id}/reject', [ApprovalController::class, 'reject'])
        ->middleware('page.permission:approvals,edit')
        ->name('approval.reject');

    // Customer Profiles
    Route::get('/customerprofile', [CustomerProfileController::class, 'index'])
        ->middleware('page.permission:customer_profiles,view')
        ->name('customerprofile.index');
    Route::get('/customerprofile/{id}', [CustomerProfileController::class, 'show'])
        ->middleware('page.permission:customer_profiles,view')
        ->name('customerprofile.show');

    // Investigation
    Route::get('/investigation', [InvestigationController::class, 'index'])
        ->middleware('page.permission:investigation,view')
        ->name('investigation.index');
    Route::post('/investigation/assign', [InvestigationController::class, 'assignStaff'])
        ->middleware('page.permission:investigation,edit')
        ->name('investigation.assign');
    Route::post('/investigation/feedback', [InvestigationController::class, 'storeFeedback'])
        ->middleware('page.permission:investigation,edit')
        ->name('investigation.feedback.store');
    Route::patch('/investigation/feedback/{id}/status', [InvestigationController::class, 'updateFeedbackStatus'])
        ->middleware('page.permission:investigation,edit')
        ->name('investigation.feedback.status');

    // Socials
    Route::get('/socials', [SocialsController::class, 'index'])
        ->middleware('page.permission:socials,view')
        ->name('socials.index');

    // Partner logos (company logos uploaded at registration / lead creation)
    Route::post('/logo-partner/upload', [CrmLogoPartnerController::class, 'upload'])
        ->middleware('page.permission:leads,edit')
        ->name('logo-partner.upload');
    Route::delete('/logo-partner/{id}', [CrmLogoPartnerController::class, 'destroy'])
        ->middleware('page.permission:leads,edit')
        ->name('logo-partner.destroy');

    // Access Control (for CRM staff – managed by CEO)
    Route::get('/access', [CrmAccessController::class, 'index'])
        ->middleware('page.permission:access,view')
        ->name('access.index');
    Route::post('/access/update', [CrmAccessController::class, 'update'])
        ->middleware('page.permission:access,edit')
        ->name('access.update');
});