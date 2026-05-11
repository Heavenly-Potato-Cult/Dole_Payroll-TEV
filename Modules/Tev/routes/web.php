<?php

use Illuminate\Support\Facades\Route;
use Modules\Tev\Http\Controllers\TevController;
use Modules\Tev\Http\Controllers\TevItineraryController;
use Modules\Payroll\Http\Controllers\OfficeOrderController;

/*
|--------------------------------------------------------------------------
| TEV Module Routes
|--------------------------------------------------------------------------
|
| All TEV-related routes have been moved here from routes/web.php
| as part of the modularization effort.
|
*/

// ── TEV (Travel & Expense Voucher) ─────────────────────────────
Route::middleware(['auth'])
    ->prefix('tev')
    ->name('tev.')
    ->group(function () {
        // TEV Dashboard - role-based routing
        Route::get('/', [TevController::class, 'dashboard'])->name('dashboard');
        
        // Explicit routes for each dashboard type
        Route::get('/employee', [TevController::class, 'dashboard'])->name('dashboard.employee');
        Route::get('/officer', [TevController::class, 'officerDashboard'])->name('dashboard.officer');

        // TEV Requests - employees can create/view their own requests
        Route::resource('requests', TevController::class, [
            'names' => [
                'index' => 'requests.index',
                'create' => 'requests.create',
                'store' => 'requests.store',
                'show' => 'requests.show',
                'edit' => 'requests.edit',
                'update' => 'requests.update',
                'destroy' => 'requests.destroy',
            ]
        ]);

        // Employee actions - submit own requests
        Route::post('/requests/{tevRequest}/submit',  [TevController::class, 'submit'])->name('requests.submit');

        // Itinerary management - employees can manage their own itinerary
        Route::post(  '/requests/{tevRequest}/itinerary',
                      [TevItineraryController::class, 'store'])->name('requests.itinerary.store');
        Route::put(   '/requests/{tevRequest}/itinerary/{line}',
                      [TevItineraryController::class, 'update'])->name('requests.itinerary.update');
        Route::delete('/requests/{tevRequest}/itinerary/{line}',
                      [TevItineraryController::class, 'destroy'])->name('requests.itinerary.destroy');

        // TEV Liquidation - employees can file their own liquidation
        Route::post('/requests/{tevRequest}/liquidate',
                    [TevController::class, 'fileLiquidation'])->name('requests.liquidate');

        // ── Officer-only actions ─────────────────────────────────
        Route::middleware(['role:hrmo|accountant|budget_officer|ard|cashier|chief_admin_officer|super_admin'])
            ->group(function () {
                // Office Orders - only index, show, approve, cancel, and pullFromApi are available
                // Create/edit removed since orders are pulled from Employee API
                Route::get('office-orders', [OfficeOrderController::class, 'index'])->name('office-orders.index');
                Route::get('office-orders/{id}', [OfficeOrderController::class, 'show'])->name('office-orders.show')->where('id', '[0-9]+');

                Route::post('/office-orders/{id}/approve',
                            [OfficeOrderController::class, 'approve'])
                    ->name('office-orders.approve')
                    ->where('id', '[0-9]+');

                Route::post('/office-orders/{id}/cancel',
                            [OfficeOrderController::class, 'cancel'])
                    ->name('office-orders.cancel')
                    ->where('id', '[0-9]+');

                Route::post('/office-orders/pull-from-api',
                            [OfficeOrderController::class, 'pullFromApi'])
                    ->name('office-orders.pullFromApi');

                // Approval actions
                Route::post('/requests/{tevRequest}/approve', [TevController::class, 'approve'])->name('requests.approve');
                Route::post('/requests/{tevRequest}/certify', [TevController::class, 'certify'])->name('requests.certify');
                Route::post('/requests/{tevRequest}/reject',  [TevController::class, 'reject'])->name('requests.reject');

                // Liquidation approval
                Route::post('/requests/{tevRequest}/liquidation/approve',
                            [TevController::class, 'approveLiquidation'])->name('requests.liquidation.approve');
            });
    });
