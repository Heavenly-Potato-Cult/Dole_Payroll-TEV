<?php

use Illuminate\Support\Facades\Route;
use Modules\Allowances\Http\Controllers\AllowanceBatchController;
use Modules\Allowances\Http\Controllers\AllowanceTypeController;
use Modules\Allowances\Http\Controllers\EmployeeAllowanceController;

Route::middleware(['auth', 'role:payroll_officer|hrmo|super_admin'])->group(function () {

    // ── Allowance batches (main Allowances section) ──────────────
    Route::prefix('allowances')->name('allowances.')->group(function () {
        Route::get('/', [AllowanceBatchController::class, 'index'])->name('index');

        Route::get('/batches/create', [AllowanceBatchController::class, 'create'])->name('batches.create');
        Route::post('/batches', [AllowanceBatchController::class, 'store'])->name('batches.store');
        Route::get('/batches/{batch}', [AllowanceBatchController::class, 'show'])->name('batches.show');
        Route::get('/batches/{batch}/edit', [AllowanceBatchController::class, 'edit'])->name('batches.edit');
        Route::put('/batches/{batch}', [AllowanceBatchController::class, 'update'])->name('batches.update');
        Route::delete('/batches/{batch}', [AllowanceBatchController::class, 'destroy'])->name('batches.destroy');
        Route::post('/batches/{batch}/advance', [AllowanceBatchController::class, 'advance'])->name('batches.advance');

        // ── Allowance types CMS ──────────────────────────────────
        Route::get('/types', [AllowanceTypeController::class, 'index'])->name('types.index');
        Route::get('/types/create', [AllowanceTypeController::class, 'create'])->name('types.create');
        Route::post('/types', [AllowanceTypeController::class, 'store'])->name('types.store');
        Route::get('/types/{type}/edit', [AllowanceTypeController::class, 'edit'])->name('types.edit');
        Route::put('/types/{type}', [AllowanceTypeController::class, 'update'])->name('types.update');
        Route::patch('/types/{type}/toggle', [AllowanceTypeController::class, 'toggle'])->name('types.toggle');
        Route::delete('/types/{type}', [AllowanceTypeController::class, 'destroy'])->name('types.destroy');
    });

    // ── Per-employee standing allowances ───────────────────────────
    Route::get('/employees/{employee}/allowances', [EmployeeAllowanceController::class, 'index'])
        ->name('employees.allowances');
    Route::post('/employees/{employee}/allowances', [EmployeeAllowanceController::class, 'update'])
        ->name('employees.allowances.update');
});
