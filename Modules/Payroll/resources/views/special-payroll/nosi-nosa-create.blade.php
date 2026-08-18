{{-- resources/views/special-payroll/nosi-nosa-create.blade.php --}}
{{--
    Expects from SpecialPayrollController@nosiNosaCreate:
      $employees — collection of active Employee models
--}}

@extends('layouts.app')

@section('title', 'NOSI / NOSA — New Entry')
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
    grid-template-columns: 1fr 380px;
    gap: 24px;
    align-items: start;
}
.sp-date-row,
.sp-salary-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.sp-nosi-info-grid {
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
    .sp-salary-row,
    .sp-nosi-info-grid {
        grid-template-columns: 1fr;
        gap: 0;
    }
    .sp-nosi-info-grid {
        gap: 12px;
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
}
</style>
@endsection

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>NOSI / NOSA — New Entry</h1>
        <p>Notice of Salary Increase or Notice of Salary Adjustment back pay computation.</p>
    </div>
    <a href="{{ route('special-payroll.nosi-nosa.index') }}" class="btn btn-outline btn-sm">
        ← Back to Records
    </a>
</div>

{{-- Type explanation --}}
<div class="alert alert-info mb-3">
    <div class="sp-nosi-info-grid">
        <div>
            <strong>📋 NOSI — Notice of Salary Increase</strong><br>
            <span style="font-size:0.83rem;">
                Merit-based or step increment ordered by the President / CSC.
                Covers a specific effectivity date range with retroactive back pay.
            </span>
        </div>
        <div>
            <strong>📋 NOSA — Notice of Salary Adjustment</strong><br>
            <span style="font-size:0.83rem;">
                Salary standardization or restructuring adjustment.
                May cover all employees or a group for a long date range (e.g. full year).
            </span>
        </div>
    </div>
    <div style="margin-top:10px; font-size:0.82rem; border-top:1px solid var(--border); padding-top:10px;">
        <strong>Formula (same as Salary Differential):</strong>
        Differential = New Rate − Old Rate.
        Partial months: <strong>ROUND(Differential × Days / 22, 2)</strong>.
        Full months: Differential.
        Deductions: GSIS PS (9%), PhilHealth (2.5%), Pag-IBIG (₱200/mo), WHT (20% default).
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-error mb-3">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="sp-create-grid">

    {{-- ── Main Form ── --}}
    <div class="card">
        <div class="card-header">
            <h3>📋 Entry Details</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('special-payroll.nosi-nosa.store') }}" id="nosiForm">
                @csrf

                {{-- Type --}}
                <div class="form-group">
                    <label for="type">
                        Type <span style="color:var(--red);">*</span>
                    </label>
                    <select name="type" id="type"
                            class="{{ $errors->has('type') ? 'is-invalid' : '' }}"
                            required>
                        <option value="">— Select Type —</option>
                        <option value="nosi" {{ old('type') === 'nosi' ? 'selected' : '' }}>
                            NOSI — Notice of Salary Increase
                        </option>
                        <option value="nosa" {{ old('type') === 'nosa' ? 'selected' : '' }}>
                            NOSA — Notice of Salary Adjustment
                        </option>
                    </select>
                    @error('type')
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
                            required>
                        <option value="">— Select Employee —</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}"
                                data-basic="{{ $emp->basic_salary }}"
                                data-position="{{ $emp->position_title }}"
                                data-wht="0.20"
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

                {{-- Effectivity date range --}}
                <div class="sp-date-row">
                    <div class="form-group">
                        <label for="effectivity_date_from">
                            Effectivity Date — From <span style="color:var(--red);">*</span>
                        </label>
                        <input type="date" id="effectivity_date_from" name="effectivity_date_from"
                               value="{{ old('effectivity_date_from') }}"
                               class="{{ $errors->has('effectivity_date_from') ? 'is-invalid' : '' }}"
                               required>
                        @error('effectivity_date_from')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="effectivity_date_to">
                            Effectivity Date — To <span style="color:var(--red);">*</span>
                        </label>
                        <input type="date" id="effectivity_date_to" name="effectivity_date_to"
                               value="{{ old('effectivity_date_to') }}"
                               class="{{ $errors->has('effectivity_date_to') ? 'is-invalid' : '' }}"
                               required>
                        @error('effectivity_date_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Old / New salary --}}
                <div class="sp-salary-row">
                    <div class="form-group">
                        <label for="old_salary">
                            Old Salary (Monthly Rate) <span style="color:var(--red);">*</span>
                        </label>
                        <input type="number" id="old_salary" name="old_salary"
                               value="{{ old('old_salary') }}"
                               step="0.01" min="0"
                               placeholder="Select an employee first"
                               class="{{ $errors->has('old_salary') ? 'is-invalid' : '' }}"
                               required>
                        @error('old_salary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div style="font-size:0.75rem; color:var(--text-light); margin-top:4px;">
                            Auto-filled from employee's current basic salary. Override if needed.
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_salary">
                            New Salary (Monthly Rate) <span style="color:var(--red);">*</span>
                        </label>
                        <input type="number" id="new_salary" name="new_salary"
                               value="{{ old('new_salary') }}"
                               step="0.01" min="0"
                               placeholder="e.g. 52864"
                               class="{{ $errors->has('new_salary') ? 'is-invalid' : '' }}"
                               required>
                        @error('new_salary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        {{-- Differential badge / inline warning --}}
                        <div id="diff-badge" style="display:none; margin-top:6px; font-size:0.80rem; font-weight:700;">
                            Differential: <span id="diff-val">—</span>
                        </div>
                        <div id="new-salary-warn" style="display:none; margin-top:6px; font-size:0.78rem;
                             color:#B71C1C; background:#FFF5F5; border:1px solid #FFCDD2;
                             border-radius:6px; padding:6px 10px;">
                            ⚠ New salary must be <strong>greater than</strong> the old salary
                            (₱<span id="warn-old-val">0.00</span>). This entry cannot be saved as-is.
                        </div>
                    </div>
                </div>

                {{-- Deduction Percentages (Optional Override) --}}
                <div class="form-group" style="margin-top:16px; padding:16px; background:#F8F9FA; border-radius:8px; border:1px solid #E9ECEF;">
                    <label style="font-size:0.78rem; font-weight:700; color:var(--navy); margin-bottom:12px; display:block;">
                        Deduction Percentage Overrides <span class="text-muted">(optional)</span>
                    </label>
                    <div style="font-size:0.75rem; color:var(--text-mid); margin-bottom:12px;">
                        Leave blank to use default rates: GSIS PS (9%), PhilHealth (2.5%), Pag-IBIG (fixed ₱200), WHT (20%).
                    </div>
                    <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                        <div>
                            <label style="font-size:0.73rem; margin-bottom:4px; display:block;">GSIS PS %</label>
                            <input type="number" name="deduction_gsis_percent"
                                   value="{{ old('deduction_gsis_percent') }}"
                                   step="0.01" min="0" max="100" placeholder="9.00"
                                   class="{{ $errors->has('deduction_gsis_percent') ? 'is-invalid' : '' }}"
                                   style="width:100%; padding:6px 10px; border:1px solid var(--border); border-radius:6px; font-size:0.85rem;">
                            @error('deduction_gsis_percent')
                                <div class="invalid-feedback" style="display:block; font-size:0.73rem;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label style="font-size:0.73rem; margin-bottom:4px; display:block;">PhilHealth %</label>
                            <input type="number" name="deduction_philhealth_percent"
                                   value="{{ old('deduction_philhealth_percent') }}"
                                   step="0.01" min="0" max="100" placeholder="2.50"
                                   class="{{ $errors->has('deduction_philhealth_percent') ? 'is-invalid' : '' }}"
                                   style="width:100%; padding:6px 10px; border:1px solid var(--border); border-radius:6px; font-size:0.85rem;">
                            @error('deduction_philhealth_percent')
                                <div class="invalid-feedback" style="display:block; font-size:0.73rem;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label style="font-size:0.73rem; margin-bottom:4px; display:block;">Pag-IBIG Amount</label>
                            <input type="number" name="deduction_pagibig_amount"
                                   value="{{ old('deduction_pagibig_amount') }}"
                                   step="0.01" min="0" placeholder="200.00"
                                   class="{{ $errors->has('deduction_pagibig_amount') ? 'is-invalid' : '' }}"
                                   style="width:100%; padding:6px 10px; border:1px solid var(--border); border-radius:6px; font-size:0.85rem;">
                            @error('deduction_pagibig_amount')
                                <div class="invalid-feedback" style="display:block; font-size:0.73rem;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label style="font-size:0.73rem; margin-bottom:4px; display:block;">WHT %</label>
                            <input type="number" name="deduction_wht_percent"
                                   value="{{ old('deduction_wht_percent') }}"
                                   step="0.01" min="0" max="100" placeholder="20.00"
                                   class="{{ $errors->has('deduction_wht_percent') ? 'is-invalid' : '' }}"
                                   style="width:100%; padding:6px 10px; border:1px solid var(--border); border-radius:6px; font-size:0.85rem;">
                            @error('deduction_wht_percent')
                                <div class="invalid-feedback" style="display:block; font-size:0.73rem;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Remarks --}}
                <div class="form-group">
                    <label for="remarks">Remarks <span class="text-muted">(optional)</span></label>
                    <textarea id="remarks" name="remarks" rows="2"
                              placeholder="e.g. Per EO No. 64 s. 2024, effective January 1, 2024..."
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
                    <a href="{{ route('special-payroll.nosi-nosa.index') }}"
                       class="btn btn-outline btn-lg">Cancel</a>
                </div>

            </form>
        </div>
    </div>

    {{-- ── Right: Live Estimate + Formula Reference ── --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

        <div class="card">
            <div class="card-header">
                <h3>🔍 Live Estimate</h3>
            </div>
            <div class="card-body" style="font-size:0.85rem;">
                <div style="text-align:center; color:var(--text-light); padding:16px 0;" id="previewEmpty">
                    Fill in the form to see a live estimate.
                </div>
                <div id="previewContent" style="display:none;">
                    <table style="width:100%; font-size:0.80rem; border-collapse:collapse;">
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:5px 0; color:var(--text-light);">Differential / mo.</td>
                            <td style="text-align:right; font-weight:600;" id="prev-diff">—</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:5px 0; color:var(--text-light);">Months</td>
                            <td style="text-align:right;" id="prev-months-count">—</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:5px 0; color:var(--text-light);">Total Earned</td>
                            <td style="text-align:right; font-weight:700;" id="prev-earned">—</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:5px 0; color:#B71C1C;">GSIS PS (9%)</td>
                            <td style="text-align:right; color:#B71C1C;" id="prev-gsis">—</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:5px 0; color:#B71C1C;">PhilHealth (2.5%)</td>
                            <td style="text-align:right; color:#B71C1C;" id="prev-phic">—</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:5px 0; color:#B71C1C;">Pag-IBIG (×mo.)</td>
                            <td style="text-align:right; color:#B71C1C;" id="prev-pagibig">—</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:5px 0; color:#B71C1C;">WHT (<span id="prev-wht-rate">20</span>%)</td>
                            <td style="text-align:right; color:#B71C1C;" id="prev-wht">—</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:5px 0; color:#B71C1C; font-weight:600;">Total Deductions</td>
                            <td style="text-align:right; color:#B71C1C; font-weight:700;" id="prev-deduct">—</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0 0; font-weight:700; color:var(--navy);">Net Amount</td>
                            <td style="text-align:right; font-weight:700; color:var(--navy); font-size:1.05rem;" id="prev-net">—</td>
                        </tr>
                    </table>

                    <div id="prev-months-wrap" style="margin-top:12px; display:none;">
                        <div style="font-size:0.72rem; font-weight:700; letter-spacing:0.04em;
                             text-transform:uppercase; color:var(--text-light); margin-bottom:6px;">
                            Per-Month Breakdown (estimate)
                        </div>
                        <table style="width:100%; font-size:0.75rem; border-collapse:collapse;" id="prev-months-table">
                            <thead>
                                <tr style="background:var(--navy); color:#fff;">
                                    <th style="padding:4px 6px; text-align:left;">Month</th>
                                    <th style="padding:4px 6px; text-align:center;">Days</th>
                                    <th style="padding:4px 6px; text-align:right;">Earned</th>
                                </tr>
                            </thead>
                            <tbody id="prev-months-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>📐 Formula Reference</h3>
            </div>
            <div class="card-body" style="font-size:0.78rem; color:var(--text-mid); line-height:1.7;">
                <strong>Differential</strong> = New Rate − Old Rate<br>
                <strong>Partial month</strong> = ROUND(Diff × Days / 22, 2)<br>
                <strong>Full month</strong> = Differential<br>
                <strong>GSIS PS</strong> = 9% × monthly earned<br>
                <strong>PhilHealth</strong> = 2.5% × monthly earned<br>
                <strong>Pag-IBIG</strong> = ₱200.00 per month (fixed)<br>
                <strong>WHT</strong> = 20% × total earned (default)<br>
                <strong>Net</strong> = Total Earned − All Deductions<br>
                <br>
                <span style="color:var(--navy); font-weight:600;">
                    NOSI/NOSA tip:
                </span>
                For a full-year range (Jan 1 – Dec 31), all 12 months
                will be full months = 12 × differential.
            </div>
        </div>

    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2/dist/js/tom-select.complete.min.js"></script>
<script>
(function () {
    // Searchable/typeable Employee dropdown — Tom Select keeps the original
    // #employee_id select in sync (data-basic/etc. and selectedIndex) and
    // dispatches a normal 'change' event on it, so the change listener
    // attached to empSelect below keeps working unmodified.
    new TomSelect('#employee_id', {
        placeholder: '— Select Employee —',
        allowEmptyOption: true,
        sortField: { field: 'text', direction: 'asc' },
        maxOptions: 500,
    });

    var watchFields = ['effectivity_date_from', 'effectivity_date_to', 'old_salary', 'new_salary'];
    watchFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.addEventListener('change', updatePreview); el.addEventListener('input', updatePreview); }
    });

    // Auto-fill Old Salary on employee select
    var empSelect = document.getElementById('employee_id');
    empSelect.addEventListener('change', function () {
        var opt   = this.options[this.selectedIndex];
        var basic = parseFloat(opt.getAttribute('data-basic'));
        var oldSalaryField = document.getElementById('old_salary');

        if (!isNaN(basic) && basic > 0) {
            oldSalaryField.value          = basic.toFixed(2);
            oldSalaryField.style.background = 'var(--surface-alt, #f0f2ff)';
            oldSalaryField.style.color      = 'var(--text-mid)';
            oldSalaryField.title            = 'Auto-filled from employee record. Click to override.';
        } else {
            oldSalaryField.value            = '';
            oldSalaryField.style.background = '';
            oldSalaryField.style.color      = '';
            oldSalaryField.title            = '';
        }
        updatePreview();
    });

    document.getElementById('old_salary').addEventListener('input', function () {
        this.style.background = '';
        this.style.color      = '';
        this.title            = '';
    });

    // Re-trigger on validation reload
    if (empSelect.value) { empSelect.dispatchEvent(new Event('change')); }

    // ── Helpers ─────────────────────────────────────────────────────────
    function fmt(n) {
        return '₱' + Number(n).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
    }
    function rnd(v) { return Math.round(v * 100) / 100; }
    function daysInMonth(y, m) { return new Date(y, m, 0).getDate(); }

    // ── Main compute ─────────────────────────────────────────────────────
    function updatePreview() {
        var empSel  = document.getElementById('employee_id');
        var fromVal = document.getElementById('effectivity_date_from').value;
        var toVal   = document.getElementById('effectivity_date_to').value;
        var oldSal  = parseFloat(document.getElementById('old_salary').value) || 0;
        var newSal  = parseFloat(document.getElementById('new_salary').value) || 0;

        // Differential badge + warning
        var diffBadge  = document.getElementById('diff-badge');
        var diffValEl  = document.getElementById('diff-val');
        var warnBox    = document.getElementById('new-salary-warn');
        var warnOldEl  = document.getElementById('warn-old-val');

        if (newSal > 0 && oldSal > 0) {
            var diff = rnd(newSal - oldSal);
            diffBadge.style.display = 'block';
            diffValEl.textContent   = fmt(diff);
            if (diff <= 0) {
                diffBadge.style.color = '#B71C1C';
                warnBox.style.display = 'block';
                warnOldEl.textContent = Number(oldSal).toLocaleString('en-PH', {minimumFractionDigits:2});
            } else {
                diffBadge.style.color = 'var(--navy)';
                warnBox.style.display = 'none';
            }
        } else {
            diffBadge.style.display = 'none';
            warnBox.style.display   = 'none';
        }

        if (!fromVal || !toVal || oldSal <= 0 || newSal <= 0 || newSal <= oldSal) {
            document.getElementById('previewEmpty').style.display   = 'block';
            document.getElementById('previewContent').style.display = 'none';
            return;
        }

        var whtRate = 0.20;
        if (empSel && empSel.selectedIndex > 0) {
            var raw = parseFloat(empSel.options[empSel.selectedIndex].getAttribute('data-wht'));
            if (!isNaN(raw) && raw > 0) whtRate = raw;
        }

        var differential = rnd(newSal - oldSal);
        var DENOM = 22;
        var from  = new Date(fromVal + 'T00:00:00');
        var to    = new Date(toVal   + 'T00:00:00');

        var perMonth    = [];
        var totalEarned = 0;
        var cursor      = new Date(from.getFullYear(), from.getMonth(), 1);
        var toMonth     = new Date(to.getFullYear(),   to.getMonth(),   1);

        while (cursor <= toMonth) {
            var mYear  = cursor.getFullYear();
            var mMonth = cursor.getMonth();
            var dim    = daysInMonth(mYear, mMonth + 1);

            var segStart = (cursor.getTime() === new Date(from.getFullYear(), from.getMonth(), 1).getTime())
                           ? from.getDate() : 1;
            var segEnd   = (cursor.getTime() === new Date(to.getFullYear(), to.getMonth(), 1).getTime())
                           ? to.getDate() : dim;

            var days        = segEnd - segStart + 1;
            var isFullMonth = (segStart === 1 && segEnd === dim);
            var earned      = isFullMonth ? rnd(differential) : rnd(differential * days / DENOM);

            var mNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            perMonth.push({ label: mNames[mMonth] + ' ' + mYear, days: days, earned: earned });
            totalEarned += earned;
            cursor = new Date(mYear, mMonth + 1, 1);
        }

        totalEarned = rnd(totalEarned);
        var totalGsis    = rnd(totalEarned * 0.09);
        var totalPhic    = rnd(totalEarned * 0.025);
        var totalPagIbig = rnd(perMonth.length * 200);
        var totalWht     = rnd(totalEarned * whtRate);
        var totalDeduct  = rnd(totalGsis + totalPhic + totalPagIbig + totalWht);
        var netAmount    = rnd(totalEarned - totalDeduct);

        document.getElementById('previewEmpty').style.display   = 'none';
        document.getElementById('previewContent').style.display = 'block';

        document.getElementById('prev-diff').textContent         = fmt(differential) + '/mo.';
        document.getElementById('prev-months-count').textContent = perMonth.length + ' month(s)';
        document.getElementById('prev-earned').textContent       = fmt(totalEarned);
        document.getElementById('prev-gsis').textContent         = fmt(totalGsis);
        document.getElementById('prev-phic').textContent         = fmt(totalPhic);
        document.getElementById('prev-pagibig').textContent      = fmt(totalPagIbig);
        document.getElementById('prev-wht-rate').textContent     = Math.round(whtRate * 100);
        document.getElementById('prev-wht').textContent          = fmt(totalWht);
        document.getElementById('prev-deduct').textContent       = fmt(totalDeduct);
        document.getElementById('prev-net').textContent          = fmt(netAmount);

        var tbody = document.getElementById('prev-months-body');
        tbody.innerHTML = '';
        perMonth.forEach(function (m) {
            var tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--border)';
            tr.innerHTML =
                '<td style="padding:3px 6px;">' + m.label + '</td>' +
                '<td style="padding:3px 6px; text-align:center;">' + m.days + '</td>' +
                '<td style="padding:3px 6px; text-align:right;">' + fmt(m.earned) + '</td>';
            tbody.appendChild(tr);
        });

        document.getElementById('prev-months-wrap').style.display = perMonth.length > 0 ? 'block' : 'none';
    }
})();
</script>
@endsection
