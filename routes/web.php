<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Modules\Payroll\Http\Controllers\DeductionTypeController;
use Modules\Payroll\Http\Controllers\EmployeeController;
use Modules\Payroll\Http\Controllers\EmployeeDeductionController;
use Modules\Payroll\Http\Controllers\EmployeePromotionController;
use Modules\Payroll\Http\Controllers\PayrollEntryController;
use Modules\Payroll\Http\Controllers\SpecialPayrollController;
use Modules\Payroll\Http\Controllers\OfficeOrderController;
use Modules\Payroll\Http\Controllers\PayrollReportController;
use Modules\Payroll\Http\Controllers\DivisionController;
use Modules\Payroll\Http\Controllers\UserController;
use Modules\Payroll\Http\Controllers\SalaryIndexTableController;
use Modules\Payroll\Http\Controllers\SignatoryController;
use Modules\Tev\Http\Controllers\TevReportController;

/*
|--------------------------------------------------------------------------
| Public Routes — No auth required
|--------------------------------------------------------------------------
*/
Route::get('/', fn() => redirect()->route('login'));

Route::get('/login',  [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');

// ── HRIS SSO Routes — JWT authentication from HRIS simulation ─────
Route::get('/hris-auth', [AuthController::class, 'hrisAuth'])->name('hris.auth')->middleware('jwt.auth');
Route::get('/tev-hris-auth', [AuthController::class, 'tevHrisAuth'])->name('tev.hris.auth')->middleware('jwt.auth');


/*
|--------------------------------------------------------------------------
| Protected Routes — Requires session auth
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ── Employees ────────────────────────────────────────────────
    Route::middleware(['role:payroll_officer|hrmo|accountant|chief_admin_officer|super_admin'])
        ->group(function () {
            Route::resource('employees', EmployeeController::class);
            Route::post('/employees/pull-from-api', [EmployeeController::class, 'pullFromApi'])->name('employees.pullFromApi');

            Route::get( '/employees/{employee}/deductions',
                        [EmployeeDeductionController::class, 'index'])->name('employees.deductions');
            Route::post('/employees/{employee}/deductions',
                        [EmployeeDeductionController::class, 'update'])->name('employees.deductions.update');

            Route::get(   '/employees/{employee}/promotions',
                          [EmployeePromotionController::class, 'index'])->name('employees.promotions.index');
            Route::get(   '/employees/{employee}/promotions/create',
                          [EmployeePromotionController::class, 'create'])->name('employees.promotions.create');
            Route::post(  '/employees/{employee}/promotions',
                          [EmployeePromotionController::class, 'store'])->name('employees.promotions.store');
            Route::delete('/employees/{employee}/promotions/{promotion}',
                          [EmployeePromotionController::class, 'destroy'])->name('employees.promotions.destroy');

            // Employee TEV History
            Route::get('/employees/{employee}/tev-history',
                       [TevReportController::class, 'employeeTevHistory'])->name('employees.tev-history');
        });

    // ── Deduction Types CMS ──────────────────────────────────────
    Route::middleware(['role:payroll_officer|super_admin'])
        ->group(function () {
            Route::resource('deduction-types', DeductionTypeController::class)
                ->except(['show', 'destroy']);

            // Toggle active/inactive
            Route::patch(
                '/deduction-types/{deductionType}/toggle',
                [DeductionTypeController::class, 'toggle']
            )->name('deduction-types.toggle');

            // AJAX bulk reorder (called from index page)
            Route::post(
                '/deduction-types/reorder',
                [DeductionTypeController::class, 'reorder']
            )->name('deduction-types.reorder');
        });

    // ── Divisions ────────────────────────────────────────────────
    Route::middleware(['role:payroll_officer|hrmo|super_admin'])
        ->group(function () {
            Route::resource('divisions', DivisionController::class);
        });

    // ── Special Payroll — Newly Hired ────────────────────────────
    Route::middleware(['role:' . implode('|', \App\SharedKernel\Services\RoleService::getRoleGroup('special_payroll'))])
        ->group(function () {
            Route::get(   '/special-payroll/newly-hired',
                          [SpecialPayrollController::class, 'newHireIndex'])
                ->name('special-payroll.newly-hired.index');

            Route::get(   '/special-payroll/newly-hired/create',
                          [SpecialPayrollController::class, 'newHireCreate'])
                ->name('special-payroll.newly-hired.create');

            Route::post(  '/special-payroll/newly-hired',
                          [SpecialPayrollController::class, 'newHireStore'])
                ->name('special-payroll.newly-hired.store');

            Route::get(   '/special-payroll/newly-hired/{id}',
                          [SpecialPayrollController::class, 'newHireShow'])
                ->name('special-payroll.newly-hired.show')
                ->where('id', '[0-9]+');

            Route::delete('/special-payroll/newly-hired/{id}',
                          [SpecialPayrollController::class, 'newHireDestroy'])
                ->name('special-payroll.newly-hired.destroy')
                ->where('id', '[0-9]+');
        });

    // ── Special Payroll — Salary Differential ────────────────────
    Route::middleware(['role:' . implode('|', \App\SharedKernel\Services\RoleService::getRoleGroup('special_payroll'))])
        ->group(function () {
            Route::get(   '/special-payroll/differential',
                          [SpecialPayrollController::class, 'differentialIndex'])
                ->name('special-payroll.differential.index');

            Route::get(   '/special-payroll/differential/create',
                          [SpecialPayrollController::class, 'differentialCreate'])
                ->name('special-payroll.differential.create');

            Route::post(  '/special-payroll/differential',
                          [SpecialPayrollController::class, 'differentialStore'])
                ->name('special-payroll.differential.store');

            Route::get(   '/special-payroll/differential/{id}',
                          [SpecialPayrollController::class, 'differentialShow'])
                ->name('special-payroll.differential.show')
                ->where('id', '[0-9]+');

            Route::delete('/special-payroll/differential/{id}',
                          [SpecialPayrollController::class, 'differentialDestroy'])
                ->name('special-payroll.differential.destroy')
                ->where('id', '[0-9]+');
        });

    // ── Special Payroll — NOSI / NOSA ────────────────────────────
    Route::middleware(['role:' . implode('|', \App\SharedKernel\Services\RoleService::getRoleGroup('special_payroll'))])
        ->group(function () {
            Route::get(    '/special-payroll/nosi-nosa',
                           [SpecialPayrollController::class, 'nosiNosaIndex'])
                ->name('special-payroll.nosi-nosa.index');

            Route::get(    '/special-payroll/nosi-nosa/create',
                           [SpecialPayrollController::class, 'nosiNosaCreate'])
                ->name('special-payroll.nosi-nosa.create');

            Route::post(   '/special-payroll/nosi-nosa',
                           [SpecialPayrollController::class, 'nosiNosaStore'])
                ->name('special-payroll.nosi-nosa.store');

            Route::get(    '/special-payroll/nosi-nosa/{id}',
                           [SpecialPayrollController::class, 'nosiNosaShow'])
                ->name('special-payroll.nosi-nosa.show')
                ->where('id', '[0-9]+');

            Route::post(   '/special-payroll/nosi-nosa/{id}/approve',
                           [SpecialPayrollController::class, 'nosiNosaApprove'])
                ->name('special-payroll.nosi-nosa.approve')
                ->where('id', '[0-9]+');

            Route::delete( '/special-payroll/nosi-nosa/{id}',
                           [SpecialPayrollController::class, 'nosiNosaDestroy'])
                ->name('special-payroll.nosi-nosa.destroy')
                ->where('id', '[0-9]+');
        });


    // ── Reports ──────────────────────────────────────────────────
    Route::middleware(['role:payroll_officer|super_admin'])
        ->group(function () {
            Route::get('/reports',                    [PayrollReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/payroll-register',   [PayrollReportController::class, 'payrollRegister'])->name('reports.payroll-register');
            Route::get('/reports/payslip',            [PayrollEntryController::class, 'payslip'])->name('reports.payslip');

            // ── GSIS ─────────────────────────────────────────────────────────
            Route::get('/reports/gsis-summary',       [PayrollReportController::class, 'gsisSummary'])->name('reports.gsis-summary');
            Route::get('/reports/gsis-detailed',      [PayrollReportController::class, 'gsisDetailed'])->name('reports.gsis-detailed');
            Route::get('/reports/gsis',               [PayrollReportController::class, 'gsisIndex'])->name('reports.gsis');

            // ── HDMF / Pag-IBIG ──────────────────────────────────────────────
            Route::get('/reports/hdmf',          [PayrollReportController::class, 'hdmfIndex'])->name('reports.hdmf');
            Route::get('/reports/hdmf/download', [PayrollReportController::class, 'hdmf'])->name('reports.hdmf-download');

            // ── Other remittances ─────────────────────────────────────────────
            Route::get('/reports/remittances',     [PayrollReportController::class, 'remittancesHub'])->name('reports.remittances');
            Route::get('/reports/phic-csv',        [PayrollReportController::class, 'phicCsv'])->name('reports.phic-csv');
            Route::get('/reports/sss',             [PayrollReportController::class, 'sssVoluntary'])->name('reports.sss');
            Route::get('/reports/lbp-loan',        [PayrollReportController::class, 'lbpLoan'])->name('reports.lbp-loan');
            Route::get('/reports/caress-union',    [PayrollReportController::class, 'caressUnion'])->name('reports.caress-union');
            Route::get('/reports/caress-mortuary', [PayrollReportController::class, 'caressMortuary'])->name('reports.caress-mortuary');
            Route::get('/reports/mass',            [PayrollReportController::class, 'mass'])->name('reports.mass');
            Route::get('/reports/provident-fund',  [PayrollReportController::class, 'providentFund'])->name('reports.provident-fund');
            Route::get('/reports/btr-refund',      [PayrollReportController::class, 'btrRefund'])->name('reports.btr-refund');

            // TEV PDF reports
            Route::get('/reports/tev/{tevRequest}/itinerary',
                       [TevReportController::class, 'tevItinerary'])->name('reports.tev-itinerary');
            Route::get('/reports/tev/{tevRequest}/travel-completed',
                       [TevReportController::class, 'tevTravelCompleted'])->name('reports.tev-travel-completed');
            Route::get('/reports/tev/{tevRequest}/annex-a',
                       [TevReportController::class, 'tevAnnexA'])->name('reports.tev-annex-a');

            // TEV Register report + export
            Route::get('/reports/tev-register/export',
                       [TevReportController::class, 'tevRegisterExport'])->name('reports.tev-register.export');
            Route::get('/reports/tev-register',
                       [TevReportController::class, 'tevRegister'])->name('reports.tev-register');
        });

    // ── Users ────────────────────────────────────────────────────
    Route::middleware(['role:super_admin'])
        ->group(function () {
            Route::resource('users', UserController::class);

            // ↓ NEW: Toggle active/inactive for a specific role assignment on a user
            Route::post('users/{user}/activate-role', [UserController::class, 'activateRole'])
                ->name('users.activate-role');
        });

    // ── Signatories (payroll_officer + super_admin) ──────────────
    // Manages the dynamic signing officers shown on payslips and reports.
    Route::middleware(['role:payroll_officer|super_admin'])
        ->group(function () {
            // ↓ NEW: Must be declared BEFORE the resource to avoid Laravel
            //   treating 'users-for-role' as a {signatory} model binding.
            Route::get('signatories/users-for-role', [SignatoryController::class, 'usersForRole'])
                ->name('signatories.users-for-role');

            Route::resource('signatories', SignatoryController::class)
                ->except(['show']);

            Route::patch('/signatories/{signatory}/toggle',
                         [SignatoryController::class, 'toggle'])
                ->name('signatories.toggle');
        });

});

/*
|--------------------------------------------------------------------------
| AJAX API Routes — Auth required, /api prefix
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/sit', [SalaryIndexTableController::class, 'lookup'])->name('api.sit');
});
