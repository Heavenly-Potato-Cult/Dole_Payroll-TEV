{{-- resources/views/special-payroll/newly-hired-create.blade.php --}}
{{--
    Expects from SpecialPayrollController@newHireCreate:
      $employees — collection of active Employee models
--}}

@extends('layouts.app')

@section('title', 'New Hire — Pro-Rated Payroll')
@section('page-title', 'Special Payroll')

@section('styles')
<style>
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
                            onchange="updatePreview()" required>
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

                {{-- Effectivity Date --}}
                <div class="form-group">
                    <label for="effectivity_date" id="effectivityLabel">
                        Effectivity Date (First Day of Work) <span style="color:var(--red);">*</span>
                    </label>
                    <input type="date" id="effectivity_date" name="effectivity_date"
                           value="{{ old('effectivity_date') }}"
                           class="{{ $errors->has('effectivity_date') ? 'is-invalid' : '' }}"
                           onchange="updatePreview()" required>
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
                               onchange="updatePreview()" required>
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
                               onchange="updatePreview()" required>
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
                            <td style="padding:4px 0; color:var(--text-light);">PERA Earned</td>
                            <td style="text-align:right; font-weight:600;" id="prev-pera">—</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:4px 0; color:var(--text-light);">Total Earned</td>
                            <td style="text-align:right; font-weight:700;" id="prev-earned">—</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:4px 0; color:#B71C1C;">GSIS PS (9.24%)</td>
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
                        ROUND(PERA ÷ 22 × working days, 2)
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">GSIS PS</span><br>
                        9.24% of Salary Earned (employee share)
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
<script>
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

function fmt(n) {
    return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function roundPHP(val) {
    return Math.round(val * 100) / 100;
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

    const salaryEarned = roundPHP((basic / 22) * days);
    const peraEarned   = roundPHP((pera  / 22) * days);

    const lwopSalary   = roundPHP(roundPHP(basic / 22) * lwop);
    const lwopPera     = roundPHP(roundPHP(pera  / 22) * lwop);
    const lwopTotal    = roundPHP(lwopSalary + lwopPera);

    const netEarned    = roundPHP((salaryEarned - lwopSalary) + (peraEarned - lwopPera));
    const gsisPS       = roundPHP(salaryEarned * 0.0924);
    const net          = roundPHP(netEarned - gsisPS);

    // Show preview
    document.getElementById('previewEmpty').style.display   = 'none';
    document.getElementById('previewContent').style.display = 'block';

    document.getElementById('prev-days').textContent   = days + ' days';
    document.getElementById('prev-basic').textContent  = fmt(basic);
    document.getElementById('prev-salary').textContent = fmt(salaryEarned);
    document.getElementById('prev-pera').textContent   = fmt(peraEarned);
    document.getElementById('prev-earned').textContent = fmt(netEarned);
    document.getElementById('prev-gsis').textContent   = '−' + fmt(gsisPS);
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
        effectivityLabel.innerHTML = 'Transfer Date (First Day in New Office) <span style="color:var(--red);">*</span>';
    } else if (type === 'newly_hired') {
        pageTitle.textContent = 'Pro-Rated Payroll — Newly Hired';
        pageDescription.textContent = 'Compute pro-rated salary for an employee who started mid-period.';
        effectivityLabel.innerHTML = 'Effectivity Date (First Day of Work) <span style="color:var(--red);">*</span>';
    } else {
        pageTitle.textContent = 'Pro-Rated Payroll — Newly Hired / Transferee';
        pageDescription.textContent = 'Compute pro-rated salary for an employee who started mid-period.';
        effectivityLabel.innerHTML = 'Effectivity Date (First Day of Work) <span style="color:var(--red);">*</span>';
    }
}

// Init on page load in case old() repopulates fields
updatePreview();
updateFormLabels();
</script>
@endsection