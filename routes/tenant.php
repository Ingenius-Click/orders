<?php

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here is where you can register tenant-specific routes for your module.
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Ingenius\Orders\Http\Controllers\InvoicesController;
use Ingenius\Orders\Http\Controllers\OrdersController;
use Ingenius\Orders\Http\Controllers\OrderStatusesController;

Route::middleware([
    'api',
])->prefix('api')->group(function () {
    // Your tenant routes here

    Route::prefix('orders')->group(function () {

        Route::group(['middleware' => 'tenant.user'], function () {
            Route::get('/', [OrdersController::class, 'index'])->middleware('tenant.has.feature:list-orders');
            Route::delete('/{id}', [OrdersController::class, 'destroy'])->middleware('tenant.has.feature:update-order');
            Route::put('/{id}/change-status', [OrdersController::class, 'changeStatus'])->middleware('tenant.has.feature:update-order');
        });

        Route::post('/', [OrdersController::class, 'store'])->middleware('tenant.has.feature:create-order');
        Route::get('/{id}', [OrdersController::class, 'show'])->middleware('tenant.has.feature:view-order');
    });

    Route::prefix('order-statuses')->middleware('tenant.user')->group(function () {
        Route::get('/', [OrderStatusesController::class, 'index'])->middleware('tenant.has.feature:list-orders');
        Route::post('/transitions', [OrderStatusesController::class, 'storeTransitions'])->middleware('tenant.has.feature:update-order');
        Route::delete('/transitions/{id}', [OrderStatusesController::class, 'deleteTransition'])->middleware('tenant.has.feature:update-order');
        Route::post('/transitions/cleanup', [OrderStatusesController::class, 'cleanupOrphanedTransitions'])->middleware('tenant.has.feature:update-order');
    });

    Route::prefix('invoices')->middleware('tenant.user')->group(function () {
        Route::get('/{invoice}', [InvoicesController::class, 'show'])->middleware('tenant.has.feature:view-invoice');
    });
});

// Route::get('tenant-example', function () {
//     return 'Hello from tenant-specific route! Current tenant: ' . tenant('id');
// });