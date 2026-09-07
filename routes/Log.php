<?php

use App\Http\Controllers\logistics\ConductorController;
use App\Http\Controllers\logistics\DispatchController;
use App\Http\Controllers\logistics\DriverController;
use App\Http\Controllers\logistics\DriversController;
use App\Http\Controllers\logistics\FleetController;

use App\Http\Controllers\logistics\LoadController;
use App\Http\Controllers\logistics\LogAccessController;
use App\Http\Controllers\logistics\LogisticsDashboardController;
use App\Http\Controllers\logistics\ProofController;
use App\Http\Controllers\logistics\ReportController;
use App\Http\Controllers\logistics\RoutesController;
use App\Http\Controllers\logistics\TrackingController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LOGISTICS DASHBOARD & ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'module.access:LOG'])->prefix('dashboard/logistics')->name('logistics.')->group(function () {
    // Main Dashboard
    Route::get('/', [LogisticsDashboardController::class, 'index'])->name('dashboard');

    // Fleet Management
    Route::get('/fleet', [FleetController::class, 'index'])->name('fleet.index');
    Route::post('/fleet', [FleetController::class, 'store'])->name('fleet.store');
    Route::patch('/fleet/{truck}', [FleetController::class, 'update'])->name('fleet.update');
    Route::delete('/fleet/{truck}', [FleetController::class, 'destroy'])->name('fleet.destroy');

    // Drivers & Conductors
    Route::get('/drivers', [DriversController::class, 'index'])->name('drivers.index');
    Route::post('/drivers', [DriversController::class, 'store'])->name('drivers.store');
    Route::get('/drivers/{driver}', [DriversController::class, 'show'])->name('drivers.show');

    // Loading & Dispatch
    Route::get('/load', [LoadController::class, 'index'])->name('load.index');
    Route::post('/load/dispatch', [LoadController::class, 'passToDispatch'])->name('load.pass');

    Route::get('/dispatch', [DispatchController::class, 'index'])->name('dispatch.index');
    Route::post('/dispatch/{delivery}', [DispatchController::class, 'assignAndDispatch'])->name('dispatch.assign');

    // Portals (Driver & Conductor)
    Route::get('/driver-portal', [DriverController::class, 'index'])->name('driver.portal');
    Route::post('/driver-portal/{delivery}/transit', [DriverController::class, 'markInTransit'])->name('driver.transit');
    Route::post('/driver-portal/{delivery}/proof', [DriverController::class, 'uploadProof'])->name('driver.proof');

    Route::get('/conductor-portal', [ConductorController::class, 'index'])->name('conductor.portal');
    Route::post('/conductor-portal/{delivery}/report', [ConductorController::class, 'storeReport'])->name('conductor.report');

    // Reports & Access
    Route::get('/proof-of-delivery', [ProofController::class, 'index'])->name('proof.index');
    Route::get('/conductor-reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/access-control', [LogAccessController::class, 'index'])->name('access.index');
    Route::post('/access-control', [LogAccessController::class, 'update'])->name('access.update');

    // ── Delivery Routes (map-based, client-linked) ────────────────────────
    Route::get('/routes', [RoutesController::class, 'index'])->name('routes');
    Route::post('/routes', [RoutesController::class, 'store'])->name('routes.store');
    Route::put('/routes/{route}', [RoutesController::class, 'update'])->name('routes.update');
    Route::delete('/routes/{route}', [RoutesController::class, 'destroy'])->name('routes.destroy');



    // Tracking & Live Map
    Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking');

    // Driver profile update
    Route::post('/driver/profile', [DriverController::class, 'updateProfile'])->name('driver.profile.update');

    // Test delivery completion
    Route::post('/driver/test-delivered/{delivery}', [DriverController::class, 'testDelivered'])->name('driver.test-delivered');
});