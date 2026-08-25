{{-- resources/views/special-payroll/newly-hired-show.blade.php --}}
{{--
    Expects from SpecialPayrollController@newHireShow:
      $batch    — SpecialPayrollBatch (type=newly_hired, with employee, approver)
      $employee — Employee model
      $result   — array from NewlyHiredPayrollService::compute()
--}}

@extends('layouts.app')

@section('title', 'Pro-Rated Payroll — ' . optional($employee)->last_name)
@section('page-title', 'Special Payroll')

@section('styles')
<style>
/* ══════════════════════════════════════════════════
   APPROVAL STAGE STEPPER
══════════════════════════════════════════════════ */
.approval-stepper {
    display: flex;
    align-items: center;
    position: relative;
    padding: 20px 10%;
    margin-bottom: 0;
    height: 80px;
}

/* Progress track line - runs through center of dots */
.approval-stepper::before {
    content: '';
    position: absolute;
    top: 16px; /* Half of 32px dot height */
    left: 0;
    right: 0;
    height: 2px;
    background: #E5E7EB;
    z-index: 1;
}

.approval-stepper .progress-fill {
    position: absolute;
    top: 16px; /* Half of 32px dot height */
    left: 0;
    height: 2px;
    background: #10B981;
    z-index: 2;
    transition: width 0.3s ease;
}

/* Step nodes - flex column with dot on top, text below */
.approval-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    position: relative;
    z-index: 3;
    flex: 1;
    text-align: center;
    padding-top: 0;
}

.approval-step-label {
    font-size: 0.80rem;
    font-weight: 600;
    color: #374151;
    line-height: 1.2;
    margin-bottom: 2px;
    text-align: center;
}

.approval-step-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 2px solid #E5E7EB;
    background: #ffffff;
    color: #9CA3AF;
    position: relative;
    z-index: 4;
    margin-bottom: 8px;
}

.approval-step.done .approval-step-dot {
    background: #10B981;
    border-color: #10B981;
    color: #ffffff;
}

.approval-step.active .approval-step-dot {
    background: #1F2937;
    border-color: #1F2937;
    color: #ffffff;
    box-shadow: 0 0 0 4px rgba(31, 41, 55, 0.1);
}

.approval-step.future .approval-step-dot {
    background: #ffffff;
    border-color: #E5E7EB;
    color: #9CA3AF;
}

.approval-step.locked .approval-step-dot {
    background: #1F2937;
    border-color: #1F2937;
    color: #ffffff;
}

.approval-step-label {
    font-size: 0.80rem;
    font-weight: 600;
    color: #374151;
    line-height: 1.2;
    margin-bottom: 2px;
}

.approval-step.done .approval-step-label {
    color: #10B981;
}

.approval-step.active .approval-step-label {
    color: #1F2937;
    font-weight: 700;
}

.approval-step.future .approval-step-label {
    color: #9CA3AF;
    font-weight: 500;
}

.approval-step.locked .approval-step-label {
    color: #1F2937;
    font-weight: 700;
}

.approval-step-sub {
    font-size: 0.70rem;
    color: #6B7280;
    font-weight: 400;
    margin-top: 2px;
}

.approval-step.done .approval-step-sub {
    color: #10B981;
}

.approval-step.active .approval-step-sub {
    color: #6B7280;
}

.approval-step.future .approval-step-sub {
    color: #9CA3AF;
}

.approval-step.locked .approval-step-sub {
    color: #6B7280;
}

/* ── Document header ── */
.doc-header {
    text-align: center; padding: 8px 0 20px;
    border-bottom: 2px solid var(--navy); margin-bottom: 20px;
}
.doc-header h2 {
    font-size: 1rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.04em;
    color: var(--navy); margin: 0 0 4px;
}
.doc-header p { font-size: 0.82rem; color: var(--text-mid); margin: 0; }

/* ── Meta grid ── */
.doc-meta {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px 24px; margin-bottom: 20px; font-size: 0.85rem;
}
.doc-meta-item { display: flex; flex-direction: column; gap: 2px; }
.doc-meta-item .label {
    font-size: 0.70rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-light);
}
.doc-meta-item .value { font-weight: 600; color: var(--text); }

/* ── Computation table (desktop) ── */
.comp-wrap { overflow-x: auto; margin-bottom: 20px; }

/* ── Allowances breakdown modal ── */
.allowance-total-btn {
    background: none;
    border: none;
    padding: 0;
    margin: 0;
    font: inherit;
    color: inherit;
    text-decoration: underline dotted;
    text-underline-offset: 2px;
    cursor: pointer;
}
@media print {
    .allowance-total-btn { text-decoration: none; cursor: default; }
}
.allowance-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(13, 28, 85, 0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.allowance-modal {
    background: #fff;
    border-radius: 10px;
    width: 320px;
    max-width: 90vw;
    box-shadow: 0 12px 32px rgba(0,0,0,0.25);
    overflow: hidden;
}
.allowance-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: #1A2B6B;
    color: #fff;
}
.allowance-modal-header strong { font-size: 0.9rem; }
.allowance-modal-header button {
    background: none;
    border: none;
    color: #fff;
    font-size: 1.2rem;
    line-height: 1;
    cursor: pointer;
}
.allowance-modal-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.allowance-modal-table td { padding: 8px 16px; border-bottom: 1px solid #EEF1FA; }
.allowance-modal-table tfoot td { border-top: 1.5px solid #1A2B6B; border-bottom: none; font-weight: 700; padding-top: 10px; }
.comp-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; white-space: nowrap; }
.comp-table thead tr:first-child th {
    background: var(--navy); color: #fff;
    padding: 7px 10px; text-align: center;
    font-size: 0.72rem; font-weight: 600; letter-spacing: 0.03em;
    border: 1px solid rgba(255,255,255,0.15);
}
.comp-table thead tr:last-child th {
    background: #2a3c6e; color: #cdd6f4;
    padding: 5px 8px; text-align: center;
    font-size: 0.69rem; font-weight: 600;
    border: 1px solid rgba(255,255,255,0.12);
}
.comp-table thead th.text-right { text-align: right; }
.comp-table tbody td { padding: 10px 12px; border-bottom: 1px solid var(--border); }
.comp-table tbody td.text-right { text-align: right; }
.comp-table tfoot td {
    padding: 10px 12px; font-weight: 700; font-size: 0.86rem;
    background: var(--navy); color: white;
}
.comp-table tfoot td.text-right { text-align: right; }
.comp-table tfoot td.gold-text  { color: var(--gold); }
.comp-table tfoot td.green-text { color: #69F0AE; }
.comp-table tfoot td.red-text   { color: #FF8A80; }

/* ── Mobile summary card ── */
.mobile-summary {
    display: none;
    border: 1px solid var(--border); border-radius: var(--radius);
    overflow: hidden; margin-bottom: 20px; font-size: 0.84rem;
}
.mobile-summary-header {
    background: var(--navy); color: #fff;
    padding: 10px 14px; font-weight: 700; font-size: 0.80rem;
}
.mobile-summary-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 9px 14px; border-bottom: 1px solid var(--border);
}
.mobile-summary-row:last-child { border-bottom: none; }
.mobile-summary-row .ms-label {
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.04em; color: var(--text-light);
}
.mobile-summary-row .ms-value { font-weight: 600; color: var(--text); }
.mobile-summary-section {
    background: var(--surface-alt, #f8f9ff); padding: 6px 14px;
    font-size: 0.70rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: var(--text-light);
    border-bottom: 1px solid var(--border);
}

/* ── Govt share note ── */
.govtshare-note {
    background: #FAFBFF; border: 1px solid var(--border);
    border-radius: var(--radius); padding: 12px 16px;
    font-size: 0.80rem; color: var(--text-mid); margin-bottom: 20px;
}

/* ── Certification blocks ── */
.cert-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 28px; }
.cert-block {
    border: 1px solid var(--border); border-radius: var(--radius);
    padding: 16px 20px 22px;
}
.cert-block-tag {
    font-size: 0.70rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; color: var(--text-light); margin-bottom: 4px;
}
.cert-block-title { font-weight: 600; font-size: 0.83rem; color: var(--navy); margin-bottom: 20px; }
.cert-sig-line { border-bottom: 1px solid var(--text-mid); margin-bottom: 6px; width: 80%; }
.cert-sig-name { font-weight: 700; font-size: 0.85rem; }
.cert-sig-role { font-size: 0.75rem; color: var(--text-light); }
.cert-date     { font-size: 0.78rem; color: var(--text-mid); margin-top: 8px; }

/* ── Print ── */
@media print {
    .no-print { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
    .approval-stepper { display: none !important; }
    .cert-grid { page-break-inside: avoid; }
    .mobile-summary { display: none !important; }
    .comp-wrap { display: block !important; }
    .comp-table { font-size: 9pt; }
    .doc-header h2 { font-size: 12pt; }
    body { font-size: 10pt; }
    @page { margin: 1.5cm 1.2cm; }
}

/* ── Mobile overrides ── */
@media (max-width: 768px) {
    .approval-stepper {
        padding: 15px 5%;
        height: auto;
        flex-direction: column;
        gap: 15px;
    }

    .approval-stepper::before {
        display: none;
    }

    .approval-stepper .progress-fill {
        display: none;
    }

    .approval-step {
        flex-direction: row;
        justify-content: flex-start;
        padding: 10px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: white;
    }

    .approval-step-dot {
        margin-bottom: 0;
        margin-right: 12px;
    }

    .approval-step-label {
        text-align: left;
        margin-bottom: 0;
    }

    .approval-step-sub {
        margin-top: 2px;
    }

    .doc-meta { grid-template-columns: 1fr 1fr; }

    .comp-wrap { display: none; }
    .mobile-summary { display: block; }

    .cert-grid { grid-template-columns: 1fr; }

    .govtshare-note { font-size: 0.76rem; }

    .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .page-header .d-flex { width: 100%; flex-wrap: wrap; }
    .page-header .d-flex .btn { flex: 1; justify-content: center; }
    .page-header .d-flex form { flex: 1; }
    .page-header .d-flex form .btn { width: 100%; }
}
</style>
@endsection

@section('content')

@php
    $statusClass = match ($batch->status) {
        'approved' => 'badge-released',
        'released' => 'badge-locked',
        default    => 'badge-draft',
    };
    $statusLabel = match ($batch->status) {
        'draft'    => 'Draft',
        'approved' => 'Approved',
        'released' => 'Released',
        default    => ucfirst($batch->status),
    };

    $periodLabel      = $batch->period_start->format('M d') . '–' . $batch->period_end->format('d, Y');
    $effectivityFmt   = $batch->effectivity_date->format('M d, Y');

    // PERA is folded in here even though it's a separate first-class field
    // in NewlyHiredPayrollService::compute() — to the payroll officer
    // reading this register, PERA is just another allowance type (it's
    // literally an AllowanceType row in the CMS), so it belongs in the
    // same breakdown as RATA/etc rather than getting its own columns.
    $allowanceBreakdown = collect([
        ['name' => 'PERA', 'code' => 'PERA', 'amount' => (float) $result['pera_earned']],
    ])->concat(
        collect($result['allowance_lines'] ?? [])->map(fn ($l) => [
            'name'   => $l['name'],
            'code'   => $l['code'],
            'amount' => (float) $l['amount'],
        ])
    )->values();

    $allowancesTotalForDisplay = round($allowanceBreakdown->sum('amount'), 2);

    $canApprove = auth()->user()->hasRole('accountant') && $batch->status === 'draft';
    $canRelease = auth()->user()->hasAnyRole(['ard', 'chief_admin_officer']) && $batch->status === 'approved';

    $steps = [
        [
            'statuses' => ['draft'],
            'label'    => 'HR Prepared',
            'sub'      => 'Payroll Officer',
            'icon'     => '✏',
        ],
        [
            'statuses' => ['approved'],
            'label'    => 'Accountant',
            'sub'      => 'Certify Funds',
            'icon'     => '💼',
        ],
        [
            'statuses' => ['released'],
            'label'    => 'RD / ARD',
            'sub'      => 'Released',
            'icon'     => '🏛',
        ],
    ];

    // Map the current status to a step index (0-based)
    $statusToStep = [
        'draft'    => 0,
        'approved' => 1,
        'released' => 2,
    ];
    $activeStep = $statusToStep[$batch->status] ?? 0;

    // Create dynamic sub-labels based on status and timestamps
    $dynamicSubs = [];
    foreach ($steps as $i => $step) {
        if ($i < $activeStep) {
            // Completed stage - show when it happened
            if ($i === 0 && $batch->created_at) {
                $dynamicSubs[] = 'Done · ' . $batch->created_at->format('M d');
            } elseif ($i === 1 && $batch->approved_at) {
                $dynamicSubs[] = 'Certified · ' . \Carbon\Carbon::parse($batch->approved_at)->format('M d');
            } elseif ($i === 2 && $batch->released_at) {
                $dynamicSubs[] = 'Released · ' . \Carbon\Carbon::parse($batch->released_at)->format('M d');
            } else {
                $dynamicSubs[] = $step['sub'];
            }
        } elseif ($i === $activeStep) {
            // Active stage - show what's waiting
            if ($batch->status === 'draft') {
                $dynamicSubs[] = 'Awaiting computation';
            } elseif ($batch->status === 'approved') {
                $dynamicSubs[] = 'Awaiting release';
            } elseif ($batch->status === 'released') {
                $dynamicSubs[] = 'Released';
            } else {
                $dynamicSubs[] = $step['sub'];
            }
        } else {
            // Future stage - just show the role
            $dynamicSubs[] = $step['sub'];
        }
    }
@endphp

{{-- ═══════════════════════════════════════════════════════════════
     HEADER CARD WITH APPROVAL STEPPER
═══════════════════════════════════════════════════════════════ --}}
<div class="card header-card" style="margin-bottom: 32px;">
    <div class="card-body" style="padding: 20px 20px 0 20px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
        <!-- Page Header Section -->
        <div class="page-header no-print" style="margin-bottom: 24px;">
            <div class="page-header-left">
                <h1>Pro-Rated Payroll</h1>
                <p>
                    {{ optional($employee)->last_name }}, {{ optional($employee)->first_name }} ·
                    {{ $periodLabel }} ·
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('special-payroll.newly-hired.index') }}"
                   class="btn btn-outline btn-sm no-print">← All Records</a>

                {{-- Was onclick="window.print()" — that printed this interactive
                     app-layout page as-is (scrollbars baked into the page, table
                     clipped at the edge, nav/logout chrome repeating on every
                     printed page, certification blocks splitting onto a blank
                     second page). Now opens the dedicated DomPDF register
                     instead, in a new tab so this page stays put. --}}
                <a href="{{ route('special-payroll.newly-hired.general-payroll', $batch->id) }}"
                   target="_blank" class="btn btn-outline btn-sm no-print">
                    🖨 Print
                </a>

                @if ($canApprove)
                    <form method="POST"
                          action="{{ route('special-payroll.newly-hired.approve', $batch->id) }}"
                          onsubmit="return confirm('Approve this pro-rated payroll record?')">
                        @csrf
                        <button type="submit" class="btn btn-gold btn-sm no-print">
                            ✔ Approve
                        </button>
                    </form>
                @endif

                @if ($canRelease)
                    <form method="POST"
                          action="{{ route('special-payroll.newly-hired.approve', $batch->id) }}"
                          onsubmit="return confirm('Release this payroll record for disbursement?')">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm no-print">
                            ✔ Release
                        </button>
                    </form>
                @endif

                @if ($batch->status === 'released')
                    <a href="{{ route('special-payroll.newly-hired.payslip', $batch->id) }}"
                       class="btn btn-gold btn-sm no-print" target="_blank">
                        ⬇ Download Payslip
                    </a>
                @endif
            </div>
        </div>

        <!-- Approval Stepper Section -->
        <div class="approval-stepper no-print">
            <!-- Progress fill line -->
            <div class="progress-fill" style="width: {{ ($activeStep / (count($steps) - 1)) * 100 }}%;"></div>

            @foreach ($steps as $i => $step)
                @php
                    if ($i < $activeStep) {
                        $stepClass = 'done';
                    } elseif ($i === $activeStep) {
                        // Mark as 'done' if current status matches this step's statuses
                        if (in_array($batch->status, $step['statuses'])) {
                            $stepClass = 'done';
                        } elseif ($batch->status === 'released') {
                            $stepClass = 'locked';
                        } else {
                            $stepClass = 'active';
                        }
                    } else {
                        $stepClass = 'future';
                    }

                    $dotContent = $i + 1; // Show step number instead of icon
                @endphp

                <div class="approval-step {{ $stepClass }}">
                    <div class="approval-step-dot">{{ $dotContent }}</div>
                    <div class="approval-step-label">
                        {{ $step['label'] }}
                        <span class="approval-step-sub">{{ $dynamicSubs[$i] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ═══ PRINTABLE DOCUMENT ═══ --}}
<div class="card">
    <div class="card-body">

        {{-- DOLE document header --}}
        <div class="doc-header">
            <h2>General Payroll</h2>
            <p>Department of Labor and Employment — Regional Office IX, Zamboanga City</p>
            <p style="font-weight:700; font-size:0.90rem; color:var(--navy); margin-top:6px;">
                PRO-RATED PAYROLL FOR NEWLY HIRED / TRANSFEREE EMPLOYEE
            </p>
            <p style="margin-top:4px;">For the Period of {{ $periodLabel }}</p>
        </div>

        <p style="font-size:0.78rem; color:var(--text-mid); font-style:italic; margin-bottom:18px;">
            I acknowledge receipt of cash shown opposite my name as full compensation
            for services rendered for the period covered.
        </p>

        {{-- Meta summary --}}
        <div class="doc-meta">
            <div class="doc-meta-item">
                <span class="label">Employee Name</span>
                <span class="value">
                    {{ $employee->last_name }}, {{ $employee->first_name }}
                    @if ($employee->middle_name) {{ substr($employee->middle_name, 0, 1) }}. @endif
                </span>
            </div>
            <div class="doc-meta-item">
                <span class="label">Position</span>
                <span class="value">{{ $employee->position_title ?? '—' }}</span>
            </div>
            <div class="doc-meta-item">
                <span class="label">Plantilla Item No.</span>
                <span class="value">{{ $employee->plantilla_item_no ?? '—' }}</span>
            </div>
            <div class="doc-meta-item">
                <span class="label">Assumption to Duty</span>
                <span class="value">{{ $effectivityFmt }}</span>
            </div>
            <div class="doc-meta-item">
                <span class="label">Period Covered</span>
                <span class="value">{{ $periodLabel }}</span>
            </div>
            <div class="doc-meta-item">
                <span class="label">Working Days</span>
                <span class="value">{{ $result['working_days'] }} day(s)</span>
                <div style="font-size:0.68rem; color:var(--text-light); font-weight:400; margin-top:2px;">
                    Salary rate uses a fixed 22-day divisor
                </div>
            </div>
        </div>

        {{-- ── Mobile summary card ── --}}
        <div class="mobile-summary">
            <div class="mobile-summary-header">Computation Summary</div>
            <div class="mobile-summary-row">
                <span class="ms-label">Basic Salary</span>
                <span class="ms-value">₱{{ number_format($result['basic_salary'], 2) }}</span>
            </div>
            <div class="mobile-summary-row">
                <span class="ms-label">Salary Earned</span>
                <span class="ms-value">₱{{ number_format($result['salary_earned'], 2) }}</span>
            </div>
            <div class="mobile-summary-row">
                <span class="ms-label">Allowances</span>
                <span class="ms-value">
                    <button type="button" class="allowance-total-btn" onclick="openAllowanceBreakdown()">
                        ₱{{ number_format($allowancesTotalForDisplay, 2) }}
                    </button>
                </span>
            </div>
            <div class="mobile-summary-row" style="background:var(--surface-alt, #f0f2fa);">
                <span class="ms-label">Total Earned</span>
                <span class="ms-value" style="color:var(--navy); font-weight:700;">₱{{ number_format($result['net_earned'], 2) }}</span>
            </div>
            <div class="mobile-summary-section">Deductions</div>
            <div class="mobile-summary-row">
                <span class="ms-label">
                    GSIS Personal Share
                    @if (! (($batch->gsis_rate_applied ?? 0) > 0))
                        <span style="font-size:0.68rem; color:var(--text-light); font-weight:400;">(not applied)</span>
                    @endif
                </span>
                <span class="ms-value" style="color:#B71C1C;">₱{{ number_format($result['gsis_ps'], 2) }}</span>
            </div>
            <div class="mobile-summary-row">
                <span class="ms-label">PhilHealth</span>
                <span class="ms-value" style="color:#B71C1C;">₱{{ number_format($result['phic'], 2) }}</span>
            </div>
            <div class="mobile-summary-row">
                <span class="ms-label">Pag-IBIG</span>
                <span class="ms-value" style="color:#B71C1C;">₱{{ number_format($result['pagibig'], 2) }}</span>
            </div>
            <div class="mobile-summary-row">
                <span class="ms-label">Withholding Tax</span>
                <span class="ms-value" style="color:#B71C1C;">₱{{ number_format($result['wht'], 2) }}</span>
            </div>
            <div class="mobile-summary-row" style="background:var(--surface-alt, #f0f2fa);">
                <span class="ms-label">Total Deductions</span>
                <span class="ms-value" style="color:#B71C1C; font-weight:700;">₱{{ number_format($result['total_deductions'], 2) }}</span>
            </div>
            <div class="mobile-summary-row" style="background:#F1FAF5;">
                <span class="ms-label">Net Amount</span>
                <span class="ms-value" style="color:#1B5E20; font-weight:700; font-size:1rem;">₱{{ number_format($result['net_amount'], 2) }}</span>
            </div>
        </div>

        {{-- ── Desktop computation table ── --}}
        <div class="comp-wrap">
            <table class="comp-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:32px; text-align:center; vertical-align:middle;">#</th>
                        <th rowspan="2" style="text-align:left; vertical-align:middle;">Name</th>
                        <th rowspan="2" style="text-align:left; vertical-align:middle;">Position</th>
                        <th colspan="4" style="text-align:center; background:#1e3a8a;">
                            EARNED FOR THE PERIOD
                        </th>
                        <th colspan="5" style="text-align:center; background:#7c1a1a;">DEDUCTIONS</th>
                        <th rowspan="2" style="text-align:right; vertical-align:middle;">Net Amount</th>
                        <th rowspan="2" style="min-width:90px; text-align:center; vertical-align:middle;">Signature</th>
                    </tr>
                    <tr>
                        <th style="text-align:right;">Basic Salary</th>
                        <th style="text-align:right;">Salary Earned</th>
                        <th style="text-align:right;">Allowances</th>
                        <th style="text-align:right; background:#7c1a1a;">Total Earned</th>
                        <th style="text-align:right; background:#5b2020;">GSIS PS</th>
                        <th style="text-align:right; background:#5b2020;">PHIC</th>
                        <th style="text-align:right; background:#5b2020;">Pag-IBIG</th>
                        <th style="text-align:right; background:#5b2020;">WHT</th>
                        <th style="text-align:right; background:#7c1a1a;">Total Deductions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="color:var(--text-light);">1</td>
                        <td class="fw-bold">
                            {{ $employee->last_name }}, {{ $employee->first_name }}
                            @if ($employee->middle_name) {{ substr($employee->middle_name, 0, 1) }}. @endif
                        </td>
                        <td class="text-muted">{{ $employee->position_title ?? '—' }}</td>
                        <td class="text-right">₱{{ number_format($result['basic_salary'],    2) }}</td>
                        <td class="text-right">₱{{ number_format($result['salary_earned'],   2) }}</td>
                        <td class="text-right">
                            <button type="button" class="allowance-total-btn"
                                    onclick="openAllowanceBreakdown()">
                                ₱{{ number_format($allowancesTotalForDisplay, 2) }}
                            </button>
                        </td>
                        <td class="text-right fw-bold">₱{{ number_format($result['net_earned'], 2) }}</td>
                        <td class="text-right" style="color:#B71C1C;">₱{{ number_format($result['gsis_ps'], 2) }}</td>
                        <td class="text-right text-muted">₱{{ number_format($result['phic'],   2) }}</td>
                        <td class="text-right text-muted">₱{{ number_format($result['pagibig'], 2) }}</td>
                        <td class="text-right text-muted">₱{{ number_format($result['wht'],    2) }}</td>
                        <td class="text-right fw-bold" style="color:#B71C1C;">₱{{ number_format($result['total_deductions'], 2) }}</td>
                        <td class="text-right fw-bold" style="color:#1B5E20; font-size:0.90rem;">₱{{ number_format($result['net_amount'], 2) }}</td>
                        <td style="text-align:center; border-bottom:1px solid var(--text-mid);">&nbsp;</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="color:rgba(255,255,255,0.6); font-size:0.78rem;">TOTALS — 1 employee</td>
                        <td class="text-right">₱{{ number_format($result['basic_salary'],    2) }}</td>
                        <td class="text-right">₱{{ number_format($result['salary_earned'],   2) }}</td>
                        <td class="text-right">₱{{ number_format($allowancesTotalForDisplay, 2) }}</td>
                        <td class="text-right gold-text">₱{{ number_format($result['net_earned'],      2) }}</td>
                        <td class="text-right red-text">₱{{ number_format($result['gsis_ps'],         2) }}</td>
                        <td class="text-right red-text">₱{{ number_format($result['phic'],   2) }}</td>
                        <td class="text-right red-text">₱{{ number_format($result['pagibig'], 2) }}</td>
                        <td class="text-right red-text">₱{{ number_format($result['wht'],    2) }}</td>
                        <td class="text-right red-text">₱{{ number_format($result['total_deductions'], 2) }}</td>
                        <td class="text-right green-text" style="font-size:0.95rem;">₱{{ number_format($result['net_amount'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Government shares note --}}
        <div class="govtshare-note">
            <strong style="color:var(--navy);">Government Shares</strong>
            (remitted separately — not deducted from employee's net pay):
            &emsp; GSIS GS: <strong>₱{{ number_format($result['gsis_gs'],  2) }}</strong>
            &emsp;|&emsp; PhilHealth GS: <strong>₱{{ number_format($result['phic_gs'], 2) }}</strong>
            &emsp;|&emsp; Pag-IBIG GS: <strong>₱{{ number_format($result['hdmf_gs'],  2) }}</strong>
        </div>

        {{-- Amount in words / ALOBS area --}}
        <div style="font-size:0.82rem; color:var(--text-mid); margin-bottom:28px; text-align:right;">
            <span style="font-weight:700; color:var(--navy);">=P=</span>
            &nbsp; ₱{{ number_format($result['net_amount'], 2) }}
            &emsp; ALOBS No.: ______________
            &emsp; Date: ______________
        </div>

        {{-- ═══ CERTIFICATION BLOCKS ═══ --}}
        <div class="cert-grid">

            <div class="cert-block">
                <div class="cert-block-tag">[ A ]</div>
                <div class="cert-block-title">Certified: Services duly rendered as stated.</div>
                <div class="cert-sig-line"></div>
                <div class="cert-sig-name">________________________________</div>
                <div class="cert-sig-role">Administrative Officer V / HRMO Designate</div>
                <div class="cert-sig-role">Authorized Official</div>
                <div class="cert-date">Date: ________________________</div>
            </div>

            <div class="cert-block">
                <div class="cert-block-tag">[ B ]</div>
                <div class="cert-block-title">
                    Certified: Funds available, cash available, supporting documents complete and proper.
                </div>
                <div class="cert-sig-line"></div>
                @if ($batch->approver && $batch->status !== 'draft')
                    <div class="cert-sig-name">{{ $batch->approver->name }}</div>
                    <div class="cert-sig-role">Accountant</div>
                    <div class="cert-date">
                        Date:
                        {{ $batch->approved_at ? \Carbon\Carbon::parse($batch->approved_at)->format('M d, Y') : '________________________' }}
                    </div>
                @else
                    <div class="cert-sig-name">________________________________</div>
                    <div class="cert-sig-role">Accountant</div>
                    <div class="cert-date">Date: ________________________</div>
                @endif
            </div>

            <div class="cert-block">
                <div class="cert-block-tag">[ C ]</div>
                <div class="cert-block-title">Approved for Payment:</div>
                <div style="font-size:0.82rem; color:var(--text-mid); margin-bottom:14px;">
                    <strong>=P=</strong> ₱{{ number_format($result['net_amount'], 2) }}
                    &emsp; JEV No.: ______________
                </div>
                <div class="cert-sig-line"></div>
                <div class="cert-sig-name">________________________________</div>
                <div class="cert-sig-role">Regional Director / ARD</div>
                <div class="cert-sig-role">Head of Agency / Authorized Representative</div>
                <div class="cert-date">Date: ________________________</div>
            </div>

            <div class="cert-block">
                <div class="cert-block-tag">[ D ]</div>
                <div class="cert-block-title">
                    Certified: Each employee whose name appears above has been paid
                    the amount indicated opposite his/her name.
                </div>
                <div class="cert-sig-line"></div>
                <div class="cert-sig-name">________________________________</div>
                <div class="cert-sig-role">AO V / Cashier</div>
                <div class="cert-date">Date: ________________________</div>
            </div>

        </div>

        @if ($batch->remarks)
        <div style="margin-top:20px; padding:12px 16px; background:#FAFBFF;
                    border:1px solid var(--border); border-radius:var(--radius);
                    font-size:0.83rem;" class="no-print">
            <strong style="color:var(--navy);">Remarks:</strong>
            {{ $batch->remarks }}
        </div>
        @endif

    </div>
</div>

{{-- ── Allowances breakdown modal (PERA + any other applied types) ── --}}
<div id="allowanceModal" class="allowance-modal-overlay no-print"
     onclick="if (event.target === this) closeAllowanceBreakdown()">
    <div class="allowance-modal">
        <div class="allowance-modal-header">
            <strong>Allowances Breakdown</strong>
            <button type="button" onclick="closeAllowanceBreakdown()" aria-label="Close">&times;</button>
        </div>
        <table class="allowance-modal-table">
            <tbody>
                @foreach ($allowanceBreakdown as $line)
                <tr>
                    <td>{{ $line['name'] }}</td>
                    <td class="text-right">₱{{ number_format($line['amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="text-right">₱{{ number_format($allowancesTotalForDisplay, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection

@section('scripts')
<script>
function openAllowanceBreakdown() {
    document.getElementById('allowanceModal').style.display = 'flex';
}
function closeAllowanceBreakdown() {
    document.getElementById('allowanceModal').style.display = 'none';
}
</script>
@endsection
