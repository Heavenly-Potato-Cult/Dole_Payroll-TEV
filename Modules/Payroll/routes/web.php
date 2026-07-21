<?php

use Illuminate\Support\Facades\Route;
use Modules\Payroll\Http\Controllers\DashboardController;
use Modules\Payroll\Http\Controllers\PayrollController;
use Modules\Payroll\Http\Controllers\PayrollReportController;
use Modules\Payroll\Http\Controllers\PayrollEntryController;
use Modules\Payroll\Http\Controllers\SpecialPayrollController;
use Modules\Payroll\Http\Controllers\DeductionTypeController;
use Modules\Payroll\Http\Controllers\DeductionTypeCategoryController;
use Modules\Payroll\Http\Controllers\Allowances\AllowanceAssignmentController;
use Modules\Payroll\Http\Controllers\Allowances\AllowanceTypeController;
use Modules\Payroll\Http\Controllers\Allowances\EmployeeAllowanceController;

/*
|--------------------------------------------------------------------------
| Payroll Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    // ── Dashboard ────────────────────────────────────────────────
    Route::middleware(['role:' . implode('|', array_diff(\App\SharedKernel\Services\RoleService::getRoleGroup('payroll'), ['cashier']))])
        ->get('/dashboard', [DashboardController::class, 'index'])
        ->name('payroll.dashboard');

    // ── My Payslip (Employee self-service) ────────────────────────
    Route::get('/my-payslip', [PayrollController::class, 'myPayslip'])
        ->name('my-payslip');

    Route::get('/payroll/{payroll}/my-payslip/{entry}',
               [PayrollController::class, 'viewMyPayslip'])
        ->name('payroll.payslip');

    // ── Officer / Staff access — full payroll management ──────────
    Route::middleware(['role:' . implode('|', \App\SharedKernel\Services\RoleService::getRoleGroup('payroll'))])
        ->group(function () {

            // ── Deduction Type Categories ─────────────────────────────────
            // Must be registered BEFORE the deduction-types resource so that
            // /deduction-types/categories/... is matched before
            // /deduction-types/{deductionType}/... treats "categories" as a model ID.
            Route::prefix('deduction-types/categories')
                ->name('deduction-type-categories.')
                ->group(function () {
                    Route::get('/',             [DeductionTypeCategoryController::class, 'index']) ->name('index');
                    Route::get('/create',       [DeductionTypeCategoryController::class, 'create'])->name('create');
                    Route::post('/',            [DeductionTypeCategoryController::class, 'store']) ->name('store');
                    Route::get('/{deductionTypeCategory}/edit',
                                               [DeductionTypeCategoryController::class, 'edit'])  ->name('edit');
                    Route::put('/{deductionTypeCategory}',
                                               [DeductionTypeCategoryController::class, 'update'])->name('update');
                    Route::delete('/{deductionTypeCategory}',
                                               [DeductionTypeCategoryController::class, 'destroy'])->name('destroy');
                    // Restore soft-deleted category
                    Route::post('/{id}/restore',       [DeductionTypeCategoryController::class, 'restore'])    ->name('restore');
                    // Permanently hard-delete an already soft-deleted category
                    Route::delete('/{id}/force-delete', [DeductionTypeCategoryController::class, 'forceDelete'])->name('force-delete');
                });

            // ── Deduction Types ───────────────────────────────────────────
            Route::prefix('deduction-types')
                ->name('deduction-types.')
                ->group(function () {
                    Route::get('/',             [DeductionTypeController::class, 'index']) ->name('index');
                    Route::get('/create',       [DeductionTypeController::class, 'create'])->name('create');
                    Route::post('/',            [DeductionTypeController::class, 'store']) ->name('store');
                    Route::get('/{deductionType}/edit',
                                               [DeductionTypeController::class, 'edit'])  ->name('edit');
                    Route::put('/{deductionType}',
                                               [DeductionTypeController::class, 'update'])->name('update');
                    Route::delete('/{deductionType}',
                                               [DeductionTypeController::class, 'destroy'])->name('destroy');
                    Route::patch('/{deductionType}/toggle',
                                               [DeductionTypeController::class, 'toggle'])->name('toggle');
                    Route::post('/reorder',    [DeductionTypeController::class, 'reorder'])->name('reorder');
                });

            // ── Payroll batches ───────────────────────────────────────────
            Route::prefix('payroll')
                ->name('payroll.')
                ->group(function () {
                    Route::get('/',        [PayrollController::class, 'index']) ->name('index');
                    Route::get('/create',  [PayrollController::class, 'create'])->name('create');
                    Route::post('/',       [PayrollController::class, 'store']) ->name('store');
                    Route::get('/{payroll}',       [PayrollController::class, 'show'])   ->name('show');
                    Route::get('/{payroll}/edit',  [PayrollController::class, 'edit'])   ->name('edit');
                    Route::put('/{payroll}',       [PayrollController::class, 'update']) ->name('update');
                    Route::delete('/{payroll}',    [PayrollController::class, 'destroy'])->name('destroy');

                    Route::delete('/{payrollBatch}/entries/{entry}', [PayrollEntryController::class, 'destroy'])
                        ->name('entries.destroy');

                    Route::post('/{payroll}/compute',          [PayrollController::class, 'compute'])        ->name('compute');
                    Route::post('/{payroll}/submit',           [PayrollController::class, 'submit'])         ->name('submit');
                    Route::post('/{payroll}/hr-approve',       [PayrollController::class, 'hrApprove'])      ->name('hrApprove');
                    Route::post('/{payroll}/certify',          [PayrollController::class, 'certify'])        ->name('certify');
                    Route::post('/{payroll}/approve',          [PayrollController::class, 'approve'])        ->name('approve');
                    Route::post('/{payroll}/lock',             [PayrollController::class, 'lock'])           ->name('lock');
                    Route::get( '/{payroll}/verify',           [PayrollController::class, 'verify'])         ->name('verify');
                    Route::post('/{payroll}/force-edit',       [PayrollController::class, 'forceEdit'])      ->name('forceEdit');
                    Route::post('/{payroll}/pull-attendance',  [PayrollController::class, 'pullAttendance']) ->name('pullAttendance');
                    Route::post('/{payroll}/pull-and-compute', [PayrollController::class, 'pullAndCompute']) ->name('pullAndCompute');

                    Route::get('/{payroll}/attendance/{snapshot}/edit',
                               [PayrollController::class, 'editAttendance'])->name('attendance.edit');
                    Route::match(['post', 'patch'], '/{payroll}/attendance/{snapshot}',
                               [PayrollController::class, 'updateAttendance'])->name('attendance.update');

                    Route::post('/{payroll}/payslips/generate',
                               [PayrollController::class, 'generatePayslips'])->name('payslips.generate');
                    Route::get('/payslips/download/{file}',
                               [PayrollController::class, 'downloadPayslip'])->name('payslips.download');
                });
        });

    // ── Special Payroll ─────────────────────────────────────────────
    Route::middleware(['role:' . implode('|', \App\SharedKernel\Services\RoleService::getRoleGroup('payroll'))])
        ->prefix('special-payroll')
        ->name('special-payroll.')
        ->group(function () {

            Route::get('/newly-hired',              [SpecialPayrollController::class, 'newHireIndex'])    ->name('newly-hired.index');
            Route::get('/newly-hired/create',       [SpecialPayrollController::class, 'newHireCreate'])   ->name('newly-hired.create');
            // Must be registered BEFORE /newly-hired/{id} — same reasoning as
            // the deduction-types/categories route ordering elsewhere in this
            // file: otherwise "allowances-preview" gets swallowed as an {id}.
            Route::get('/newly-hired/allowances-preview',
                                                     [SpecialPayrollController::class, 'newHireAllowancesPreview'])
                                                                                                            ->name('newly-hired.allowances-preview');
            Route::post('/newly-hired',             [SpecialPayrollController::class, 'newHireStore'])    ->name('newly-hired.store');
            Route::get('/newly-hired/{id}',         [SpecialPayrollController::class, 'newHireShow'])     ->name('newly-hired.show');
            Route::get('/newly-hired/{id}/payslip', [SpecialPayrollController::class, 'newHirePayslip'])  ->name('newly-hired.payslip');
            Route::post('/newly-hired/{id}/approve',[SpecialPayrollController::class, 'newHireApprove'])  ->name('newly-hired.approve');
            Route::delete('/newly-hired/{id}',      [SpecialPayrollController::class, 'newHireDestroy'])  ->name('newly-hired.destroy');

            Route::get('/differential',              [SpecialPayrollController::class, 'differentialIndex'])  ->name('differential.index');
            Route::get('/differential/create',       [SpecialPayrollController::class, 'differentialCreate']) ->name('differential.create');
            Route::post('/differential',             [SpecialPayrollController::class, 'differentialStore'])  ->name('differential.store');
            Route::get('/differential/{id}',         [SpecialPayrollController::class, 'differentialShow'])   ->name('differential.show');
            Route::get('/differential/{id}/payslip', [SpecialPayrollController::class, 'differentialPayslip'])->name('differential.payslip');
            Route::post('/differential/{id}/approve',[SpecialPayrollController::class, 'differentialApprove'])->name('differential.approve');
            Route::delete('/differential/{id}',      [SpecialPayrollController::class, 'differentialDestroy'])->name('differential.destroy');

            Route::get('/nosi-nosa',              [SpecialPayrollController::class, 'nosiNosaIndex'])  ->name('nosi-nosa.index');
            Route::get('/nosi-nosa/create',       [SpecialPayrollController::class, 'nosiNosaCreate']) ->name('nosi-nosa.create');
            Route::post('/nosi-nosa',             [SpecialPayrollController::class, 'nosiNosaStore'])  ->name('nosi-nosa.store');
            Route::get('/nosi-nosa/{id}',         [SpecialPayrollController::class, 'nosiNosaShow'])   ->name('nosi-nosa.show');
            Route::get('/nosi-nosa/{id}/payslip', [SpecialPayrollController::class, 'nosiNosaPayslip'])->name('nosi-nosa.payslip');
            Route::post('/nosi-nosa/{id}/approve',[SpecialPayrollController::class, 'nosiNosaApprove'])->name('nosi-nosa.approve');
            Route::delete('/nosi-nosa/{id}',      [SpecialPayrollController::class, 'nosiNosaDestroy'])->name('nosi-nosa.destroy');
        });

    // ── Allowances ─────────────────────────────────────────────────────
    Route::middleware(['role:' . implode('|', array_diff(\App\SharedKernel\Services\RoleService::getRoleGroup('payroll'), ['cashier']))])
        ->group(function () {

            // ── Allowance assignments (main Allowances section) ──────────────
            Route::prefix('allowances')->name('payroll.allowances.')->group(function () {
                Route::get('/', [AllowanceAssignmentController::class, 'index'])->name('index');

                Route::get('/assignments/create', [AllowanceAssignmentController::class, 'create'])->name('assignments.create');
                Route::post('/assignments', [AllowanceAssignmentController::class, 'store'])->name('assignments.store');
                Route::get('/assignments/{assignment}', [AllowanceAssignmentController::class, 'show'])->name('assignments.show');
                Route::get('/assignments/{assignment}/edit', [AllowanceAssignmentController::class, 'edit'])->name('assignments.edit');
                Route::put('/assignments/{assignment}', [AllowanceAssignmentController::class, 'update'])->name('assignments.update');
                Route::delete('/assignments/{assignment}', [AllowanceAssignmentController::class, 'destroy'])->name('assignments.destroy');
                Route::post('/assignments/{assignment}/advance', [AllowanceAssignmentController::class, 'advance'])->name('assignments.advance');

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
                ->name('payroll.employees.allowances');
            Route::post('/employees/{employee}/allowances', [EmployeeAllowanceController::class, 'update'])
                ->name('payroll.employees.allowances.update');
        });

    // ── Reports: General Payroll Register ───────────────────────────
    // NOTE: reports.gsis / reports.hdmf / reports.caress-union etc. are not
    // defined in this file, so they must live in a separate reports routes
    // file elsewhere in the module. If so, move this pair there instead —
    // it's placed here only because this is the routes file provided.
    Route::middleware(['role:' . implode('|', \App\SharedKernel\Services\RoleService::getRoleGroup('payroll'))])
        ->prefix('reports')
        ->name('reports.')
        ->group(function () {
            Route::get('/general-payroll', [PayrollReportController::class, 'generalPayrollIndex'])
                ->name('general-payroll');
            Route::get('/general-payroll-download', [PayrollReportController::class, 'generalPayrollDownload'])
                ->name('general-payroll-download');
        });
});
