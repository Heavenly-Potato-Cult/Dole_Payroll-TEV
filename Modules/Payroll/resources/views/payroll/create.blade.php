@extends('layouts.app')

@section('title', 'Create Payroll Batch')
@section('page-title', 'Create Payroll Batch')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>New Payroll Batch</h1>
        <p>Select the period and cut-off, then submit to generate all employee entries.</p>
    </div>
    <a href="{{ route('payroll.index') }}" class="btn btn-outline btn-sm">← Back to Payroll List</a>
</div>

{{-- How it works info banner --}}
<div class="alert alert-info mb-3">
    <div>
        <strong>How batch creation works:</strong>
        Creating a batch immediately triggers payroll computation for all
        <strong>active employees</strong> using the 22-day fixed denominator.
        Deductions are pulled from each employee's active enrollment records.
        You can re-compute at any time before the batch is submitted for approval.
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

                {{-- Cut-off --}}
                <div class="form-group">
                    <label>Cut-off Period</label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:4px;">

                        <label class="cutoff-card {{ old('cutoff', '1st') === '1st' ? 'cutoff-card--active' : '' }}"
                               for="cutoff_1st">
                            <input type="radio" name="cutoff" id="cutoff_1st" value="1st"
                                   {{ old('cutoff', '1st') === '1st' ? 'checked' : '' }}
                                   onchange="updatePreview()">
                            <div class="cutoff-card-body">
                                <strong>1st Cut-off</strong>
                                <span>Coverage: 1–15</span>
                                <span class="text-muted" style="font-size:0.78rem;">Released on the 10th</span>
                            </div>
                        </label>

                        <label class="cutoff-card {{ old('cutoff') === '2nd' ? 'cutoff-card--active' : '' }}"
                               for="cutoff_2nd">
                            <input type="radio" name="cutoff" id="cutoff_2nd" value="2nd"
                                   {{ old('cutoff') === '2nd' ? 'checked' : '' }}
                                   onchange="updatePreview()">
                            <div class="cutoff-card-body">
                                <strong>2nd Cut-off</strong>
                                <span>Coverage: 16–30/31</span>
                                <span class="text-muted" style="font-size:0.78rem;">Released on the 25th</span>
                            </div>
                        </label>

                    </div>
                    @error('cutoff')
                        <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Custom Date Range (Optional) --}}
                <div class="form-group" style="margin-top:16px; padding:16px; background:#F8F9FA; border-radius:8px; border:1px solid #E9ECEF;">
                    <label style="font-size:0.78rem; font-weight:700; color:var(--navy); margin-bottom:12px; display:block;">
                        Custom Date Range <span class="text-muted">(optional)</span>
                    </label>
                    <div style="font-size:0.75rem; color:var(--text-mid); margin-bottom:12px;">
                        Leave blank to use standard cut-off dates above. Specify custom dates for special payroll periods.
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
                        <span id="confirmText"></span> — this will compute payroll for all active employees.
                    </div>
                </div>

                <div class="d-flex gap-2" style="margin-top:24px;">
                    <button type="button" class="btn btn-primary btn-lg" id="submitBtn" onclick="confirmCreatePayroll()">
                         Create &amp; Compute Payroll
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
                        Fixed at <strong>22 working days</strong> per cut-off
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">Salary Earned</span><br>
                        Basic Monthly ÷ 2
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">PERA Earned</span><br>
                        PERA Monthly ÷ 2
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">Attendance Deduction</span><br>
                        Hits <em>leave credits first</em>;<br>
                        salary deducted only when credits are exhausted
                    </div>
                    <div style="border-top:1px solid var(--border); padding-top:8px;">
                        <span class="fw-bold text-navy">Tardiness / Undertime</span><br>
                        Hidden in regular payroll view; reflected in special payroll processing
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
<style>
/* Cut-off selector cards */
.cutoff-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    border: 2px solid var(--border);
    border-radius: var(--radius);
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
    background: white;
}
.cutoff-card:hover {
    border-color: var(--navy);
    background: var(--navy-light);
}
.cutoff-card--active {
    border-color: var(--navy);
    background: var(--navy-light);
}
.cutoff-card input[type="radio"] {
    width: auto;
    margin-top: 3px;
    flex-shrink: 0;
    accent-color: var(--navy);
}
.cutoff-card-body {
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: 0.88rem;
    line-height: 1.4;
}
.cutoff-card-body strong {
    font-size: 0.92rem;
    color: var(--navy);
}
</style>

<script>
const MONTHS = [
    '', 'January','February','March','April','May','June',
    'July','August','September','October','November','December'
];

function updatePreview() {
    const year   = document.getElementById('period_year').value;
    const month  = parseInt(document.getElementById('period_month').value);
    const cutoff = document.querySelector('input[name="cutoff"]:checked')?.value || '1st';
    const customStart = document.getElementById('period_start').value;
    const customEnd = document.getElementById('period_end').value;

    let days, release, label;

    // Use custom dates if both are provided, otherwise use preset cutoffs
    if (customStart && customEnd) {
        const startDate = new Date(customStart);
        const endDate = new Date(customEnd);
        const startDay = startDate.getDate();
        const endDay = endDate.getDate();
        const startMonth = startDate.getMonth() + 1;
        const endMonth = endDate.getMonth() + 1;

        if (startMonth === endMonth && startMonth === month) {
            days = `${startDay}–${endDay}`;
        } else {
            days = `${MONTHS[startMonth]} ${startDay} – ${MONTHS[endMonth]} ${endDay}`;
        }

        label = `${MONTHS[month]} ${days}, ${year}`;
        release = 'Custom';
    } else {
        days = cutoff === '1st' ? '1–15' : '16–30/31';
        release = cutoff === '1st' ? '10th' : '25th';
        label = `${MONTHS[month]} ${days}, ${year}`;
    }

    document.getElementById('previewLabel').textContent = label;
    document.getElementById('previewSub').textContent   = `${cutoff.toUpperCase()} cut-off`;
    document.getElementById('previewRelease').innerHTML =
        `<span class="badge badge-pending" style="font-size:0.78rem;">Release date: ${MONTHS[month]} ${release}, ${year}</span>`;

    // Confirmation notice
    document.getElementById('confirmText').textContent = label;
    document.getElementById('confirmNotice').style.display = 'flex';

    // Highlight active cut-off card
    document.querySelectorAll('.cutoff-card').forEach(card => {
        card.classList.remove('cutoff-card--active');
        if (card.querySelector('input[value="' + cutoff + '"]')) {
            card.classList.add('cutoff-card--active');
        }
    });
}

// SweetAlert2 confirmation for Create & Compute Payroll
function confirmCreatePayroll() {
    const year   = document.getElementById('period_year').value;
    const month  = parseInt(document.getElementById('period_month').value);
    const cutoff = document.querySelector('input[name="cutoff"]:checked')?.value || '1st';
    const days   = cutoff === '1st' ? '1–15' : '16–30/31';
    const label  = `${MONTHS[month]} ${days}, ${year}`;

    Swal.fire({
        title: 'Create & Compute Payroll?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.25rem;font-weight:600;color:#0F1B4C;margin-bottom:8px;">${label}</div>
            <p style="color:#6b7280;font-size:0.95rem;">This will generate payroll entries for all active employees.</p>
        </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Create & Compute',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0F1B4C',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            executeCreatePayroll(label);
        }
    });
}

async function executeCreatePayroll(periodLabel) {
    // Show simple loading modal
    Swal.fire({
        title: '<span style="color:#0F1B4C;">Creating Payroll...</span>',
        html: `<div style="margin-top:10px;text-align:center;">
            <div style="font-size:1.1rem;color:#0F1B4C;margin-bottom:8px;">${periodLabel}</div>
            <p style="font-size:0.9rem;color:#6b7280;">Please wait while payroll is computed...</p>
        </div>`,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        showCancelButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const form = document.getElementById('createForm');
        const formData = new FormData(form);
        const csrfToken = formData.get('_token');

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 120000); // 120 second timeout for payroll computation

        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json, text/html',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData,
            signal: controller.signal
        });

        clearTimeout(timeoutId);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        // Payroll creation typically redirects to show page
        Swal.fire({
            icon: 'success',
            title: 'Payroll Created!',
            html: `<div style="text-align:center;">
                <div style="font-size:1.1rem;color:#0F1B4C;margin-bottom:8px;">${periodLabel}</div>
                <p style="color:#6b7280;font-size:0.9rem;">Payroll has been computed for all active employees.</p>
            </div>`,
            confirmButtonColor: '#0F1B4C'
        }).then(() => {
            // Follow the redirect or reload
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                window.location.reload();
            }
        });

    } catch (error) {
        let errorTitle = 'Creation Failed';
        let errorMessage = 'An unexpected error occurred while creating payroll.';

        if (error.message.includes('NetworkError') || error.message.includes('fetch')) {
            errorTitle = 'Connection Error';
            errorMessage = 'Unable to complete the request. Please check your internet connection.';
        } else if (error.message.includes('500')) {
            errorTitle = 'Server Error';
            errorMessage = 'The server encountered an error during payroll computation. Please contact the system administrator.';
        } else if (error.message.includes('422')) {
            errorTitle = 'Validation Error';
            errorMessage = 'Please check that all required fields are filled correctly (Year, Month, Cutoff).';
        } else if (error.message) {
            errorMessage = error.message;
        }

        Swal.fire({
            icon: 'error',
            title: errorTitle,
            html: `<div style="text-align:left;">
                <p>${errorMessage}</p>
                <p style="margin-top:12px;font-size:0.85rem;color:#6b7280;">
                    <strong>Troubleshooting:</strong><br>
                    • Verify all fields are filled (Year, Month, Cutoff)<br>
                    • Check that employees have valid salary grades<br>
                    • Contact IT if the problem persists
                </p>
            </div>`,
            confirmButtonText: 'Try Again',
            confirmButtonColor: '#0F1B4C',
            showCancelButton: true,
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#6B7280'
        }).then((result) => {
            if (result.isConfirmed) {
                confirmCreatePayroll();
            }
        });
    }
}

// Init on page load
updatePreview();
</script>
@endsection
