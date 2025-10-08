<?php

use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\GrnController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\StockMonitorController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\UserDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:admin_gudang'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', AdminDashboardController::class)->name('dashboard');

        Route::resource('locations', LocationController::class)->except('show');
        Route::resource('suppliers', SupplierController::class)->except('show');
        Route::resource('items', ItemController::class)->except('show');
        Route::resource('purchase-orders', PurchaseOrderController::class)->except('show');
        Route::resource('drivers', DriverController::class)->except('show');
        Route::resource('vehicles', VehicleController::class)->except('show');
        Route::resource('shipments', ShipmentController::class);

        Route::post('shipments/{shipment}/dispatch', [ShipmentController::class, 'dispatch'])
            ->name('shipments.dispatch');
        Route::post('shipments/{shipment}/deliver', [ShipmentController::class, 'deliver'])
            ->name('shipments.deliver');

        Route::get('stock-monitor', StockMonitorController::class)->name('stock-monitor');

        Route::get('grn/create', [GrnController::class, 'create'])->name('grn.create');
        Route::post('grn', [GrnController::class, 'store'])->name('grn.store');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', UserDashboardController::class)->name('user.dashboard');
});
