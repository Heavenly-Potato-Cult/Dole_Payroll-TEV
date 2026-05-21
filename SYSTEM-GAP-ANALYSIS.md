# Dole Payroll System — Comprehensive Gap Analysis Report

**Prepared:** April 9, 2026 · **Last reviewed:** May 2026  
**System:** DOLE Regional Office 9 — Payroll Management System  
**Stack:** Laravel 11 · PHP 8.2 · Spatie Permission · DomPDF · Maatwebsite Excel · Laravel Modules (Payroll, Tev)  
**Purpose:** Document incomplete, stub, or missing components and provide a prioritized implementation roadmap.

### Current development workflow (May 2026)

| In use | Notes |
|--------|--------|
| **XAMPP** + `php artisan serve` | Primary local runtime |
| **`pull-from-gdrive.bat`** / **`push-to-gdrive.bat`** | Team database sync via rclone |
| **`get-rclone.bat`** | One-time rclone download |
| **`TEAM-SETUP.md`**, **`README.md`**, this file | Active documentation |

**Removed from repo (no longer documented):** Docker (`docker-compose.yaml`, `Dockerfile`, `start.sh`, `initial_start.bat`, `.env.docker`), `setup-xampp.bat`, `restore-data.bat`, `gdrive-upload.bat`, `gdrive-download.bat`, `gdrive-list.bat`, `phpunit.xml`, and auxiliary markdown guides (`README-SETUP.md`, `commands.md`, etc.).

---

## Table of Contents

1. [System Architecture Overview](#1-system-architecture-overview)
2. [Gap Summary Matrix](#2-gap-summary-matrix)
3. [Tier 1 — Critical Gaps (Impact 9–10)](#3-tier-1--critical-gaps-impact-910)
4. [Tier 2 — High Impact Gaps (Impact 7–8)](#4-tier-2--high-impact-gaps-impact-78)
5. [Tier 3 — Medium Impact Gaps (Impact 5–6)](#5-tier-3--medium-impact-gaps-impact-56)
6. [Tier 4 — Architectural & Structural Gaps (Impact 5–7 long-term)](#6-tier-4--architectural--structural-gaps-impact-57-long-term)
7. [Inter-Gap Dependency Map](#7-inter-gap-dependency-map)
8. [Recommended Implementation Roadmap](#8-recommended-implementation-roadmap)
9. [Risk Register](#9-risk-register)

---

## 1. System Architecture Overview

### What Exists

The system follows a standard Laravel MVC architecture with a service layer:

```
routes/web.php
  └── Modules/Payroll/     — PayrollController, PayrollReportController, …
  └── Modules/Tev/         — TevController, TevReportController, …
  └── app/                 — AuthController, SharedKernel services/models
        ├── FormRequests
        ├── Services (module + SharedKernel)
        └── Eloquent models
```

### Modules Present

| Module | Controller | Service(s) | Status |
|---|---|---|---|
| Authentication | `AuthController` | — | ✅ Complete |
| Dashboard | `DashboardController` | — | ✅ Complete |
| Employees | `EmployeeController` | — | ✅ Mostly complete |
| Divisions | `DivisionController` | — | ✅ Complete |
| Payroll Batches | `PayrollController` | `PayrollComputationService` | ✅ Core complete, attendance stubbed |
| Payroll Entries | `PayrollEntryController` | `PayrollComputationService` | ✅ Complete |
| Deductions | `EmployeeDeductionController` | `DeductionService` | ✅ Complete |
| Special Payroll | `SpecialPayrollController` | `NewlyHiredPayrollService`, `SalaryDifferentialService` | ✅ Complete |
| TEV | `TevController`, `TevItineraryController` | `TevComputationService` | ✅ Complete |
| Office Orders | `OfficeOrderController` | — | ✅ Complete |
| Reports | `PayrollReportController`, `TevReportController` | `ReportService` (stub) | ⚠️ Largely implemented; shared `ReportService` still empty |
| Users | `UserController` | — | ✅ Complete |
| HRIS Integration | — | `HrisApiService`, `AttendanceService` | ❌ Stubbed/mocked |

### Roles in the System

`payroll_officer` · `hrmo` · `accountant` · `ard` · `chief_admin_officer` · `cashier` · `budget_officer`

---

## 2. Gap Summary Matrix

| # | Gap | Tier | Impact | Type | Blocks |
|---|---|:---:|:---:|---|---|
| G-01 | ~~`ReportController` missing 16 methods~~ | 1 | **—** | **Resolved** | Reports modularized |
| G-02 | HRIS Attendance not integrated | 1 | **9/10** | Business Logic | G-03 |
| G-03 | YTD Gross = 0 → WHT miscalculated | 1 | **9/10** | Business Logic | — |
| G-04 | 7 Government Export classes empty | 2 | **8/10** | Compliance | — |
| G-05 | Zero automated test suite | 2 | **7/10** | Quality | — |
| G-06 | `EmployeePolicy` & `TevPolicy` empty | 2 | **7/10** | Security | — |
| G-07 | 10 standard report views are stubs | 3 | **6/10** | UI/UX | G-01 |
| G-08 | `payroll/payslip` & `payroll/detail` views are stubs | 3 | **6/10** | UI/UX | — |
| G-09 | 3 UI component views are stubs | 3 | **5/10** | UI/UX | — |
| G-10 | `ApprovePayrollRequest` & `StoreSpecialPayrollRequest` have no rules | 3 | **5/10** | Security | — |
| G-11 | `ReportService` is an empty class | 3 | **5/10** | Architecture | G-01 |
| G-12 | No `app/Repositories/` layer | 4 | **6/10** (long-term) | Architecture | G-05 |
| G-13 | Only 1 Factory (`UserFactory`) | 4 | **7/10** (long-term) | Testability | G-05 |
| G-14 | 5 API Resource classes return raw data | 4 | **5/10** (long-term) | Security/Privacy | — |
| G-15 | No reusable role-checking Middleware | 4 | **5/10** (long-term) | Security | G-06 |

---

## 3. Tier 1 — Critical Gaps (Impact 9–10)

These gaps cause **immediate runtime failures or produce structurally incorrect payroll figures** in every computation run.

---

### G-01 · ~~Missing `ReportController` methods~~ — **RESOLVED (May 2026)**

**Original impact: 10/10** | **Status: Closed**

Payroll and TEV reports were moved into module controllers:

- `Modules/Payroll/Http/Controllers/PayrollReportController.php` — GSIS, HDMF, remittances, payroll register, etc.
- `Modules/Tev/Http/Controllers/TevReportController.php` — TEV itinerary, travel completed, Annex A, liquidation DV, TEV register

Routes are registered in `routes/web.php`. The legacy monolithic `ReportController` gap no longer applies.

**Remaining related work:** **G-11** (`ReportService` still empty), **G-04** (verify all export classes return real data), **G-07** (stub blade views if any remain under module `resources/views/reports/`).

---

### G-02 · HRIS Attendance Integration Not Wired

**Impact: 9/10** | **Type: Silent Business Logic Failure**

#### Description

`AttendanceService::getAttendanceForBatch()` is documented as a stub and unconditionally returns an empty array `[]`. The `HrisApiService::fetchAttendance()` returns a mock object simulating perfect attendance (0 LWOP days, 0 late minutes, 0 undertime minutes) for every employee.

```php
// AttendanceService.php — line 119–123
public function getAttendanceForBatch(PayrollBatch $batch): array
{
    // STUB: returns empty attendance — no deductions applied.
    return [];
}
```

```php
// HrisApiService.php — line 77–78
// ── MOCK RESPONSE (development stub) ─────────────────────
return $this->mockAttendance($employeeId, $cutoffStart, $cutoffEnd);
```

#### Affected Files

- `app/Services/AttendanceService.php` — `getAttendanceForBatch()` is a stub
- `app/Services/HrisApiService.php` — `fetchAttendance()` returns mock data
- `app/Services/PayrollComputationService.php` — receives empty `$attendance` array, so LWOP and tardiness deductions are always ₱0.00
- `.env` / `.env.example` — `HRIS_API_URL` and `HRIS_API_KEY` entries exist but are not consumed by any real code path

#### Root Cause

The external HRIS API (managed by Maam Eden Cutara, TSSD) was not yet available during initial development. The stub was intentionally placed as a placeholder.

#### Consequence

- **Every employee appears to have perfect attendance** in every payroll batch
- LWOP salary deductions = ₱0.00 (should deduct based on actual absent days)
- LWOP PERA deductions = ₱0.00
- Tardiness deductions = ₱0.00
- Undertime deductions = ₱0.00
- Net pay is **overstated** for any employee with actual absences, tardiness, or undertime
- Government disbursements are paying more than employees are entitled to

#### Implementation Path

1. Confirm API contract with TSSD team (endpoint URL, auth method, request/response shape)
2. Replace the mock block in `HrisApiService::fetchAttendance()` with a real `Http::withToken()` call
3. Implement `getAttendanceForBatch()` in `AttendanceService` to call `HrisApiService` for each employee in the batch
4. Add a fallback (perfect-attendance default) for when the HRIS API is unavailable, with a logged warning
5. Wire the result into `PayrollController::compute()` so `$attendance` arrays are real data

#### Estimated Effort

Medium. The integration code is already scaffolded and commented in `HrisApiService.php`. The main effort is API contract confirmation and error handling.

---

### G-03 · YTD Gross Not Tracked — Withholding Tax Always Under-Computed

**Impact: 9/10** | **Type: Silent Compliance Failure**

#### Description

`DeductionService::computeWithholdingTax()` accepts a `$ytdGross` parameter representing year-to-date gross earnings before the current cut-off. This value is used to annualize projected income for BIR TRAIN Law withholding tax computation. However, `ytdGross` is **always passed as `0`** from `PayrollComputationService`.

```php
// DeductionService.php — line 169–171 (documented stub warning)
// ⚠ STUB NOTE: ytdGross is currently passed as 0 from PayrollComputationService
// because attendance / YTD tracking is not yet wired. This will be refined
// in Phase 3A when YTD accumulation logic is implemented.
```

#### Affected Files

- `app/Services/DeductionService.php` — `computeWithholdingTax()` receives `ytdGross = 0`
- `app/Services/PayrollComputationService.php` — passes `ytdGross = 0` hardcoded
- `app/Models/PayrollEntry.php` — no `ytd_gross` column tracked
- `database/migrations/` — no YTD accumulation table or column exists

#### Root Cause

YTD gross accumulation requires summing all previous payroll entries for the same employee in the same calendar year. This logic was deferred ("Phase 3A") and never implemented.

#### Consequence

- WHT is computed as if the employee earns the same amount projected over 24 cut-offs from the current period — it ignores all prior income in the year
- In early cut-offs (January), WHT is close to correct by coincidence
- In later cut-offs (e.g., July onward), WHT is **significantly under-computed** because prior months' actual earnings are ignored
- Employees may face a large tax reconciliation shortfall at year-end
- BIR audits may flag under-withheld tax as a compliance violation

#### Implementation Path

1. Add a `ytd_gross` field to `payroll_entries` (or a dedicated YTD tracking table)
2. After each batch is locked, compute and store YTD gross per employee
3. In `PayrollComputationService`, query the sum of `net_before_tax` (or gross) from all locked batches for the employee in the same `period_year` before the current cut-off
4. Pass the result as `$ytdGross` to `DeductionService`
5. Add a unit test verifying WHT calculation correctness across multiple cut-offs in the same year

#### Estimated Effort

Medium. Requires a migration, a query, and integration into the computation pipeline. The WHT formula itself is already correctly implemented.

---

## 4. Tier 2 — High Impact Gaps (Impact 7–8)

These gaps do not crash the application but represent **missing compliance functionality or a complete absence of quality assurance**.

---

### G-04 · 7 Government Remittance Export Classes Are Empty Stubs

**Impact: 8/10** | **Type: Compliance Failure**

#### Description

Seven Excel export classes under `app/Exports/` were created as scaffolding stubs. Each implements `FromCollection` but returns `collect([])` — an empty collection — producing blank Excel files when downloaded.

#### Affected Files and What They Should Produce

| File | Government Body | Report Name |
|---|---|---|
| `app/Exports/GsisDetailedExport.php` | GSIS | Monthly detailed remittance per employee |
| `app/Exports/GsisSummaryExport.php` | GSIS | Consolidated monthly remittance summary |
| `app/Exports/HdmfP1Export.php` | Pag-IBIG (HDMF) | Regular contribution (P1) |
| `app/Exports/HdmfP2Export.php` | Pag-IBIG (HDMF) | Calamity/multi-purpose loan (P2) |
| `app/Exports/HdmfMplExport.php` | Pag-IBIG (HDMF) | MPL loan amortization |
| `app/Exports/HdmfCalExport.php` | Pag-IBIG (HDMF) | CAL (Calamity) loan amortization |
| `app/Exports/HdmfHousingExport.php` | Pag-IBIG (HDMF) | Housing loan amortization |

#### Root Cause

These exports require querying `PayrollDeduction` records filtered by deduction type codes (e.g., `GSIS_LIFE_RETIREMENT`, `PAG_IBIG_1`, etc.), joining employee and division data, and formatting output to match the official government-prescribed column layouts. This was deferred as a separate implementation task.

#### Consequence

- Payroll officers cannot generate official GSIS remittance reports; manual preparation in spreadsheets is required
- HDMF loan payment reports cannot be produced; the agency risks late or incorrect loan remittances
- Late GSIS/HDMF remittances carry penalties and interest charges under their respective charters
- RA 8291 (GSIS Act) and RA 9679 (HDMF Law) mandate timely remittance — blank exports = non-compliance

#### Implementation Path

1. Identify the official column format for each government form (GSIS Form, HDMF RF-1, etc.)
2. For each export class, inject a `PayrollBatch $batch` parameter in the constructor
3. Query `PayrollDeduction` records for the batch filtered by the relevant `deduction_type.code`
4. Join with `Employee` and `Division` for required demographic fields
5. Use Maatwebsite Excel's `WithHeadings`, `WithMapping`, and `WithStyles` concerns for proper formatting
6. Wire each export into its corresponding `ReportController` method (resolves part of G-01)

#### Estimated Effort

Medium per export. The data model and deduction codes are already in place. Effort is in mapping columns to the official government form layouts.

---

### G-05 · Zero Automated Test Suite

**Impact: 7/10** | **Type: Quality Assurance Absence**

#### Description

The `tests/` directory contains only two default Laravel scaffold tests that assert trivially true conditions. There are **no domain-specific tests** for any business logic in the system.

```
tests/
  Feature/ExampleTest.php   — asserts GET / returns 200
  Unit/ExampleTest.php      — asserts true === true
```

PHPUnit 10.5 and Mockery are listed in `composer.json` `require-dev`. The project root `phpunit.xml` was removed; re-add with `php artisan test` / a new `phpunit.xml` when starting automated tests. Scribe is present for API docs. The toolchain is available — domain tests are still missing.

#### What Has No Test Coverage

- Payroll computation math (`PayrollComputationService`)
- Deduction formula calculations: GSIS, PhilHealth, Pag-IBIG, WHT
- BIR TRAIN Law graduated tax brackets (`DeductionService::birGraduatedTax`)
- Table IV tardiness conversion (`TableIVConverter::minutesToDays`)
- TEV per-diem computation (`TevComputationService`)
- Salary differential calculation (`SalaryDifferentialService`)
- Newly hired payroll proration (`NewlyHiredPayrollService`)
- Payroll workflow state transitions (`PayrollPolicy`)
- Role-based access control enforcement
- HRIS API integration and fallback behavior

#### Consequence

- Any formula change (e.g., PhilHealth rate update, TRAIN Law amendment) has no regression safety net
- Silent computation bugs can propagate through hundreds of payroll entries before being noticed
- New developers have no executable specification of expected behavior
- The `knuckleswtf/scribe` package (API doc generator) in `composer.json` suggests API documentation was planned but has no tests to derive from

#### Implementation Path

1. Implement Model Factories first (see G-13) — they are a hard prerequisite
2. Unit test each computation method in isolation (mock DB calls using Mockery)
3. Feature test the full payroll computation pipeline end-to-end
4. Feature test all workflow state transitions (draft → computed → pending_accountant → ...)
5. Feature test role-based access enforcement on each protected route
6. Add a CI check (e.g., GitHub Actions) to run the test suite on every push

#### Priority Tests to Write First

```
Unit/DeductionService/BirGraduatedTaxTest.php        — tax bracket correctness
Unit/DeductionService/PhilHealthComputationTest.php  — rate and cap enforcement
Unit/AttendanceService/TableIVConversionTest.php     — minute-to-day conversion
Unit/PayrollComputationService/NetPayTest.php        — end-to-end net pay formula
Feature/PayrollWorkflow/ComputeAndSubmitTest.php     — full batch lifecycle
Feature/Auth/RoleAccessControlTest.php              — unauthorized access returns 403
```

#### Estimated Effort

Large. Requires factories (G-13), significant test authoring, and potentially a test database configuration. High ROI once in place.

---

### G-06 · `EmployeePolicy` and `TevPolicy` Are Empty

**Impact: 7/10** | **Type: Authorization Security Gap**

#### Description

Two of three policy classes are entirely unimplemented stubs with TODO comments:

```php
// app/Policies/EmployeePolicy.php
class EmployeePolicy {}  // TODO: implement EmployeePolicy

// app/Policies/TevPolicy.php
class TevPolicy {}       // TODO: implement TevPolicy
```

Only `PayrollPolicy` is fully implemented and registered in `AppServiceProvider`. Neither `EmployeePolicy` nor `TevPolicy` is registered as a `Gate::policy()` binding, meaning Laravel's authorization system does not use them at all.

Authorization for Employee and TEV routes is currently handled ad-hoc via inline `abort(403)` checks scattered across `EmployeeController`, `TevController`, and `TevItineraryController`.

#### Consequence

- Authorization rules for Employee CRUD and TEV workflow are invisible to the policy system — they cannot be queried via `$this->authorize()`, `can()`, or Blade `@can` directives
- Rules exist in multiple places: one bug in one controller does not affect the others, creating silent inconsistency
- There is no single authoritative source of truth for "who can do what" with employees or TEV requests
- New routes added in the future may miss authorization entirely if developers assume policies handle it

#### Implementation Path

1. Implement `EmployeePolicy` with methods: `viewAny`, `view`, `create`, `update`, `delete`
   - Only `payroll_officer` and `hrmo` can create/update/delete employees
2. Implement `TevPolicy` with methods: `create`, `submit`, `approve`, `certify`, `reject`, `fileLiquidation`, `approveLiquidation`
   - Model the approval chain already documented in `TevController`
3. Register both policies in `AppServiceProvider::boot()`
4. Replace all inline `abort(403)` checks in controllers with `$this->authorize('action', $model)`
5. Add `@can` directives to blade views to conditionally show action buttons

#### Estimated Effort

Medium. The authorization logic is already implicit in the controllers — it just needs to be extracted and formalized.

---

## 5. Tier 3 — Medium Impact Gaps (Impact 5–6)

These gaps produce **degraded user experience, missing UI elements, or bypassed input validation**.

---

### G-07 · 10 Standard Report Blade Views Are Empty Stubs

**Impact: 6/10** | **Type: UI/UX Failure**

#### Description

Ten blade view files under `resources/views/reports/` contain nothing but a TODO comment and render blank pages:

| View File | Expected Content |
|---|---|
| `reports/index.blade.php` | Reports landing page with links to all report types |
| `reports/payroll-register.blade.php` | HTML payroll register (all employees in a batch) |
| `reports/payslip.blade.php` | Payslip search/print interface |
| `reports/gsis-summary.blade.php` | GSIS remittance summary table |
| `reports/btr-refund.blade.php` | BTR refund report |
| `reports/caress-union.blade.php` | CARESS union dues deduction report |
| `reports/caress-mortuary.blade.php` | CARESS mortuary contribution report |
| `reports/lbp-loan.blade.php` | LBP loan amortization report |
| `reports/mass.blade.php` | MASS (Multi-purpose loan) report |
| `reports/provident-fund.blade.php` | Provident Fund report |

#### Implementation Path

Each view should: extend the main layout → display filter controls (batch, month, year) → render a formatted data table → provide a print/export button.

---

### G-08 · `payroll/payslip.blade.php` and `payroll/detail.blade.php` Are Stubs

**Impact: 6/10** | **Type: Core Feature Missing**

#### Description

Both files contain only a `{{-- TODO --}}` comment. The `payroll/payslip` route is wired to `PayrollEntryController::payslip()` and is intended to render a printable payslip for an individual employee per cut-off.

Note: TEV and payslip PDFs under `resources/views/payslip/` are **implemented**. These stubs are the separate in-browser/HTML versions.

#### Consequence

- Users cannot view formatted payroll detail for a batch in the browser
- The payslip print function from the payroll module is non-functional

---

### G-09 · 3 Shared UI Components Are Empty Stubs

**Impact: 5/10** | **Type: UI Rendering Failure**

#### Description

Three Blade component files under `resources/views/components/` are TODO stubs:

| Component | Usage | Expected Behavior |
|---|---|---|
| `<x-alert>` | Flash messages (success, error, warning) throughout every view | Renders styled alert boxes from session flash data |
| `<x-status-badge>` | Payroll and TEV status display in tables and detail views | Renders a colored badge for status values (draft, pending, approved, etc.) |
| `<x-approval-timeline>` | Payroll and TEV detail views | Renders a horizontal/vertical timeline of approval steps with timestamps |

#### Consequence

- Flash messages (e.g., "Payroll batch created successfully") render as empty space — users get no feedback after actions
- Status columns in all listing tables show nothing where a badge should appear
- Approval history timelines in `payroll/show.blade.php` and `tev/show.blade.php` are invisible

---

### G-10 · Two FormRequest Classes Have No Validation Rules

**Impact: 5/10** | **Type: Input Validation Bypass**

#### Description

```php
// ApprovePayrollRequest.php
public function rules() { return []; }

// StoreSpecialPayrollRequest.php
public function rules() { return []; }
```

Both requests pass all input through without any server-side validation.

#### Consequence

- Payroll approval can be submitted with arbitrary or missing fields (e.g., no `remarks` when a remarks field is expected)
- Special payroll creation accepts malformed data that may cause unexpected computation behavior
- No validation means no clean error messages for the user — failures surface as runtime exceptions

#### Implementation Path

- `ApprovePayrollRequest`: add validation for `remarks` (required if rejecting) and any status-specific required fields
- `StoreSpecialPayrollRequest`: add validation for `employee_id`, `type`, `effective_date`, and any amount fields

---

### G-11 · `ReportService` Is an Empty Shell

**Impact: 5/10** | **Type: Architecture Violation**

#### Description

```php
// app/Services/ReportService.php
class ReportService {}  // TODO: implement ReportService
```

All existing report logic lives directly inside `ReportController`, which handles data queries, filtering, PDF generation, and Excel exports inline. This is consistent with the service-layer architecture used for all other modules but completely absent for reports.

#### Consequence

- Report data queries cannot be reused across different output formats (HTML view vs. PDF vs. Excel export)
- The controller grows uncontrolled as more reports are added
- Report logic cannot be unit tested without an HTTP request context

#### Implementation Path

Move shared report query logic into `ReportService`:
- `getPayrollRegisterData(PayrollBatch $batch): Collection`
- `getTevRegisterData(array $filters): Collection`
- `getGsisSummaryData(PayrollBatch $batch): Collection`
- `getHdmfRemittanceData(PayrollBatch $batch, string $type): Collection`

Controllers then call the service and choose the output format (view/PDF/Excel).

---

## 6. Tier 4 — Architectural & Structural Gaps (Impact 5–7 Long-Term)

These gaps do not cause immediate failures but **compound technical debt**, block testability, and create security drift over time. They are rated on a **6-month production horizon** rather than immediate runtime breakage.

---

### G-12 · No Repository Layer (`app/Repositories/`)

**Long-Term Impact: 6/10** | **Type: Architecture / Testability**

#### Description

The system architecture is: **Controller → Service → Eloquent Model (directly)**. There is no `app/Repositories/` layer. Every service (`PayrollComputationService`, `DeductionService`, `SalaryDifferentialService`, etc.) issues Eloquent queries inline within its business logic methods.

Example in `PayrollComputationService`:
```php
$deductionTypes = DeductionType::orderBy('display_order')->get()->keyBy('code');
$enrollments = $employee->deductionEnrollments()->activeOn(...)->get();
```

#### Consequence

- Service methods cannot be unit tested without a live database — every test is an integration test
- Swapping the data source (e.g., caching frequently-read deduction types) requires modifying service files
- Business logic and data access logic are tightly interleaved
- As queries grow more complex, service files become difficult to reason about

#### Implementation Path

Introduce repository interfaces and Eloquent implementations:

```
app/Repositories/
  Contracts/
    PayrollRepositoryInterface.php
    EmployeeRepositoryInterface.php
    DeductionRepositoryInterface.php
  Eloquent/
    PayrollRepository.php
    EmployeeRepository.php
    DeductionRepository.php
```

Bind interfaces to implementations in `AppServiceProvider`. Services receive repositories via constructor injection, enabling mock injection in tests.

> **Note:** This is a refactor of existing working code. Prioritize only after G-01 through G-06 are resolved.

---

### G-13 · Only 1 Factory — `UserFactory`

**Long-Term Impact: 7/10** | **Type: Testability Hard Blocker**

#### Description

`database/factories/` contains only `UserFactory.php`. There are no factories for any domain model:

| Missing Factory | Needed By |
|---|---|
| `EmployeeFactory` | Payroll computation tests, TEV tests, deduction tests |
| `PayrollBatchFactory` | All payroll workflow tests |
| `PayrollEntryFactory` | Entry update, payslip, and net pay tests |
| `TevRequestFactory` | TEV workflow and report tests |
| `OfficeOrderFactory` | TEV and office order tests |
| `DeductionTypeFactory` | Deduction enrollment tests |
| `EmployeeDeductionEnrollmentFactory` | Deduction computation tests |
| `SpecialPayrollBatchFactory` | Newly hired / differential / NOSI tests |

#### Why This Rates 7/10

This gap **directly blocks G-05 (test suite)**. Without factories:
- You cannot create test employees with specific salary grades, deduction enrollments, and GSIS data
- Edge cases (mid-month hire, zero PERA employee, multiple loan deductions) cannot be systematically tested
- The entire test strategy is non-executable regardless of how many test files you write

#### Implementation Path

Create factories for each model above using `php artisan make:factory`. Use the existing seeder data (`SalaryIndexTableSeeder`, `DeductionTypeSeeder`) as reference for realistic values.

---

### G-14 · 5 API Resource Classes Return Unfiltered Raw Data

**Long-Term Impact: 5/10** | **Type: Data Privacy / Security**

#### Description

Five resource classes return `parent::toArray($request)` which dumps **every Eloquent column** of the model:

| Resource | Sensitive Fields That Leak |
|---|---|
| `PayrollBatchResource` | Internal status codes, workflow timestamps, creator IDs |
| `PayrollEntryResource` | Salary amounts, individual deduction breakdowns, net pay |
| `TevRequestResource` | Personal travel data, per-diem amounts, approval chain |
| `SpecialPayrollBatchResource` | Salary differential amounts, approval data |
| `OfficeOrderResource` | Internal office order metadata |

#### Legal Context

Under **RA 10173 (Data Privacy Act of 2012)**, salary data and personal employee information are classified as personal information requiring proportionality in disclosure. Unfiltered API responses to any consumer (mobile app, HRIS integration, DBM system) may constitute a privacy violation.

#### Implementation Path

Explicitly define `toArray()` for each resource, listing only the fields required by the consuming interface. Use `$this->when()` for conditional fields (e.g., show detailed breakdown only to `payroll_officer` role).

---

### G-15 · No Reusable Role-Checking Middleware

**Long-Term Impact: 5/10** | **Type: Security Consistency**

#### Description

Every controller defines its own private `authorizeRole()` helper:

```php
// Duplicated across: PayrollController, TevController, ReportController,
// SpecialPayrollController, OfficeOrderController, UserController
private function authorizeRole(array $roles): void
{
    if (!Auth::user()->hasAnyRole($roles)) {
        abort(403);
    }
}
```

This same pattern exists in **7 separate controller files** with no shared source of truth.

#### Consequence

- If a role name changes (e.g., `ard` renamed to `assistant_rd`), every controller must be updated individually
- Adding a new role requires auditing all controllers to determine where it should be granted access
- A typo in one controller's role array silently grants or denies access without any test to catch it
- The `budget_officer` role appears in `DashboardController` and some route comments but may be missing from some controller `authorizeRole()` calls

#### Implementation Path

Create a `CheckRole` middleware class:

```php
// app/Http/Middleware/CheckRole.php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    if (!$request->user()?->hasAnyRole($roles)) {
        abort(403);
    }
    return $next($request);
}
```

Register it in `bootstrap/app.php` under an alias (e.g., `role`). Apply it at the route group level instead of per-controller.

---

## 7. Inter-Gap Dependency Map

Some gaps must be resolved before others can be meaningfully implemented. The dependency graph below shows which gaps block others:

```
G-13 (Factories)
  └── blocks → G-05 (Test Suite)
                  └── benefits from → G-12 (Repository Layer)

G-02 (HRIS Attendance)
  └── blocks → G-03 (YTD/WHT accuracy)
               (attendance data needed to compute YTD correctly)

G-01 (ReportController) — RESOLVED
  └── relates → G-07 (Report Views), G-04 (Exports), G-11 (ReportService)

G-06 (Policies)
  └── relates → G-15 (Middleware)    [both address the same authorization concern]

G-12 (Repository Layer)
  └── prerequisite for → G-05 (meaningful unit tests without DB)
```

**Recommended Resolution Order Based on Dependencies:**

```
Phase 1:  G-09 → G-10                  (UI feedback + validation; G-01 done)
Phase 2:  G-02 → G-03                  (fix payroll computation accuracy)
Phase 3:  G-04 → G-07 → G-11           (complete the reports module)
Phase 4:  G-06 → G-15                  (formalize authorization)
Phase 5:  G-13 → G-05                  (build the test suite)
Phase 6:  G-08 → G-14 → G-12           (payslip views, API hygiene, architecture)
```

---

## 8. Recommended Implementation Roadmap

### Phase 1 — Unblock the Application (Est. 1–2 weeks)

**Goal:** Eliminate all 500 errors and restore basic UI feedback.

- [x] **G-01** — Reports modularized (`PayrollReportController`, `TevReportController`)
- [ ] **G-09** — Implement `<x-alert>`, `<x-status-badge>`, and `<x-approval-timeline>` components
- [ ] **G-10** — Add validation rules to `ApprovePayrollRequest` and `StoreSpecialPayrollRequest`

**Success Criteria:** No 500 errors on any defined route. Flash messages visible. Status badges display in tables.

---

### Phase 2 — Restore Payroll Accuracy (Est. 2–4 weeks, HRIS API dependent)

**Goal:** Ensure payroll figures reflect actual attendance and correct tax withholding.

- [ ] **G-02** — Coordinate with TSSD team; wire `HrisApiService` to the real HRIS API endpoint
- [ ] **G-02** — Implement `AttendanceService::getAttendanceForBatch()` with real API calls
- [ ] **G-03** — Add `ytd_gross` tracking per employee per year; wire into `DeductionService`

**Success Criteria:** A payroll batch computation shows non-zero LWOP/tardiness deductions for an employee with known absences. WHT amounts increase correctly in later months of the year.

---

### Phase 3 — Complete the Reports Module (Est. 3–4 weeks)

**Goal:** Make all reports functional and exportable.

- [ ] **G-11** — Implement `ReportService` with shared data-fetching methods
- [ ] **G-04** — Implement all 7 Export classes (GSIS, HDMF variants)
- [ ] **G-07** — Implement all 10 stub report blade views
- [ ] **G-08** — Implement `payroll/payslip.blade.php` and `payroll/detail.blade.php`
- [ ] Verify all report URLs and exports end-to-end under module controllers

**Success Criteria:** All report URLs return a rendered page or file download. GSIS and HDMF exports produce populated Excel files.

---

### Phase 4 — Formalize Authorization (Est. 1 week)

**Goal:** Centralize and formalize all access control rules.

- [ ] **G-06** — Implement `EmployeePolicy` and `TevPolicy`; register in `AppServiceProvider`
- [ ] **G-15** — Create `CheckRole` middleware; apply at route group level
- [ ] Replace all inline `authorizeRole()` private methods in controllers with `$this->authorize()` calls

**Success Criteria:** All controller authorization logic delegates to a registered policy or named middleware. No `authorizeRole()` private methods remain in controllers.

---

### Phase 5 — Build the Test Suite (Est. 4–6 weeks, ongoing)

**Goal:** Establish a regression-safe test suite for all business logic.

- [ ] **G-13** — Create all missing model factories
- [ ] **G-05** — Write unit tests for: `DeductionService`, `AttendanceService`, `PayrollComputationService`, `TevComputationService`, `SalaryDifferentialService`, `NewlyHiredPayrollService`, `TableIVConverter`
- [ ] **G-05** — Write feature tests for: payroll workflow, TEV workflow, role-based access control
- [ ] Set up a CI pipeline (GitHub Actions) to run tests on every commit

**Success Criteria:** `php artisan test` passes with coverage for all computation services. Regression tests catch a deliberate formula mutation.

---

### Phase 6 — Architecture Hardening (Est. 2–3 weeks, low urgency)

**Goal:** Improve long-term maintainability and data privacy compliance.

- [ ] **G-12** — Introduce Repository interfaces for `Employee`, `PayrollBatch`, `DeductionType`
- [ ] **G-14** — Explicitly define `toArray()` for all 5 stub API Resource classes
- [ ] Update `AppServiceProvider` to bind repository interfaces to implementations

**Success Criteria:** Service methods no longer call Eloquent directly; all DB access goes through repository interfaces. API resources output only explicitly whitelisted fields.

---

## 9. Risk Register

| Risk | Probability | Impact | Mitigation |
|---|---|---|---|
| HRIS API never delivered by TSSD | Medium | Critical — payroll forever uses mock data | Implement a manual attendance override UI as a fallback |
| BTR/CARESS/LBP report format changes | Low | Medium — existing export layout becomes wrong | Store report column configs in DB, not hardcoded |
| YTD computation retroactively incorrect for prior batches | High | High — historical WHT records are inaccurate | Add a backfill migration that computes YTD from existing locked batch data |
| New PhilHealth/GSIS rate changes | Medium | High — all deduction computations are wrong | Externalize rate constants to `.env` or a `config/deductions.php` file |
| Employee without a `basic_salary` value passes validation | Medium | Medium — division by zero or ₱0 payroll entry | Add `min:1` validation on `StoreEmployeeRequest` for salary fields |
| Locked batch force-edited without audit log | Low | High — compliance trail broken | Add a DB-level trigger or Observer on `PayrollEntry` updates to enforce audit logging |
| `payroll_officer` role assigned to wrong user | Low | Critical — full payroll write access | Enforce 2FA or require a second `payroll_officer` confirmation for role assignment |

---

*This report should be reviewed and updated after each development phase is completed. All gap IDs (G-01 through G-15) should be treated as backlog item identifiers in your project tracker.*
