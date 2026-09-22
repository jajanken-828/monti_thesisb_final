<?php

use App\Http\Controllers\Crm\ActivityController;
use App\Http\Controllers\Crm\ApprovalController;
use App\Http\Controllers\Crm\CampaignController;
use App\Http\Controllers\Crm\CaseController;
use App\Http\Controllers\Crm\ContactController;
use App\Http\Controllers\Crm\CrmDashboardController;
use App\Http\Controllers\Crm\CustomerProfileController;

use App\Http\Controllers\Crm\InvestigationController;
use App\Http\Controllers\Crm\LeadController;
use App\Http\Controllers\Crm\OpportunityController;
use App\Http\Controllers\Crm\QuotationController;
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
    Route::post('/lead/{id}/qualify', [LeadController::class, 'qualify'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.qualify');



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

    // Customer Profiles = Accounts (company 360)
    Route::get('/customerprofile', [CustomerProfileController::class, 'index'])
        ->middleware('page.permission:customer_profiles,view')
        ->name('customerprofile.index');
    Route::get('/customerprofile/{id}', [CustomerProfileController::class, 'show'])
        ->middleware('page.permission:customer_profiles,view')
        ->name('customerprofile.show');

    // Contacts (people at accounts / prospects)
    Route::post('/contacts', [ContactController::class, 'store'])
        ->middleware('page.permission:customer_profiles,edit')
        ->name('contacts.store');
    Route::patch('/contacts/{contact}', [ContactController::class, 'update'])
        ->middleware('page.permission:customer_profiles,edit')
        ->name('contacts.update');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])
        ->middleware('page.permission:customer_profiles,edit')
        ->name('contacts.destroy');
    Route::post('/contacts/{contact}/primary', [ContactController::class, 'makePrimary'])
        ->middleware('page.permission:customer_profiles,edit')
        ->name('contacts.primary');

    // Opportunities (deal pipeline; approvals surface as stage gates)
    Route::get('/opportunities', [OpportunityController::class, 'index'])
        ->middleware('page.permission:opportunities,view')
        ->name('opportunities');
    Route::post('/opportunities', [OpportunityController::class, 'store'])
        ->middleware('page.permission:opportunities,edit')
        ->name('opportunities.store');
    Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show'])
        ->middleware('page.permission:opportunities,view')
        ->name('opportunities.show');
    Route::post('/opportunities/{opportunity}/move', [OpportunityController::class, 'move'])
        ->middleware('page.permission:opportunities,edit')
        ->name('opportunities.move');

    // Activities (unified timeline)
    Route::get('/activities', [ActivityController::class, 'index'])
        ->middleware('page.permission:activities,view')
        ->name('activities');
    Route::post('/activities', [ActivityController::class, 'store'])
        ->middleware('page.permission:activities,edit')
        ->name('activities.store');
    Route::post('/activities/{activity}/done', [ActivityController::class, 'done'])
        ->middleware('page.permission:activities,edit')
        ->name('activities.done');
    Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])
        ->middleware('page.permission:activities,edit')
        ->name('activities.destroy');

    // Cases (complaints & claims)
    Route::get('/cases', [CaseController::class, 'index'])
        ->middleware('page.permission:cases,view')
        ->name('cases');
    Route::post('/cases', [CaseController::class, 'store'])
        ->middleware('page.permission:cases,edit')
        ->name('cases.store');
    Route::post('/cases/{case}/status', [CaseController::class, 'status'])
        ->middleware('page.permission:cases,edit')
        ->name('cases.status');

    // Campaigns (with lead attribution)
    Route::get('/campaigns', [CampaignController::class, 'index'])
        ->middleware('page.permission:campaigns,view')
        ->name('campaigns');
    Route::post('/campaigns', [CampaignController::class, 'store'])
        ->middleware('page.permission:campaigns,edit')
        ->name('campaigns.store');
    Route::post('/campaigns/{campaign}/leads', [CampaignController::class, 'attachLead'])
        ->middleware('page.permission:campaigns,edit')
        ->name('campaigns.attach-lead');
    Route::post('/campaigns/{campaign}/status', [CampaignController::class, 'status'])
        ->middleware('page.permission:campaigns,edit')
        ->name('campaigns.status');

    // Quotations (ECO-issued, read-only in CRM)
    Route::get('/quotations', [QuotationController::class, 'index'])
        ->middleware('page.permission:quotations,view')
        ->name('quotations');

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
    Route::post('/socials/facebook/connect', [SocialsController::class, 'connectFb'])
        ->middleware('page.permission:socials,edit')
        ->name('socials.facebook.connect');
    Route::delete('/socials/facebook', [SocialsController::class, 'disconnectFb'])
        ->middleware('page.permission:socials,edit')
        ->name('socials.facebook.disconnect');
    Route::get('/socials/facebook/comments', [SocialsController::class, 'fbComments'])
        ->middleware('page.permission:socials,view')
        ->name('socials.facebook.comments');
    Route::post('/socials/facebook/convert-post', [SocialsController::class, 'convertPost'])
        ->middleware('page.permission:socials,edit')
        ->name('socials.facebook.convert-post');
    Route::post('/socials/facebook/convert-comment', [SocialsController::class, 'convertComment'])
        ->middleware('page.permission:socials,edit')
        ->name('socials.facebook.convert-comment');

    // Partner logos (company logos uploaded at registration / lead creation)
    Route::post('/logo-partner/upload', [CrmLogoPartnerController::class, 'upload'])
        ->middleware('page.permission:leads,edit')
        ->name('logo-partner.upload');
    Route::delete('/logo-partner/{id}', [CrmLogoPartnerController::class, 'destroy'])
        ->middleware('page.permission:leads,edit')
        ->name('logo-partner.destroy');

});