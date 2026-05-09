<?php

use Illuminate\Support\Facades\Route;
use Modules\Payroll\Http\Controllers\DashboardController;
use Modules\Payroll\Http\Controllers\PayrollController;

/*
|--------------------------------------------------------------------------
| Payroll Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    // ── Dashboard ────────────────────────────────────────────────
    // Exclude cashiers from dashboard access
    Route::middleware(['role:' . implode('|', array_diff(\App\SharedKernel\Services\RoleService::getRoleGroup('payroll'), ['cashier']))])
        ->get('/dashboard', [DashboardController::class, 'index'])
        ->name('payroll.dashboard');

    // ── My Payslip (Employee self-service) ────────────────────────
    // Accessible to any authenticated user including HRIS-redirected
    // employees who have no direct Payroll account.
    Route::get('/my-payslip', [PayrollController::class, 'myPayslip'])
        ->name('my-payslip');

    // Single-employee payslip PDF — employees can only view their own.
    // Route model binding resolves both {payroll} → PayrollBatch
    // and {entry} → PayrollEntry automatically.
    Route::get('/payroll/{payroll}/my-payslip/{entry}',
               [PayrollController::class, 'viewMyPayslip'])
        ->name('payroll.payslip');

    // ── Officer / Staff access — full payroll management ──────────
    Route::middleware(['role:' . implode('|', \App\SharedKernel\Services\RoleService::getRoleGroup('payroll'))])
        ->prefix('payroll')
        ->name('payroll.')
        ->group(function () {

            Route::get('/',        [PayrollController::class, 'index']) ->name('index');
            Route::get('/create',  [PayrollController::class, 'create'])->name('create');
            Route::post('/',       [PayrollController::class, 'store']) ->name('store');
            Route::get('/{payroll}',       [PayrollController::class, 'show'])   ->name('show');
            Route::get('/{payroll}/edit',  [PayrollController::class, 'edit'])   ->name('edit');
            Route::put('/{payroll}',       [PayrollController::class, 'update']) ->name('update');
            Route::delete('/{payroll}',    [PayrollController::class, 'destroy'])->name('destroy');

            // Payroll workflow actions
            Route::post('/{payroll}/compute',         [PayrollController::class, 'compute'])        ->name('compute');
            Route::post('/{payroll}/submit',          [PayrollController::class, 'submit'])         ->name('submit');
            Route::post('/{payroll}/certify',         [PayrollController::class, 'certify'])        ->name('certify');
            Route::post('/{payroll}/approve',         [PayrollController::class, 'approve'])        ->name('approve');
            Route::post('/{payroll}/lock',            [PayrollController::class, 'lock'])           ->name('lock');
            Route::get( '/{payroll}/verify',          [PayrollController::class, 'verify'])         ->name('verify');
            Route::post('/{payroll}/force-edit',      [PayrollController::class, 'forceEdit'])      ->name('forceEdit');
            Route::post('/{payroll}/pull-attendance', [PayrollController::class, 'pullAttendance']) ->name('pullAttendance');
            Route::post('/{payroll}/pull-and-compute', [PayrollController::class, 'pullAndCompute']) ->name('pullAndCompute');

            // ── Payslip generation (released / locked batches only) ──
            // ?mode=consolidated (default) | per_batch
            // ?entry_id=<PayrollEntry id>  (optional — single employee)
            Route::get('/{payroll}/payslips/generate',
                       [PayrollController::class, 'generatePayslips'])
                ->name('payslips.generate');
        });
});
