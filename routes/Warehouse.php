<?php

use App\Http\Controllers\warehouse\AccessController as WarehouseAccessController;
use App\Http\Controllers\warehouse\MonitorController;
use App\Http\Controllers\warehouse\PackageController;
use App\Http\Controllers\warehouse\ReceivingController;
use App\Http\Controllers\warehouse\RejectController;
use App\Http\Controllers\warehouse\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Warehouse Module
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/warehouse')->name('warehouse.')->middleware(['auth', 'verified', 'module.access:WAR'])->group(function () {
    // General Warehouse Management
    Route::get('/', [WarehouseController::class, 'index'])->name('index');
    Route::post('/', [WarehouseController::class, 'store'])->name('store');
    Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
    Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');

    // Receiving & Logistics
    Route::get('/receiving', [ReceivingController::class, 'index'])->name('receiving');
    Route::post('/receiving', [ReceivingController::class, 'receive'])->name('receiving.store');

    // Warehouse Monitor (The Grid)
    Route::get('/monitor/{warehouse}', [MonitorController::class, 'show'])->name('monitor');
    Route::post('/monitor/layout/{warehouse}', [MonitorController::class, 'updateLayout'])->name('monitor.layout');

    // DRAG & DROP ROUTE (Fixed naming)
    Route::post('/monitor/assign', [MonitorController::class, 'assignToShelf'])->name('monitor.assign');

    Route::post('/monitor/use/{stockItem}', [MonitorController::class, 'useMaterial'])->name('monitor.use');

    // Packaging & Logistics
    Route::get('/packages', [PackageController::class, 'index'])->name('packages');
    Route::post('/packages/{package}/push', [PackageController::class, 'pushToLogistics'])->name('packages.push');

    // Rejects
    Route::get('/rejects', [RejectController::class, 'index'])->name('rejects');

    // Access Control
    Route::get('/access', [WarehouseAccessController::class, 'index'])->name('access');
    Route::post('/access/update', [WarehouseAccessController::class, 'update'])->name('access.update');
});