<?php

use App\Domain\Inbound\Http\Controllers\PostGrnController;
use App\Domain\Outbound\Http\Controllers\AllocateController;
use App\Domain\Outbound\Http\Controllers\CompletePickController;
use App\Domain\Outbound\Http\Controllers\DeliverShipmentController;
use App\Domain\Outbound\Http\Controllers\DispatchShipmentController;
use App\Domain\Scan\Http\Controllers\ScanController;
use App\Http\Controllers\Driver\AssignmentsController as DriverAssignmentsController;
use App\Http\Controllers\Driver\DispatchController as DriverDispatchController;
use App\Http\Controllers\Driver\PickController as DriverPickController;
use App\Http\Controllers\Driver\PodController as DriverPodController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::middleware('role:admin_gudang')->group(function (): void {
        Route::post('/admin/inbound/grn', [PostGrnController::class, 'store'])
            ->name('admin.inbound.grn.store');

        Route::post('/admin/outbound/allocate', AllocateController::class)
            ->name('admin.outbound.allocate');

        Route::post('/admin/outbound/pick/complete', CompletePickController::class)
            ->name('admin.outbound.pick.complete');

        Route::post('/admin/outbound/shipment/dispatch', DispatchShipmentController::class)
            ->name('admin.outbound.shipment.dispatch');
    });

    Route::post('/admin/outbound/shipment/deliver', DeliverShipmentController::class)
        ->middleware('role:admin_gudang,driver')
        ->name('admin.outbound.shipment.deliver');

    Route::post('/scan', ScanController::class)
        ->middleware('role:admin_gudang,driver')
        ->name('scan.store');
});

Route::prefix('driver')
    ->middleware(['auth:sanctum', 'throttle:driver-api', 'role:driver'])
    ->group(function (): void {
        Route::get('/assignments', DriverAssignmentsController::class)->name('driver.assignments');
        Route::post('/pick', DriverPickController::class)->name('driver.pick');
        Route::post('/dispatch', DriverDispatchController::class)->name('driver.dispatch');
        Route::post('/pod', DriverPodController::class)->name('driver.pod');
    });
