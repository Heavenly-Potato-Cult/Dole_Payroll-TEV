<?php

use Illuminate\Support\Facades\Route;
use Modules\Payroll\Http\Controllers\DashboardController;
use Modules\Payroll\Http\Controllers\PayrollController;
use Modules\Payroll\Http\Controllers\SpecialPayrollController;
use Modules\Payroll\Http\Controllers\DeductionCategoryController;
use Modules\Payroll\Http\Controllers\DeductionTypeController;

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

    // ── Special Payroll ─────────────────────────────────────────────
    Route::middleware(['role:' . implode('|', \App\SharedKernel\Services\RoleService::getRoleGroup('payroll'))])
        ->prefix('special-payroll')
        ->name('special-payroll.')
        ->group(function () {

            // Newly Hired
            Route::get('/newly-hired', [SpecialPayrollController::class, 'newHireIndex'])
                ->name('newly-hired.index');
            Route::get('/newly-hired/create', [SpecialPayrollController::class, 'newHireCreate'])
                ->name('newly-hired.create');
            Route::post('/newly-hired', [SpecialPayrollController::class, 'newHireStore'])
                ->name('newly-hired.store');
            Route::get('/newly-hired/{id}', [SpecialPayrollController::class, 'newHireShow'])
                ->name('newly-hired.show');
            Route::post('/newly-hired/{id}/approve', [SpecialPayrollController::class, 'newHireApprove'])
                ->name('newly-hired.approve');
            Route::delete('/newly-hired/{id}', [SpecialPayrollController::class, 'newHireDestroy'])
                ->name('newly-hired.destroy');

            // Salary Differential
            Route::get('/differential', [SpecialPayrollController::class, 'differentialIndex'])
                ->name('differential.index');
            Route::get('/differential/create', [SpecialPayrollController::class, 'differentialCreate'])
                ->name('differential.create');
            Route::post('/differential', [SpecialPayrollController::class, 'differentialStore'])
                ->name('differential.store');
            Route::get('/differential/{id}', [SpecialPayrollController::class, 'differentialShow'])
                ->name('differential.show');
            Route::post('/differential/{id}/approve', [SpecialPayrollController::class, 'differentialApprove'])
                ->name('differential.approve');
            Route::delete('/differential/{id}', [SpecialPayrollController::class, 'differentialDestroy'])
                ->name('differential.destroy');

            // NOSI / NOSA
            Route::get('/nosi-nosa', [SpecialPayrollController::class, 'nosiNosaIndex'])
                ->name('nosi-nosa.index');
            Route::get('/nosi-nosa/create', [SpecialPayrollController::class, 'nosiNosaCreate'])
                ->name('nosi-nosa.create');
            Route::post('/nosi-nosa', [SpecialPayrollController::class, 'nosiNosaStore'])
                ->name('nosi-nosa.store');
            Route::get('/nosi-nosa/{id}', [SpecialPayrollController::class, 'nosiNosaShow'])
                ->name('nosi-nosa.show');
            Route::post('/nosi-nosa/{id}/approve', [SpecialPayrollController::class, 'nosiNosaApprove'])
                ->name('nosi-nosa.approve');
            Route::delete('/nosi-nosa/{id}', [SpecialPayrollController::class, 'nosiNosaDestroy'])
                ->name('nosi-nosa.destroy');
        });

    // ── Deduction Types & Categories (Enhancements #1-4) ──────────────────
    Route::middleware(['role:' . implode('|', \App\SharedKernel\Services\RoleService::getRoleGroup('payroll'))])
        ->prefix('deduction-categories')
        ->name('deduction-categories.')
        ->group(function () {
            Route::get('/', [DeductionCategoryController::class, 'index'])->name('index');
            Route::get('/create', [DeductionCategoryController::class, 'create'])->name('create');
            Route::post('/', [DeductionCategoryController::class, 'store'])->name('store');
            Route::get('/{deductionCategory}/edit', [DeductionCategoryController::class, 'edit'])->name('edit');
            Route::put('/{deductionCategory}', [DeductionCategoryController::class, 'update'])->name('update');
            Route::patch('/{deductionCategory}/toggle', [DeductionCategoryController::class, 'toggle'])->name('toggle');
            Route::delete('/{deductionCategory}', [DeductionCategoryController::class, 'destroy'])->name('destroy');
        });

Route::middleware(['role:' . implode('|', \App\SharedKernel\Services\RoleService::getRoleGroup('payroll'))])
    ->prefix('deduction-types')
    ->name('deduction-types.')
    ->group(function () {
        Route::get('/', [DeductionTypeController::class, 'index'])->name('index');
        Route::get('/create', [DeductionTypeController::class, 'create'])->name('create');
        Route::post('/', [DeductionTypeController::class, 'store'])->name('store');
        Route::get('/{deductionType}/edit', [DeductionTypeController::class, 'edit'])->name('edit');
        Route::put('/{deductionType}', [DeductionTypeController::class, 'update'])->name('update');
        Route::patch('/{deductionType}/toggle', [DeductionTypeController::class, 'toggle'])->name('toggle');
        Route::delete('/{deductionType}', [DeductionTypeController::class, 'destroy'])->name('destroy');
    });

});
