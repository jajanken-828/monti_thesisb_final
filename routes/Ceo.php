<?php

use App\Http\Controllers\ceo\CeoAccessController;
use App\Http\Controllers\ceo\CeoDashboardController;
use App\Http\Controllers\ceo\GeolocationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CEO Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/ceo')->name('ceo.')->middleware(['auth', 'verified', 'role:CEO'])->group(function () {
    Route::get('/', [CeoDashboardController::class, 'index'])->name('dashboard');

    // Access Control
    Route::get('/access', [CeoAccessController::class, 'index'])->name('access');
    Route::get('/access/employee/{id}/personal-info', [CeoAccessController::class, 'getEmployeePersonalInfo'])->name('access.employeePersonalInfo');

    Route::post('/access/update-position', [CeoAccessController::class, 'updatePosition'])->name('access.updatePosition');
    Route::post('/access/update-modules', [CeoAccessController::class, 'updateModules'])->name('access.updateModules');
    Route::post('/access/update-staff-pages', [CeoAccessController::class, 'updateStaffPages'])->name('access.updateStaffPages');
    Route::post('/access/assign-staff-role', [CeoAccessController::class, 'assignStaffRole'])->name('access.assignStaffRole');
    Route::post('/access/update-profile-photo', [CeoAccessController::class, 'updateProfilePhoto'])->name('access.updateProfilePhoto');
    Route::get('/access/client-assignments/{staffId}', [CeoAccessController::class, 'getClientAssignments'])->name('access.clientAssignments');
    Route::post('/access/assign-clients', [CeoAccessController::class, 'updateClientAssignments'])->name('access.updateClientAssignments');

    // Geolocation Page View (GET)
    Route::get('/location', [GeolocationController::class, 'index'])->name('location.index');

    // Geolocation Data Sync (POST)
    Route::post('/user/location/sync', [GeolocationController::class, 'store'])->name('location.store');
});