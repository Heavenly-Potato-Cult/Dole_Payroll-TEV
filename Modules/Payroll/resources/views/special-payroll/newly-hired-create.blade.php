{{-- resources/views/special-payroll/newly-hired-create.blade.php --}}
{{--
    Expects from SpecialPayrollController@newHireCreate:
      $employees — collection of active Employee models
--}}

@extends('layouts.app')

@section('title', 'New Hire — Pro-Rated Payroll')
@section('page-title', 'Special Payroll')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2/dist/css/tom-select.bootstrap5.min.css">
<style>
/* ── Searchable Employee Dropdown (Tom Select) ── */
.ts-wrapper.single .ts-control {
    padding: 8px 10px;
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 0.85rem;
    min-height: unset;
}
.ts-wrapper.single.focus .ts-control {
    border-color: var(--navy);
    box-shadow: 0 0 0 3px rgba(31, 41, 55, 0.08);
}
.ts-dropdown {
    font-size: 0.85rem;
    border-color: var(--border);
    background: #ffffff;
    z-index: 1000;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    border-radius: 6px;
    overflow: hidden;
}
.ts-dropdown .ts-dropdown-content {
    background: #ffffff;
}
.ts-dropdown [data-selectable],
.ts-dropdown .option {
    background: #ffffff;
    color: var(--text, #1F2937);
}
.ts-dropdown .active {
    background: var(--navy);
    color: #fff;
}
.ts-wrapper.is-invalid .ts-control {
    border-color: var(--red);
}

/* ── Responsive: Special Payroll Create Pages ── */
.sp-create-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 24px;
    align-items: start;
}
.sp-date-row,
.sp-cutoff-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 900px) {
    .sp-create-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 600px) {
    .sp-date-row,
    .sp-cutoff-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .sp-action-row {
        flex-direction: column;
    }
    .sp-action-row .btn {
        width: 100%;
        text-align: center;
    }
    .stat-grid {
        grid-template-columns: 1fr 1fr !important;
    }
}
</style>
@endsection

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1 id="pageTitle">Pro-Rated Payroll — Newly Hired / Transferee</h1>
        <p id="pageDescription">Compute pro-rated salary for an employee who started mid-period.</p>
    </div>
    <a href="{{ route('special-payroll.newly-hired.index') }}" class="btn btn-outline btn-sm">
        ← Back to Records
    </a>
</div>

<div class="alert alert-info mb-3">
    <div>
        <strong>How pro-rated computation works:</strong>
        Salary is computed as <strong>(Basic ÷ 22) × working days</strong> from the
        effectivity date to the end of the cut-off. Only the GSIS Personal Share (9.24%)
        is deducted. PhilHealth and Pag-IBIG are remitted as government share only.
        Withholding tax is ₱0 pending annualization.
    </div>
</div>

<div class="sp-create-grid">

    {{-- ── Main Form ── --}}
    <div class="card">
        <div class="card-header">
            <h3>📋 Entry Details</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('special-payroll.newly-hired.store') }}" id="newHireForm">
                @csrf

                {{-- Type Selection --}}
                <div class="form-group">
                    <label for="payroll_type">
                        Payroll Type <span style="color:var(--red);">*</span>
                    </label>
                    <select name="payroll_type" id="payroll_type"
                            class="{{ $errors->has('payroll_type') ? 'is-invalid' : '' }}"
                            onchange="updateFormLabels()" required>
                        <option value="">— Select Type —</option>
                        <option value="newly_hired" {{ old('payroll_type') == 'newly_hired' ? 'selected' : '' }}>
                            Newly Hired
                        </option>
                        <option value="transferee" {{ old('payroll_type') == 'transferee' ? 'selected' : '' }}>
                            Transferee
                        </option>
                        <option value="others" {{ old('payroll_type') == 'others' ? 'selected' : '' }}>
                            Others
                        </option>
                    </select>
                    @error('payroll_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Employee --}}
                <div class="form-group">
                    <label for="employee_id">
                        Employee <span style="color:var(--red);">*</span>
                    </label>
                    <select name="employee_id" id="employee_id"
                            class="{{ $errors->has('employee_id') ? 'is-invalid' : '' }}"
                            onchange="handleFormChange()" required>
                        <option value="">— Select Employee —</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}"
                                data-basic="{{ $emp->basic_salary }}"
                                data-pera="{{ $emp->pera }}"
                                data-position="{{ $emp->position_title }}"
                                {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->last_name }}, {{ $emp->first_name }}
                                @if ($emp->middle_name) {{ substr($emp->middle_name, 0, 1) }}. @endif
                                — {{ $emp->position_title ?? 'N/A' }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Assumption to Duty --}}
                <div class="form-group">
                    <label for="effectivity_date" id="effectivityLabel">
                        Assumption to Duty (First Day of Work) <span style="color:var(--red);">*</span>
                    </label>
                    <input type="date" id="effectivity_date" name="effectivity_date"
                           value="{{ old('effectivity_date') }}"
                           class="{{ $errors->has('effectivity_date') ? 'is-invalid' : '' }}"
                           onchange="handleFormChange()" required>
                    @error('effectivity_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Cut-off dates --}}
                <div class="sp-cutoff-row">
                    <div class="form-group">
                        <label for="cutoff_start">
                            Cut-off Start <span style="color:var(--red);">*</span>
                        </label>
                        <input type="date" id="cutoff_start" name="cutoff_start"
                               value="{{ old('cutoff_start') }}"
                               class="{{ $errors->has('cutoff_start') ? 'is-invalid' : '' }}"
                               onchange="handleFormChange()" required>
                        @error('cutoff_start')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="cutoff_end">
                            Cut-off End <span style="color:var(--red);">*</span>
                        </label>
                        <input type="date" id="cutoff_end" name="cutoff_end"
                               value="{{ old('cutoff_end') }}"
                               class="{{ $errors->has('cutoff_end') ? 'is-invalid' : '' }}"
                               onchange="handleFormChange()" required>
                        @error('cutoff_end')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- LWOP --}}
                <div class="form-group" style="max-width:220px;">
                    <label for="lwop_days">LWOP Days (Leave Without Pay)</label>
                    <input type="number" id="lwop_days" name="lwop_days"
                           value="{{ old('lwop_days', 0) }}"
                           min="0" max="22" step="1"
                           class="{{ $errors->has('lwop_days') ? 'is-invalid' : '' }}"
                           onchange="updatePreview()">
                    @error('lwop_days')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- PERA (manual override) --}}
                <div class="form-group" style="max-width:280px;">
                    <label for="pera_amount">PERA Earned (optional override)</label>
                    <input type="number" id="pera_amount" name="pera_amount"
                           value="{{ old('pera_amount') }}"
                           step="0.01" min="0"
                           placeholder="Auto: PERA ÷ 22 × working days"
                           class="{{ $errors->has('pera_amount') ? 'is-invalid' : '' }}"
                           onchange="updatePreview()">
                    <div style="font-size:0.72rem; color:var(--text-light); margin-top:4px;">
                        Leave blank to auto-compute from the employee's PERA record.
                        Enter a figure here to set it manually — e.g. when the
                        pro-rated amount doesn't match the ₱2,000 standard PERA cap.
                        Entering a value here replaces the auto-computed figure
                        exactly (no further pro-ration or LWOP adjustment is applied
                        to it).
                    </div>
                    @error('pera_amount')
                        <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Allowances (Optional) — Goal 1 --}}
                <div class="form-group" id="allowancesSection"
                     style="margin-top:16px; padding:16px; background:#F8F9FA; border-radius:8px; border:1px solid #E9ECEF;">
                    <label style="font-size:0.78rem; font-weight:700; color:var(--navy); margin-bottom:8px; display:block;">
                        Allowances <span class="text-muted">(optional — RATA, etc.)</span>
                    </label>
                    <div style="font-size:0.75rem; color:var(--text-mid); margin-bottom:12px;">
                        Each applicable allowance is pro-rated the same way as PERA
                        (Amount ÷ 22 × working days). Uncheck any that shouldn't
                        apply for this partial period (e.g. RATA where permanency
                        hasn't been established yet), or use Override to enter a
                        manual figure with a reason.
                    </div>
                    <div style="font-size:0.70rem; color:#B7791F; background:#FFF8E8; border:1px solid #F0DFAE; border-radius:6px; padding:6px 8px; margin-bottom:12px;">
                        <strong>Note:</strong> PERA is not listed here even if the
                        employee has an active PERA enrollment — it's already
                        pro-rated separately above as its own field, so including
                        it again in this list would double it. Only allowances
                        <em>other than PERA</em> (e.g. RATA) will appear.
                    </div>

                    <div id="allowancesEmpty" style="font-size:0.8rem; color:var(--text-light);">
                        — Select employee, effectivity date, and cut-off dates above to check applicable allowances —
                    </div>
                    <div id="allowancesList"></div>
                </div>

                {{-- Deduction Percentage Override (Optional) --}}
                <div class="form-group" style="margin-top:16px; padding:16px; background:#F8F9FA; border-radius:8px; border:1px solid #E9ECEF;">
                    <label style="font-size:0.78rem; font-weight:700; color:var(--navy); margin-bottom:12px; display:block;">
                        Deductions <span class="text-muted">(optional)</span>
                    </label>
                    <div style="font-size:0.75rem; color:var(--text-mid); margin-bottom:12px;">
                        By default this record is computed as full pro-rated gross —
                        no deductions applied. GSIS Personal Share only applies to
                        GSIS-covered appointees (permanent/regular); Job Order/COS
                        hires are not GSIS-covered, so leave this off for them.
                    </div>

                    <label style="display:flex; align-items:center; gap:8px; margin-bottom:12px; cursor:pointer;">
                        <input type="checkbox" id="apply_gsis" name="apply_gsis" value="1"
                               {{ old('apply_gsis') ? 'checked' : '' }}
                               onchange="toggleGsisFields(); updatePreview();">
                        <span style="font-size:0.82rem; font-weight:600;">
                            Apply GSIS Personal Share deduction (employee is GSIS-covered)
                        </span>
                    </label>

                    <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                        <div>
                            <label style="font-size:0.73rem; margin-bottom:4px; display:block;">GSIS PS %</label>
                            <input type="number" id="deduction_gsis_percent" name="deduction_gsis_percent"
                                   value="{{ old('deduction_gsis_percent') }}"
                                   step="0.01" min="0" max="100" placeholder="9.00"
                                   class="{{ $errors->has('deduction_gsis_percent') ? 'is-invalid' : '' }}"
                                   style="width:100%; padding:6px 10px; border:1px solid var(--border); border-radius:6px; font-size:0.85rem;"
                                   {{ old('apply_gsis') ? '' : 'disabled' }} onchange="updatePreview()">
                            @error('deduction_gsis_percent')
                                <div class="invalid-feedback" style="display:block; font-size:0.73rem;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label style="font-size:0.73rem; margin-bottom:4px; display:block;">PhilHealth %</label>
                            <input type="number" name="deduction_philhealth_percent"
                                   value="{{ old('deduction_philhealth_percent') }}"
                                   step="0.01" min="0" max="100" placeholder="0.00"
                                   class="{{ $errors->has('deduction_philhealth_percent') ? 'is-invalid' : '' }}"
                                   style="width:100%; padding:6px 10px; border:1px solid var(--border); border-radius:6px; font-size:0.85rem;" disabled>
                            @error('deduction_philhealth_percent')
                                <div class="invalid-feedback" style="display:block; font-size:0.73rem;">{{ $message }}</div>
                            @enderror
                            <div style="font-size:0.70rem; color:var(--text-light); margin-top:2px;">Not deducted for newly hired</div>
                        </div>
                        <div>
                            <label style="font-size:0.73rem; margin-bottom:4px; display:block;">Pag-IBIG Amount</label>
                            <input type="number" name="deduction_pagibig_amount"
                                   value="{{ old('deduction_pagibig_amount') }}"
                                   step="0.01" min="0" placeholder="0.00"
                                   class="{{ $errors->has('deduction_pagibig_amount') ? 'is-invalid' : '' }}"
                                   style="width:100%; padding:6px 10px; border:1px solid var(--border); border-radius:6px; font-size:0.85rem;" disabled>
                            @error('deduction_pagibig_amount')
                                <div class="invalid-feedback" style="display:block; font-size:0.73rem;">{{ $message }}</div>
                            @enderror
                            <div style="font-size:0.70rem; color:var(--text-light); margin-top:2px;">Government share only</div>
                        </div>
                        <div>
                            <label style="font-size:0.73rem; margin-bottom:4px; display:block;">WHT %</label>
                            <input type="number" name="deduction_wht_percent"
                                   value="{{ old('deduction_wht_percent') }}"
                                   step="0.01" min="0" max="100" placeholder="0.00"
                                   class="{{ $errors->has('deduction_wht_percent') ? 'is-invalid' : '' }}"
                                   style="width:100%; padding:6px 10px; border:1px solid var(--border); border-radius:6px; font-size:0.85rem;" disabled>
                            @error('deduction_wht_percent')
                                <div class="invalid-feedback" style="display:block; font-size:0.73rem;">{{ $message }}</div>
                            @enderror
                            <div style="font-size:0.70rem; color:var(--text-light); margin-top:2px;">Annualized (no history)</div>
                        </div>
                    </div>
                </div>

                {{-- Remarks --}}
                <div class="form-group">
                    <label for="remarks">Remarks <span class="text-muted">(optional)</span></label>
                    <textarea id="remarks" name="remarks" rows="2"
                              placeholder="e.g. Transferred from DOLE RO10, appointment no., etc."
                              class="{{ $errors->has('remarks') ? 'is-invalid' : '' }}"
                              style="width:100%; resize:vertical;">{{ old('remarks') }}</textarea>
                    @error('remarks')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2 sp-action-row" style="margin-top:24px;">
                    <button type="submit" class="btn btn-primary btn-lg">
                        ⚙ Compute &amp; Save
                    </button>
                    <a href="{{ route('special-payroll.newly-hired.index') }}"
                       class="btn btn-outline btn-lg">Cancel</a>
                </div>

            </form>
        </div>
    </div>

    {{-- ── Right: Live Preview + Formula Reference ── --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Live Preview --}}
        <div class="card">
            <div class="card-header">
                <h3>🔍 Live Estimate</h3>
            </div>
            <div class="card-body" id="previewBox" style="font-size:0.85rem;">
                <div style="text-align:center; color:var(--text-light); padding:16px 0;" id="previewEmpty">
                    Fill in the form to see a live estimate.
                </div>
                <div id="previewContent" style="display:none;">
                    <div class="stat-grid" style="grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
                        <div class="stat-card">
                            <div class="stat-label">Working Days</div>
                            <div class="stat-value" id="prev-days" style="font-size:1.4rem;">—</div>
                        </div>
                        <div class="stat-card gold">
                            <div class="stat-label">Net Amount</div>
                            <div class="stat-value" id="prev-net" style="font-size:1.1rem;">—</div>
                        </div>
                    </div>
                    <table style="width:100%; font-size:0.80rem; border-collapse:collapse;">
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:4px 0; color:var(--text-light);">Basic Salary</td>
                            <td style="text-align:right;" id="prev-basic">—</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:4px 0; color:var(--text-light);">Salary Earned</td>
                            <td style="text-align:right; font-weight:600;" id="prev-salary">—</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:4px 0; color:var(--text-light);" id="prev-pera-label">PERA Earned</td>
                            <td style="text-align:right; font-weight:600;" id="prev-pera">—</td>
                        </tr>
                        <tr id="prev-allowances-row" style="border-bottom:1px solid var(--border); display:none;">
                            <td style="padding:4px 0; color:var(--text-light);">Allowances Earned</td>
                            <td style="text-align:right; font-weight:600;" id="prev-allowances">—</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:4px 0; color:var(--text-light);">Total Earned</td>
                            <td style="text-align:right; font-weight:700;" id="prev-earned">—</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:4px 0; color:#B71C1C;" id="prev-gsis-label">GSIS PS</td>
                            <td style="text-align:right; color:#B71C1C;" id="prev-gsis">—</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0 0; font-weight:700; color:var(--navy);">Net Amount</td>
                            <td style="text-align:right; font-weight:700; color:var(--navy);" id="prev-net2">—</td>
                        </tr>
                    </table>
                    <div id="prev-lwop-row" style="display:none; margin-top:8px;
                         font-size:0.78rem; color:#B71C1C; border-top:1px solid var(--border); padding-top:6px;">
                        ⚠ LWOP deduction applied: <span id="prev-lwop-amt"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formula Reference --}}
        <div class="card">
            <div class="card-header">
                <h3>📐 Formula Reference</h3>
            </div>
            <div class="card-body" style="font-size:0.84rem; color:var(--text-mid); line-height:1.8;">
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <div>
                        <span class="fw-bold text-navy">Working Days</span><br>
                        Weekdays only, from effectivity date to cut-off end (inclusive)
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">Salary Earned</span><br>
                        ROUND(Basic ÷ 22 × working days, 2)
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">PERA Earned</span><br>
                        ROUND(PERA ÷ 22 × working days, 2) — or the manually
                        entered PERA Earned amount, if set
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">GSIS PS</span><br>
                        ROUND(Basic ÷ 22 × calendar days × rate, 2) — calendar
                        days from effectivity date to cut-off end, inclusive of
                        Sundays (not the weekday-only Working Days figure above)
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">PhilHealth / Pag-IBIG</span><br>
                        Government share only — <em>not deducted</em> from net
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">Withholding Tax</span><br>
                        ₱0 — annualized, no history yet for newly hired
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2/dist/js/tom-select.complete.min.js"></script>
<script>
const allowancesPreviewUrl = "{{ route('special-payroll.newly-hired.allowances-preview') }}";

// Repopulate the dynamically-rendered checklist after a validation failure
// (e.g. a missing override reason) — the checklist itself isn't server-
// rendered, so old() values have to be threaded through manually here.
const oldSelectedAllowances = @json(array_map('intval', old('allowances', [])));
const oldAllowanceOverrides = @json(old('allowance_override', []));
const oldAllowanceOverrideReasons = @json(old('allowance_override_reason', []));

// Cache of the last-resolved allowance lines (full monthly amounts, pre-proration)
// from the server. Re-fetched only when employee/effectivity/cutoff change;
// checkbox toggles and override edits recompute totals from this cache without
// another round-trip.
let resolvedAllowances = [];
let allowanceFetchToken = 0; // guards against out-of-order responses

function countWeekdays(start, end) {
    let count = 0;
    const cur = new Date(start);
    const last = new Date(end);
    while (cur <= last) {
        const dow = cur.getDay();
        if (dow !== 0 && dow !== 6) count++;
        cur.setDate(cur.getDate() + 1);
    }
    return count;
}

// Calendar days (inclusive, Sundays counted) — used only for the GSIS PS
// base, which is pro-rated on time-in-service rather than the weekday-only
// Working Days figure. Mirrors NewlyHiredPayrollService::calendarDays().
function countCalendarDays(start, end) {
    const s = new Date(start);
    const e = new Date(end);
    return Math.round((e - s) / 86400000) + 1;
}

function fmt(n) {
    return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function roundPHP(val) {
    return Math.round(val * 100) / 100;
}

function fmtPlain(n) {
    return Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/**
 * Current working-days figure using the same logic as updatePreview() —
 * kept in sync manually since this is a lightweight client-side estimate,
 * not the source of truth (the server recomputes via
 * NewlyHiredPayrollService::workingDays() on submit).
 */
function currentWorkingDays() {
    const effDate = document.getElementById('effectivity_date').value;
    const coStart = document.getElementById('cutoff_start').value;
    const coEnd   = document.getElementById('cutoff_end').value;
    if (!effDate || !coStart || !coEnd) return 0;
    const eff = effDate > coStart ? effDate : coStart;
    return coEnd >= eff ? countWeekdays(eff, coEnd) : 0;
}

/**
 * Fetch applicable allowance lines for the currently selected employee +
 * period from the server (AllowanceService::resolveForPeriod(), same
 * precedence logic the regular payroll module uses). Triggered whenever
 * employee_id, effectivity_date, cutoff_start, or cutoff_end changes.
 */
function fetchAllowances() {
    const employeeId = document.getElementById('employee_id').value;
    const effDate     = document.getElementById('effectivity_date').value;
    const coStart     = document.getElementById('cutoff_start').value;
    const coEnd       = document.getElementById('cutoff_end').value;

    const emptyMsg = document.getElementById('allowancesEmpty');
    const listEl   = document.getElementById('allowancesList');

    if (!employeeId || !effDate || !coStart || !coEnd) {
        resolvedAllowances = [];
        emptyMsg.style.display = 'block';
        listEl.innerHTML = '';
        return;
    }

    const token = ++allowanceFetchToken;
    const params = new URLSearchParams({
        employee_id: employeeId,
        effectivity_date: effDate,
        cutoff_start: coStart,
        cutoff_end: coEnd,
    });

    fetch(`${allowancesPreviewUrl}?${params.toString()}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => r.json())
        .then(data => {
            if (token !== allowanceFetchToken) return; // a newer request superseded this one
            resolvedAllowances = data.allowances || [];
            renderAllowances();
        })
        .catch(() => {
            if (token !== allowanceFetchToken) return;
            resolvedAllowances = [];
            emptyMsg.textContent = 'Could not load allowances — you can still submit without them.';
            emptyMsg.style.display = 'block';
            listEl.innerHTML = '';
        });
}

/**
 * Render the allowance checklist from resolvedAllowances. Every line
 * defaults to checked. Each row shows the full monthly amount and the
 * pro-rated amount (recomputed live as dates/days change), plus an
 * Override toggle for a manual figure + mandatory reason.
 */
function renderAllowances() {
    const emptyMsg = document.getElementById('allowancesEmpty');
    const listEl   = document.getElementById('allowancesList');

    if (!resolvedAllowances.length) {
        emptyMsg.textContent = 'No standing or assigned allowances found for this employee/period.';
        emptyMsg.style.display = 'block';
        listEl.innerHTML = '';
        return;
    }

    emptyMsg.style.display = 'none';

    const days = currentWorkingDays();

    listEl.innerHTML = resolvedAllowances.map(line => {
        const prorated = roundPHP((line.amount / 22) * days);
        return `
        <div class="allowance-row" data-type-id="${line.allowance_type_id}"
             data-full="${line.amount}"
             style="padding:8px 0; border-bottom:1px solid var(--border);">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                <input type="checkbox" name="allowances[]" value="${line.allowance_type_id}"
                       class="allowance-checkbox" onchange="updatePreview()">
                <div style="flex:1;">
                    <div style="font-weight:600; font-size:0.82rem;">${line.name}</div>
                    <div style="font-size:0.72rem; color:var(--text-light);">
                        Full: ${fmt(line.amount)} · Pro-rated (${days}d): <span class="prorated-amt">${fmt(prorated)}</span>
                    </div>
                </div>
                <button type="button" class="btn btn-outline btn-sm" style="padding:2px 8px; font-size:0.72rem;"
                        onclick="event.preventDefault(); toggleAllowanceOverride(${line.allowance_type_id})">
                    Override
                </button>
            </label>
            <div class="allowance-override-fields" id="override-fields-${line.allowance_type_id}"
                 style="display:none; margin:8px 0 0 26px; display:grid; grid-template-columns:140px 1fr; gap:8px;">
                <input type="number" step="0.01" min="0"
                       name="allowance_override[${line.allowance_type_id}]"
                       placeholder="Override amount"
                       oninput="updatePreview()"
                       style="padding:5px 8px; border:1px solid var(--border); border-radius:6px; font-size:0.78rem;">
                <input type="text"
                       name="allowance_override_reason[${line.allowance_type_id}]"
                       placeholder="Reason (required if overriding)"
                       style="padding:5px 8px; border:1px solid var(--border); border-radius:6px; font-size:0.78rem;">
            </div>
        </div>`;
    }).join('');

    // Re-apply old() selections/overrides after a validation-error redirect.
    if (oldSelectedAllowances.length || Object.keys(oldAllowanceOverrides).length) {
        resolvedAllowances.forEach(line => {
            const typeId = line.allowance_type_id;
            const row = listEl.querySelector(`.allowance-row[data-type-id="${typeId}"]`);
            if (!row) return;

            const checkbox = row.querySelector('.allowance-checkbox');
            checkbox.checked = oldSelectedAllowances.length
                ? oldSelectedAllowances.includes(typeId)
                : false;

            const overrideVal = oldAllowanceOverrides[typeId];
            if (overrideVal !== undefined && overrideVal !== null && overrideVal !== '') {
                const fields = document.getElementById(`override-fields-${typeId}`);
                fields.style.display = 'grid';
                fields.querySelector(`input[name="allowance_override[${typeId}]"]`).value = overrideVal;
                const reasonVal = oldAllowanceOverrideReasons[typeId] || '';
                fields.querySelector(`input[name="allowance_override_reason[${typeId}]"]`).value = reasonVal;
            }
        });
    }

    updatePreview();
}

function toggleAllowanceOverride(typeId) {
    const el = document.getElementById(`override-fields-${typeId}`);
    if (!el) return;
    const showing = el.style.display !== 'none';
    el.style.display = showing ? 'none' : 'grid';
    if (showing) {
        // Clearing an override falls back to the pro-rated amount.
        el.querySelectorAll('input').forEach(i => i.value = '');
    }
    updatePreview();
}

/**
 * Sum the amount that will actually be submitted for checked allowances —
 * the override value when present, otherwise the pro-rated amount.
 * Mirrors AllowanceService::proRateLines() + the override handling in
 * SpecialPayrollController::newHireStore(), so the live estimate matches
 * what the server will compute.
 */
function getAllowancesEarnedTotal() {
    const days = currentWorkingDays();
    let total = 0;

    document.querySelectorAll('.allowance-row').forEach(row => {
        const checkbox = row.querySelector('.allowance-checkbox');
        if (!checkbox.checked) return;

        const typeId       = row.dataset.typeId;
        const fullAmount   = parseFloat(row.dataset.full) || 0;
        const overrideEl   = row.querySelector(`input[name="allowance_override[${typeId}]"]`);
        const overrideVal  = overrideEl && overrideEl.value !== '' ? parseFloat(overrideEl.value) : null;

        total += (overrideVal !== null && !isNaN(overrideVal))
            ? overrideVal
            : roundPHP((fullAmount / 22) * days);
    });

    return roundPHP(total);
}

function handleFormChange() {
    fetchAllowances(); // will call renderAllowances() -> updatePreview() once resolved
    updatePreview();   // also update immediately using the previous allowance cache
}

/**
 * Enable/disable the GSIS % input to match the apply_gsis checkbox.
 * Disabling (rather than just ignoring) means a disabled field's value
 * won't even be submitted, so there's no ambiguity server-side about
 * whether a leftover percent value should count.
 */
function toggleGsisFields() {
    const checked = document.getElementById('apply_gsis').checked;
    const percentField = document.getElementById('deduction_gsis_percent');
    percentField.disabled = !checked;
    if (!checked) {
        percentField.value = '';
    }
}

function updatePreview() {
    const empEl   = document.getElementById('employee_id');
    const effDate = document.getElementById('effectivity_date').value;
    const coStart = document.getElementById('cutoff_start').value;
    const coEnd   = document.getElementById('cutoff_end').value;
    const lwop    = parseInt(document.getElementById('lwop_days').value) || 0;

    const selOpt  = empEl.options[empEl.selectedIndex];
    const basic   = parseFloat(selOpt?.dataset.basic) || 0;
    const pera    = parseFloat(selOpt?.dataset.pera)  || 0;

    if (!basic || !effDate || !coEnd) {
        document.getElementById('previewEmpty').style.display  = 'block';
        document.getElementById('previewContent').style.display = 'none';
        return;
    }

    // Working days: from max(effectivity, cutoff_start) to cutoff_end
    const eff   = effDate > coStart ? effDate : coStart;
    const days  = coEnd >= eff ? countWeekdays(eff, coEnd) : 0;

    // Calendar days (Sundays included) — GSIS PS base only, see countCalendarDays().
    const calDays = coEnd >= eff ? countCalendarDays(eff, coEnd) : 0;

    const salaryEarned = roundPHP((basic / 22) * days);

    // PERA Earned — manual override takes priority over the auto-computed
    // pro-rated figure. When overridden, LWOP is not re-applied to it: the
    // entered amount is treated as the final earned figure.
    const peraOverrideEl  = document.getElementById('pera_amount');
    const peraOverrideVal = peraOverrideEl && peraOverrideEl.value !== ''
        ? parseFloat(peraOverrideEl.value)
        : null;
    const peraOverridden = peraOverrideVal !== null && !isNaN(peraOverrideVal);
    const peraEarned = peraOverridden
        ? roundPHP(peraOverrideVal)
        : roundPHP((pera / 22) * days);

    const lwopSalary   = roundPHP(roundPHP(basic / 22) * lwop);
    const lwopPera     = peraOverridden ? 0 : roundPHP(roundPHP(pera / 22) * lwop);
    const lwopTotal    = roundPHP(lwopSalary + lwopPera);

    // Allowances are never GSIS-able (same treatment as PERA) — they're
    // added to net_earned only, after GSIS's base (salaryEarned) is fixed.
    const allowancesEarned = getAllowancesEarnedTotal();

    const netEarned = roundPHP((salaryEarned - lwopSalary) + (peraEarned - lwopPera) + allowancesEarned);

    // GSIS is opt-in (off by default) — only GSIS-covered appointees get it.
    // Rate mirrors NewlyHiredPayrollService::GSIS_EMPLOYEE_RATE (9%) unless
    // the preparer entered a custom percent. Base is calendar days (Sundays
    // included), not the weekday-only Working Days figure used for salary —
    // matches NewlyHiredPayrollService::compute()'s gsis_base.
    const applyGsis   = document.getElementById('apply_gsis')?.checked || false;
    const gsisPercent = applyGsis
        ? (parseFloat(document.getElementById('deduction_gsis_percent').value) || 9.00)
        : 0;
    const gsisBase = roundPHP((basic / 22) * calDays);
    const gsisPS   = applyGsis ? roundPHP(gsisBase * (gsisPercent / 100)) : 0;
    const net      = roundPHP(netEarned - gsisPS);

    // Show preview
    document.getElementById('previewEmpty').style.display   = 'none';
    document.getElementById('previewContent').style.display = 'block';

    document.getElementById('prev-days').textContent   = days + ' days';
    document.getElementById('prev-basic').textContent  = fmt(basic);
    document.getElementById('prev-salary').textContent = fmt(salaryEarned);
    document.getElementById('prev-pera').textContent   = fmt(peraEarned);
    document.getElementById('prev-pera-label').textContent = peraOverridden ? 'PERA Earned (manual)' : 'PERA Earned';

    const allowRow = document.getElementById('prev-allowances-row');
    if (allowancesEarned > 0) {
        allowRow.style.display = 'table-row';
        document.getElementById('prev-allowances').textContent = fmt(allowancesEarned);
    } else {
        allowRow.style.display = 'none';
    }

    document.getElementById('prev-earned').textContent = fmt(netEarned);
    document.getElementById('prev-gsis-label').textContent = applyGsis
        ? `GSIS PS (${gsisPercent.toFixed(2)}%)`
        : 'GSIS PS (not applied)';
    document.getElementById('prev-gsis').textContent   = applyGsis ? ('−' + fmt(gsisPS)) : '₱0.00';
    document.getElementById('prev-net').textContent    = fmt(net);
    document.getElementById('prev-net2').textContent   = fmt(net);

    const lwopRow = document.getElementById('prev-lwop-row');
    if (lwop > 0) {
        lwopRow.style.display = 'block';
        document.getElementById('prev-lwop-amt').textContent = fmt(lwopTotal);
    } else {
        lwopRow.style.display = 'none';
    }
}

function updateFormLabels() {
    const type = document.getElementById('payroll_type').value;
    const pageTitle = document.getElementById('pageTitle');
    const pageDescription = document.getElementById('pageDescription');
    const effectivityLabel = document.getElementById('effectivityLabel');

    if (type === 'transferee') {
        pageTitle.textContent = 'Pro-Rated Payroll — Transferee';
        pageDescription.textContent = 'Compute pro-rated salary for an employee who transferred mid-period.';
        effectivityLabel.innerHTML = 'Assumption to Duty (First Day in New Office) <span style="color:var(--red);">*</span>';
    } else if (type === 'others') {
        pageTitle.textContent = 'Pro-Rated Payroll — Others';
        pageDescription.textContent = 'Compute special pro-rated payroll for other qualifying cases.';
        effectivityLabel.innerHTML = 'Assumption to Duty <span style="color:var(--red);">*</span>';
    } else if (type === 'newly_hired') {
        pageTitle.textContent = 'Pro-Rated Payroll — Newly Hired';
        pageDescription.textContent = 'Compute pro-rated salary for an employee who started mid-period.';
        effectivityLabel.innerHTML = 'Assumption to Duty (First Day of Work) <span style="color:var(--red);">*</span>';
    } else {
        pageTitle.textContent = 'Pro-Rated Payroll — Newly Hired / Transferee';
        pageDescription.textContent = 'Compute pro-rated salary for an employee who started mid-period.';
        effectivityLabel.innerHTML = 'Assumption to Duty (First Day of Work) <span style="color:var(--red);">*</span>';
    }
}

// Searchable/typeable Employee dropdown — swaps the plain <select> for a
// type-to-filter control. Tom Select keeps the original #employee_id select
// element in sync (including data-basic/data-pera/data-position attributes
// and selectedIndex) and dispatches a normal 'change' event on it, so
// handleFormChange()/updatePreview() above keep working unmodified.
new TomSelect('#employee_id', {
    placeholder: '— Select Employee —',
    allowEmptyOption: true,
    sortField: { field: 'text', direction: 'asc' },
    maxOptions: 500,
});

// Init on page load in case old() repopulates fields
toggleGsisFields();
fetchAllowances();
updatePreview();
updateFormLabels();

// Safety net: GSIS off is the default, but it should be a deliberate choice
// for a GSIS-covered appointee, not something left unchecked by accident.
document.getElementById('newHireForm').addEventListener('submit', function (e) {
    const applyGsis = document.getElementById('apply_gsis').checked;
    if (!applyGsis) {
        const confirmed = confirm(
            'No GSIS deduction will be applied — this record will be treated ' +
            'as a non-GSIS-covered hire (e.g. Job Order/COS).\n\n' +
            'If this employee IS a permanent/regular GSIS-covered appointee, ' +
            'cancel and check "Apply GSIS Personal Share deduction" first.\n\n' +
            'Continue without GSIS deduction?'
        );
        if (!confirmed) {
            e.preventDefault();
        }
    }
});
</script>
@endsection
