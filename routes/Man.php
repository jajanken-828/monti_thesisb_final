<?php

use App\Http\Controllers\hrm\AccessController;
use App\Http\Controllers\man\ManAccessController;
use App\Http\Controllers\man\Manager\ManufacturingManagerController;
use App\Http\Controllers\man\ManDashboardController;
use App\Http\Controllers\man\ManufacturingInventoryController;
use App\Http\Controllers\man\Staff\CheckerQualityController;
use App\Http\Controllers\man\Staff\DyeingColorController;
use App\Http\Controllers\man\Staff\DyeingFabricSoftenerController;
use App\Http\Controllers\man\Staff\DyeingIroningController;
use App\Http\Controllers\man\Staff\DyeingPackagingController;
use App\Http\Controllers\man\Staff\DyeingSqueezerController;
use App\Http\Controllers\man\Staff\KnittingYarnController;
use App\Http\Controllers\man\Staff\MaintenanceCheckerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Manufacturing Plant (MAN) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/man')->name('man.')->middleware(['auth', 'verified', 'module.access:MAN'])->group(function () {
    
    // ---- These routes are now inside the manager middleware group below ----
    // Route::get('/interview', [InterviewController::class, 'index'])->name('interview.index');
    // Route::get('/trainee', [TraineeController::class, 'index'])->name('trainee.index');

    // Access control overview — restricted to manufacturing managers
    // (previously had no inner authorization check beyond module access).
    Route::get('/access', [AccessController::class, 'index'])
        ->middleware('can.access.man.manager')
        ->name('access.index');

    // ──────────────────────────────────────────────────────────────────────────
    // Manager-level access (includes MAN managers, supervisors, and elevated roles)
    // ──────────────────────────────────────────────────────────────────────────
    Route::middleware(['can.access.man.manager'])->group(function () {
        
        

        // Manager access control (supervisor management)
        Route::get('/access/manage', [ManAccessController::class, 'index'])->name('access.manage');
        Route::post('/access/assign-supervisor', [ManAccessController::class, 'assignSupervisor'])->name('access.assign-supervisor');

        // Manufacturing Inventory (Production Inventory)
        Route::get('/inventory', [ManufacturingInventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/{item}/container', [ManufacturingInventoryController::class, 'updateContainer'])->name('inventory.container.update');
        Route::post('/inventory/{item}/consume', [ManufacturingInventoryController::class, 'consume'])->name('inventory.consume');

        // Manufacturing Manager Dashboard & Functions
        Route::get('/', [ManufacturingManagerController::class, 'index'])->name('manager.dashboard');
        Route::get('/production', [ManufacturingManagerController::class, 'production'])->name('manager.production');
        Route::get('/rejected', [ManufacturingManagerController::class, 'rejected'])->name('manager.rejected');
        Route::post('/rejected/{fabric}/recolor', [ManufacturingManagerController::class, 'recolorFabric'])->name('manager.rejected.recolor');
        Route::post('/rejected/{fabric}/total-reject', [ManufacturingManagerController::class, 'rejectFabricTotally'])->name('manager.rejected.total-reject');
        Route::post('/orders/{id}/forward-to-checker', [ManufacturingManagerController::class, 'forwardToChecker'])->name('manager.forward-to-checker');
        Route::post('/staff/{id}/update-role', [ManufacturingManagerController::class, 'updateStaffRole'])->name('manager.update-staff-role');
        Route::post('/packages/{id}/send-to-logistics', [ManufacturingManagerController::class, 'sendToLogistics'])->name('manager.send-to-logistics');

        // Quality Checker (Manager level)
        Route::prefix('checker-quality')->name('manager.checker-quality.')->controller(CheckerQualityController::class)
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/production', 'production')->name('production');
                Route::post('/order/{id}/check-inventory', 'checkInventory')->name('check-inventory');
                Route::post('/order/{id}/start-production', 'startProduction')->name('start-production');
                Route::post('/fabric/{id}/pass', 'passFabric')->name('pass-fabric');
                Route::post('/dye/{id}/pass', 'passDye')->name('pass-dye');
                Route::post('/softener/{id}/pass', 'passSoftener')->name('pass-softener');
                Route::post('/squeezer/{id}/pass', 'passSqueezer')->name('pass-squeezer');
                Route::post('/iron/{id}/pass', 'passIron')->name('pass-iron');
                Route::post('/package/{id}/assign-to-order', 'assignPackageToOrder')->name('assign-package');
            });
    });

    // ──────────────────────────────────────────────────────────────────────────
    // Staff-specific routes (only for users with matching manufacturing_role)
    // ──────────────────────────────────────────────────────────────────────────
    Route::middleware(['module.access:MAN'])->group(function () {
        
        // Staff dashboard entry point (redirects to role-specific dashboard)
        Route::get('/staff', [ManDashboardController::class, 'staffDashboard'])
            ->middleware(['position:staff'])
            ->name('employee.dashboard');

        // Knitting Yarn
        Route::prefix('knitting-yarn')->name('staff.knitting-yarn.')->controller(KnittingYarnController::class)
            ->middleware('man.role:knitting_yarn')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/knitting-yarn', 'knittingYarn')->name('page');
                Route::post('/fabric', 'storeFabric')->name('store-fabric');
                Route::get('/reports', 'reports')->name('reports');
                Route::post('/machine-report', 'reportMachine')->name('report-machine');
                Route::post('mark-done/{id}', 'markDone')->name('mark-done');
                Route::post('unmark-done/{id}', 'unmarkDone')->name('unmark-done');
                Route::get('/history', 'history')->name('history');
            });

        // Dyeing Color
        Route::prefix('dyeing-color')->name('staff.dyeing-color.')->controller(DyeingColorController::class)
            ->middleware('man.role:dyeing_color')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/dyeing-color', 'dyeingColor')->name('page');
                Route::post('/dye', 'storeDye')->name('store-dye');
                Route::get('/reports', 'reports')->name('reports');
                Route::post('/machine-report', 'reportMachine')->name('report-machine');
                Route::get('/history', 'history')->name('history');
            });

        // Dyeing Fabric Softener
        Route::prefix('dyeing-fabric-softener')->name('staff.dyeing-fabric-softener.')->controller(DyeingFabricSoftenerController::class)
            ->middleware('man.role:dyeing_fabric_softener')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/dyeing-fabric-softener', 'dyeingFabricSoftener')->name('page');
                Route::post('/soften', 'storeSoftener')->name('store-soften');
                Route::get('/reports', 'reports')->name('reports');
                Route::post('/machine-report', 'reportMachine')->name('report-machine');
                Route::get('/history', 'history')->name('history');
            });

        // Dyeing Squeezer
        Route::prefix('dyeing-squeezer')->name('staff.dyeing-squeezer.')->controller(DyeingSqueezerController::class)
            ->middleware('man.role:dyeing_squeezer')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/dyeing-squeezer', 'dyeingSqueezer')->name('page');
                Route::post('/squeeze', 'storeSqueezer')->name('store-squeeze');
                Route::get('/reports', 'reports')->name('reports');
                Route::post('/machine-report', 'reportMachine')->name('report-machine');
                Route::get('/history', 'history')->name('history');
            });

        // Dyeing Ironing
        Route::prefix('dyeing-ironing')->name('staff.dyeing-ironing.')->controller(DyeingIroningController::class)
            ->middleware('man.role:dyeing_ironing')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/dyeing-ironing', 'dyeingIroning')->name('page');
                Route::post('/iron', 'storeIron')->name('store-iron');
                Route::get('/reports', 'reports')->name('reports');
                Route::post('/machine-report', 'reportMachine')->name('report-machine');
                Route::get('/history', 'history')->name('history');
            });

        // Dyeing Packaging (ironing flows directly here; forming stage removed)
        Route::prefix('dyeing-packaging')->name('staff.dyeing-packaging.')->controller(DyeingPackagingController::class)
            ->middleware('man.role:dyeing_packaging')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/packaging', 'packaging')->name('page');
                Route::post('/package', 'storePackage')->name('store-package');
                Route::get('/reports', 'reports')->name('reports');
                Route::post('/machine-report', 'reportMachine')->name('report-machine');
                Route::get('/history', 'history')->name('history');
            });

        // Maintenance Checker
        Route::prefix('maintenance-checker')->name('staff.maintenance-checker.')->controller(MaintenanceCheckerController::class)
            ->middleware('man.role:maintenance_checker')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/maintenance', 'maintenance')->name('page');
                Route::post('/machine', 'storeMachine')->name('store-machine');
                Route::patch('/machine/{id}', 'updateMachineStatus')->name('update-machine');
                Route::get('/reports', 'reports')->name('reports');
                Route::patch('/report/{id}', 'resolveReport')->name('resolve-report');
            });

        // Quality Checker (Staff level) — kept for role‑based access
        Route::prefix('checker-quality')->name('staff.checker-quality.')->controller(CheckerQualityController::class)
            ->middleware(['man.role:checker_quality'])
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/production', 'production')->name('production');
                Route::post('/order/{id}/check-inventory', 'checkInventory')->name('check-inventory');
                Route::post('/order/{id}/start-production', 'startProduction')->name('start-production');
                Route::post('/fabric/{id}/pass', 'passFabric')->name('pass-fabric');
                Route::post('/dye/{id}/pass', 'passDye')->name('pass-dye');
                Route::post('/softener/{id}/pass', 'passSoftener')->name('pass-softener');
                Route::post('/squeezer/{id}/pass', 'passSqueezer')->name('pass-squeezer');
                Route::post('/iron/{id}/pass', 'passIron')->name('pass-iron');
                Route::post('/package/{id}/assign-to-order', 'assignPackageToOrder')->name('assign-package');
                Route::post('/package/{id}/push-to-logistics', 'pushToLogistics')->name('push-to-logistics');
            });
    });
});