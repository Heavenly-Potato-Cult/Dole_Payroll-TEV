@extends('layouts.app')

@section('title', 'Create Payroll Batch')
@section('page-title', 'Create Payroll Batch')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>New Payroll Batch</h1>
        <p>Select the payroll period, then submit to create the monthly batch.</p>
    </div>
    <a href="{{ route('payroll.index') }}" class="btn btn-outline btn-sm">← Back to Payroll List</a>
</div>

{{-- How it works info banner --}}
<div class="alert alert-info mb-3">
    <div>
        <strong>How batch creation works:</strong>
        Creating a batch sets up the monthly payroll period for all
        <strong>active employees</strong>.
        After creation, pull attendance from HRIS, review / correct records, then compute.
        The 44-day monthly denominator is applied automatically.
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:start;">

    {{-- ── Main Form ── --}}
    <div class="card">
        <div class="card-header">
            <h3>📅 Payroll Period</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('payroll.store') }}" id="createForm">
                @csrf

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;">

                    {{-- Year --}}
                    <div class="form-group">
                        <label for="period_year">Year</label>
                        <select name="period_year" id="period_year"
                                class="{{ $errors->has('period_year') ? 'is-invalid' : '' }}"
                                onchange="updatePreview()">
                            @foreach ($years as $y)
                                <option value="{{ $y }}"
                                    {{ (old('period_year', $currentYear) == $y) ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>
                        @error('period_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Month --}}
                    <div class="form-group">
                        <label for="period_month">Month</label>
                        <select name="period_month" id="period_month"
                                class="{{ $errors->has('period_month') ? 'is-invalid' : '' }}"
                                onchange="updatePreview()">
                            @php
                                $months = [
                                    1=>'January', 2=>'February', 3=>'March',
                                    4=>'April',   5=>'May',      6=>'June',
                                    7=>'July',    8=>'August',   9=>'September',
                                    10=>'October',11=>'November',12=>'December',
                                ];
                            @endphp
                            @foreach ($months as $num => $label)
                                <option value="{{ $num }}"
                                    {{ (old('period_month', $currentMonth) == $num) ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('period_month')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                {{-- Toggle for Custom Date Range --}}
                <div class="form-group" style="margin-top:16px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" id="use_custom_range" onchange="toggleCustomRange()">
                        <span style="font-size:0.85rem; font-weight:600; color:var(--navy);">Use Custom Date Range</span>
                    </label>
                </div>

                {{-- Custom Date Range (Optional) --}}
                <div class="form-group" id="customRangeSection" style="margin-top:12px; padding:16px; background:#F8F9FA; border-radius:8px; border:1px solid #E9ECEF; opacity:0.5; pointer-events:none;">
                    <label style="font-size:0.78rem; font-weight:700; color:var(--navy); margin-bottom:12px; display:block;">
                        Custom Date Range <span class="text-muted">(optional)</span>
                    </label>
                    <div style="font-size:0.75rem; color:var(--text-mid); margin-bottom:12px;">
                        Leave blank to use the full calendar month (1st to last day).
                        Specify custom dates only for special payroll periods.
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div>
                            <label style="font-size:0.73rem; margin-bottom:4px; display:block;">Period Start</label>
                            <input type="date" name="period_start" id="period_start"
                                   value="{{ old('period_start') }}"
                                   class="{{ $errors->has('period_start') ? 'is-invalid' : '' }}"
                                   style="width:100%; padding:6px 10px; border:1px solid var(--border); border-radius:6px; font-size:0.85rem;"
                                   onchange="updatePreview()">
                            @error('period_start')
                                <div class="invalid-feedback" style="display:block; font-size:0.73rem;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label style="font-size:0.73rem; margin-bottom:4px; display:block;">Period End</label>
                            <input type="date" name="period_end" id="period_end"
                                   value="{{ old('period_end') }}"
                                   class="{{ $errors->has('period_end') ? 'is-invalid' : '' }}"
                                   style="width:100%; padding:6px 10px; border:1px solid var(--border); border-radius:6px; font-size:0.85rem;"
                                   onchange="updatePreview()">
                            @error('period_end')
                                <div class="invalid-feedback" style="display:block; font-size:0.73rem;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Confirmation notice --}}
                <div class="alert alert-warning" id="confirmNotice" style="display:none;">
                    <div>
                        <strong>⚠ About to create:</strong>
                        <span id="confirmText"></span> — pull attendance after creation, then compute.
                    </div>
                </div>

                <div class="d-flex gap-2" style="margin-top:24px;">
                    <button type="button" class="btn btn-primary btn-lg" id="submitBtn" onclick="confirmCreatePayroll()">
                        Create Payroll Batch
                    </button>
                    <a href="{{ route('payroll.index') }}" class="btn btn-outline btn-lg">Cancel</a>
                </div>

            </form>
        </div>
    </div>

    {{-- ── Right: Preview Card + Rules ── --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Live Preview --}}
        <div class="card">
            <div class="card-header">
                <h3>🔍 Period Preview</h3>
            </div>
            <div class="card-body">
                <div id="previewBox" style="text-align:center; padding:12px 0;">
                    <div style="font-size:1.5rem; font-weight:700; color:var(--navy);" id="previewLabel">—</div>
                    <div class="text-muted" style="font-size:0.82rem; margin-top:6px;" id="previewSub">
                        Select a period above
                    </div>
                    <div style="margin-top:12px;" id="previewRelease"></div>
                </div>
            </div>
        </div>

        {{-- Computation Rules --}}
        <div class="card">
            <div class="card-header">
                <h3>📐 Computation Rules</h3>
            </div>
            <div class="card-body" style="font-size:0.84rem; color:var(--text-mid); line-height:1.8;">
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <div>
                        <span class="fw-bold text-navy">Denominator</span><br>
                        Fixed at <strong>44 working days</strong> per month (22 × 2)
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">Salary Earned</span><br>
                        Full basic monthly salary
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">PERA Earned</span><br>
                        Full PERA monthly amount
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">Attendance Deduction</span><br>
                        Hits <em>leave credits first</em>;<br>
                        salary deducted only when credits are exhausted
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">Cutoff Breakdown</span><br>
                        1st (days 1–15) and 2nd (days 16–end)<br>
                        split proportionally from daily attendance logs
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">Withholding Tax</span><br>
                        Annualized (Jan–Dec)<br>
                        GSIS / PhilHealth / Pag-IBIG deducted from taxable income
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection

@section('scripts')
<script>
const MONTHS = [
    '', 'January','February','March','April','May','June',
    'July','August','September','October','November','December'
];

function lastDayOf(year, month) {
    return new Date(year, month, 0).getDate();
}

function toggleCustomRange() {
    const useCustom = document.getElementById('use_custom_range').checked;
    const yearSelect = document.getElementById('period_year');
    const monthSelect = document.getElementById('period_month');
    const customSection = document.getElementById('customRangeSection');
    
    yearSelect.disabled = useCustom;
    monthSelect.disabled = useCustom;
    
    if (useCustom) {
        customSection.style.opacity = '1';
        customSection.style.pointerEvents = 'auto';
    } else {
        customSection.style.opacity = '0.5';
        customSection.style.pointerEvents = 'none';
        document.getElementById('period_start').value = '';
        document.getElementById('period_end').value = '';
    }
    
    updatePreview();
}

function updatePreview() {
    const useCustom = document.getElementById('use_custom_range').checked;
    const year = parseInt(document.getElementById('period_year').value);
    const month = parseInt(document.getElementById('period_month').value);
    const customStart = document.getElementById('period_start').value;
    const customEnd = document.getElementById('period_end').value;
    
    let label, rangeSub;
    
    if (useCustom && customStart && customEnd) {
        const startDate = new Date(customStart);
        const endDate = new Date(customEnd);
        const startDay = startDate.getDate();
        const endDay = endDate.getDate();
        const startMon = startDate.getMonth() + 1;
        const endMon = endDate.getMonth() + 1;
        const startYear = startDate.getFullYear();
        const endYear = endDate.getFullYear();
        
        if (startMon === endMon && startYear === endYear) {
            rangeSub = `${startDay}–${endDay}`;
            label = `${MONTHS[startMon]} ${rangeSub}, ${startYear}`;
        } else {
            rangeSub = `${MONTHS[startMon]} ${startDay}, ${startYear} – ${MONTHS[endMon]} ${endDay}, ${endYear}`;
            label = rangeSub;
        }
    } else {
        const lastDay = lastDayOf(year, month);
        rangeSub = `1–${lastDay}`;
        label = `${MONTHS[month]} ${rangeSub}, ${year}`;
    }
    
    document.getElementById('previewLabel').textContent = label;
    document.getElementById('previewSub').textContent = useCustom ? 'Custom payroll period' : 'Monthly payroll period';
    document.getElementById('previewRelease').innerHTML = useCustom 
        ? `<span class="badge badge-pending" style="font-size:0.78rem;">Custom period</span>`
        : `<span class="badge badge-pending" style="font-size:0.78rem;">Release: end of ${MONTHS[month]} ${year}</span>`;
    
    document.getElementById('confirmText').textContent = label;
    document.getElementById('confirmNotice').style.display = 'flex';
}

function confirmCreatePayroll() {
    const useCustom = document.getElementById('use_custom_range').checked;
    let label;
    
    if (useCustom) {
        const customStart = document.getElementById('period_start').value;
        const customEnd = document.getElementById('period_end').value;
        const startDate = new Date(customStart);
        const endDate = new Date(customEnd);
        const startDay = startDate.getDate();
        const endDay = endDate.getDate();
        const startMon = startDate.getMonth() + 1;
        const endMon = endDate.getMonth() + 1;
        const startYear = startDate.getFullYear();
        const endYear = endDate.getFullYear();
        
        if (startMon === endMon && startYear === endYear) {
            label = `${MONTHS[startMon]} ${startDay}–${endDay}, ${startYear}`;
        } else {
            label = `${MONTHS[startMon]} ${startDay}, ${startYear} – ${MONTHS[endMon]} ${endDay}, ${endYear}`;
        }
    } else {
        const year = parseInt(document.getElementById('period_year').value);
        const month = parseInt(document.getElementById('period_month').value);
        const lastDay = lastDayOf(year, month);
        label = `${MONTHS[month]} 1–${lastDay}, ${year}`;
    }

    Swal.fire({
        title: 'Create Payroll Batch?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.25rem;font-weight:600;color:#0F1B4C;margin-bottom:8px;">${label}</div>
            <p style="color:#6b7280;font-size:0.95rem;">
                A ${useCustom ? 'custom' : 'monthly'} batch will be created.<br>
                Pull attendance first, then compute.
            </p>
        </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Create Batch',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0F1B4C',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('createForm').submit();
        }
    });
}

toggleCustomRange();
</script>
@endsection