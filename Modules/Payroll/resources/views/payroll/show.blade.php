{{-- resources/views/payroll/show.blade.php --}}
{{--
    Expects from PayrollController@show:
      $payroll        — PayrollBatch (with entries.employee, entries.deductions, creator, auditLogs.user)
      $entries        — sorted collection
      $totalGross, $totalDeds, $totalNet, $employeeCount
      $auditLogs
--}}

@extends('layouts.app')

@section('title', 'Payroll Batch Detail')
@section('page-title', 'Payroll Batch')

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
}

.approval-step-sub {
    display: block;
    font-size: 0.70rem;
    font-weight: 400;
    line-height: 1.2;
}

.approval-step.done .approval-step-sub {
    color: #6B7280;
}

.approval-step.active .approval-step-sub {
    color: #6B7280;
    font-weight: 500;
}

.approval-step.future .approval-step-sub {
    color: #9CA3AF;
    opacity: 0.8;
}

.approval-step.locked .approval-step-sub {
    color: #6B7280;
}

/* Header card styling */
.header-card {
    border-bottom: none;
    box-shadow: 0 2px 8px rgba(15,27,76,0.09);
}

/* ── Deduction expansion panel ── */
.ded-toggle {
    background: none; border: 1px solid var(--border);
    color: var(--navy); border-radius: 4px;
    padding: 2px 8px; font-size: 0.73rem;
    cursor: pointer; white-space: nowrap;
}
.ded-toggle:hover { background: var(--navy-light); }
.ded-panel { display: none; background: var(--bg); border-top: 1px solid var(--border); padding: 10px 14px; }
.ded-panel.open { display: block; }

/* ── Virtual Scrolling ── */
.table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    width: 100%;
}

#payrollRegisterTable,
#virtualScrollContainer table,
#payrollRegisterTableFooter {
    table-layout: fixed;
    width: 100%;
    min-width: 1260px;
}

.virtual-scroll-container {
    height: 480px;
    overflow-x: hidden;
    overflow-y: auto;
    width: 100%;
    min-width: 1260px;
    position: relative;
}
.virtual-scroll-table {
    table-layout: fixed;
}
.virtual-scroll-thead {
    position: sticky;
    top: 0;
    z-index: 10;
    background: var(--navy);
}
.virtual-scroll-thead th {
    background: var(--navy);
    border-bottom: 2px solid var(--border);
    color: white !important;
}
.virtual-scroll-tfoot {
    position: sticky;
    bottom: 0;
    z-index: 10;
}
.virtual-spacer {
    height: 0;
}
#payrollRegisterTable tbody tr {
    height: 44px;
}
#payrollRegisterTable tbody tr.deduction-detail-row {
    height: auto;
}
.ded-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 4px 16px; font-size: 0.76rem;
}
.ded-row {
    display: flex; justify-content: space-between;
    padding: 2px 0; border-bottom: 1px solid var(--border);
    color: var(--text-mid);
}
.ded-row span:last-child { font-weight: 600; color: var(--text); }
.tfoot-totals td { padding: 12px 14px; font-weight: 700; font-size: 0.88rem; }
.net-warn { background: #FFF8E1 !important; }
.net-warn-badge {
    display: inline-block; margin-top: 3px;
    font-size: 0.67rem; background: #FFE082; color: #7A5900;
    padding: 1px 6px; border-radius: 10px;
    font-weight: 700; letter-spacing: 0.03em;
}
.scroll-hint { font-size: 0.75rem; color: var(--text-light); padding: 6px 14px 0; }
.empty-state { text-align: center; padding: 60px 20px; color: var(--text-light); }
.empty-state-icon { font-size: 2.5rem; margin-bottom: 12px; }
.empty-state h3 { color: var(--text-mid); margin-bottom: 8px; }

/* ── Audit log ── */
.audit-table td { font-size: 0.80rem; vertical-align: top; }
.audit-arrow { color: var(--text-light); margin: 0 4px; }

/* Hide tardiness column in regular payroll register */
#payrollRegisterTable th:nth-child(8),
#virtualScrollContainer table td:nth-child(8),
#payrollRegisterTableFooter td:nth-child(8) {
    display: none;
}

/* ══════════════════════════════════════════════════
   MOBILE RESPONSIVE
══════════════════════════════════════════════════ */
@media (max-width: 768px) {

    /* Page header: stack vertically */
    .page-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 12px !important;
    }
    .page-header > .d-flex {
        width: 100%;
        flex-wrap: wrap;
    }
    .page-header > .d-flex .btn {
        flex: 1;
        justify-content: center;
        text-align: center;
        min-width: calc(50% - 4px);
    }

    /* Approval stepper: scroll horizontally on mobile */
    .approval-stepper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding: 20px 10px;
        height: 90px;
    }
    .approval-step {
        min-width: 70px;
        flex: 0 0 auto;
        padding: 0 5px;
    }
    .approval-step-dot {
        width: 28px;
        height: 28px;
        font-size: 0.75rem;
        margin-bottom: 6px;
    }
    .approval-step-label {
        font-size: 0.70rem;
        text-align: center;
    }
    .approval-step-sub {
        font-size: 0.60rem;
    }

    /* Stat grid: 2 columns on mobile */
    .stat-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important;
    }
    .stat-card { padding: 14px !important; }
    .stat-value { font-size: 1.2rem !important; }

    /* Payroll register table: keep horizontal scroll with sticky # column */
    .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }

    /* Scroll hint more visible */
    .scroll-hint {
        background: var(--bg);
        border-bottom: 1px solid var(--border);
        padding: 8px 14px;
        font-size: 0.78rem;
        color: var(--text-mid);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .scroll-hint::before { content: '↔'; font-size: 1rem; }

    /* Make action buttons in header stack into 2-col grid */
    .payroll-show-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        width: 100%;
    }
    .payroll-show-actions .btn,
    .payroll-show-actions form { width: 100%; }
    .payroll-show-actions form .btn { width: 100%; }

    /* Certification footer: stack */
    .cert-footer > div {
        flex-direction: column !important;
        gap: 10px !important;
    }

    /* Audit table: horizontal scroll */
    .audit-table { min-width: 600px; }
}
</style>
@endsection

@section('content')

@php
    $months = [
        '', 'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];
    // Phase 5: full-month label — no cutoff half
    $periodLabel = ($months[$payroll->period_month] ?? '?')
        . ' ' . \Carbon\Carbon::parse($payroll->period_start)->format('j')
        . '–' . \Carbon\Carbon::parse($payroll->period_end)->format('j')
        . ', ' . $payroll->period_year;

    $statusClass = match ($payroll->status) {
        'draft'              => 'badge-draft',
        'computed'           => 'badge-computed',
        'pending_accountant',
        'pending_rd'         => 'badge-pending',
        'released'           => 'badge-released',
        'locked'             => 'badge-locked',
        default              => 'badge-draft',
    };
    // Phase 5: pending_hr removed
    $statusLabels = [
        'draft'               => 'Draft',
        'computed'            => 'Computed',
        'pending_accountant'  => 'Pending Accountant',
        'pending_rd'          => 'Pending RD / ARD',
        'released'            => 'Released',
        'locked'              => 'Locked',
    ];
    $statusLabel = $statusLabels[$payroll->status] ?? ucfirst(str_replace('_', ' ', $payroll->status));

    $isLocked   = $payroll->status === 'locked';
    $isComputed = ! in_array($payroll->status, ['draft']);

    // CHANGED: hrmo removed — only payroll_officer may compute / re-compute
    $canCompute = in_array($payroll->status, ['draft', 'computed'])
               && auth()->user()->hasRole('payroll_officer');

    $canPullAttendance = in_array($payroll->status, ['draft', 'computed'])
                  && auth()->user()->hasRole('payroll_officer');

    $canRemoveEntry = auth()->user()->hasRole('payroll_officer')
                  && in_array($payroll->status, ['draft', 'computed']);

    $nextAction = null;
    // Phase 5: submit goes directly computed → pending_accountant (no pending_hr step)
    if (auth()->user()->hasRole('payroll_officer')
        && $payroll->status === 'computed') {
        $nextAction = [
            'label'  => 'Submit to Accountant',
            'route'  => route('payroll.submit', $payroll),
            'class'  => 'btn-primary',
            'confirm'=> 'Submit this payroll batch to the Accountant for review?',
        ];
    } elseif (auth()->user()->hasRole('accountant')
              && $payroll->status === 'pending_accountant') {
        $nextAction = [
            'label'  => 'Certify & Forward to RD/ARD',
            'route'  => route('payroll.certify', $payroll),
            'class'  => 'btn-primary',
            'confirm'=> 'Certify funds and forward to RD/ARD for approval?',
        ];
    } elseif (auth()->user()->hasAnyRole(['ard', 'chief_admin_officer'])
              && $payroll->status === 'pending_rd') {
        $nextAction = [
            'label'  => 'Approve & Release',
            'route'  => route('payroll.approve', $payroll),
            'class'  => 'btn-gold',
            'confirm'=> 'Approve and release this payroll batch?',
        ];
    } elseif (auth()->user()->hasRole('cashier')
              && $payroll->status === 'released') {
        $nextAction = [
            'label'  => 'Lock — Disbursement Complete',
            'route'  => route('payroll.lock', $payroll),
            'class'  => 'btn-danger',
            'confirm'=> 'Lock this payroll batch? This marks disbursement as complete and cannot be undone.',
        ];
    }
@endphp

{{-- ═══════════════════════════════════════════════════════════════
     HEADER CARD WITH APPROVAL STEPPER
═══════════════════════════════════════════════════════════════ --}}
<div class="card header-card">
    <div class="card-body" style="padding: 20px 20px 0 20px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
        <!-- Page Header Section -->
        <div class="page-header" style="margin-bottom: 24px;">
            <div class="page-header-left">
                <h1>{{ $periodLabel }}</h1>
                <p>
                    Monthly payroll ·
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    · Created by {{ $payroll->creator->name ?? '—' }}
                    on {{ $payroll->created_at->format('M d, Y') }}
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap payroll-show-actions">
                <a href="{{ route('payroll.index') }}" class="btn btn-outline btn-sm">← All Batches</a>

    @if ($canPullAttendance)
        {{-- Phase 5: separate Pull, Edit, and Compute buttons --}}
        <form method="POST" action="{{ route('payroll.pullAttendance', $payroll) }}" id="pullAttendanceForm">
            @csrf
            <button type="button" class="btn btn-outline btn-sm" onclick="confirmPullAttendance()">
                {{ $snapshotCount > 0 ? '🔄 Re-pull Attendance' : '📥 Pull Attendance' }}
                @if ($snapshotCount > 0)
                    <span style="font-size:0.72rem; opacity:0.8;">({{ $snapshotCount }}/{{ $activeCount }})</span>
                @endif
            </button>
        </form>
    @endif

    @if ($canCompute)
        <form method="POST" action="{{ route('payroll.compute', $payroll) }}" id="computeForm">
            @csrf
            @if ($snapshotCount === 0)
                <button type="button" class="btn btn-gold btn-sm" disabled title="Pull attendance first">
                    ⚙ {{ $payroll->status === 'draft' ? 'Compute Payroll' : 'Re-compute' }}
                </button>
            @else
                <button type="button" class="btn btn-gold btn-sm" onclick="confirmCompute()">
                    ⚙ {{ $payroll->status === 'draft' ? 'Compute Payroll' : 'Re-compute' }}
                </button>
            @endif
        </form>
    @endif

            @if ($nextAction)
                <form method="POST" action="{{ $nextAction['route'] }}" id="nextActionForm">
                    @csrf
                    <button type="button" class="btn {{ $nextAction['class'] }} btn-sm" onclick="confirmNextAction()">
                        ✔ {{ $nextAction['label'] }}
                    </button>
                </form>
            @endif

          {{-- NEW: --}}
    <!-- {{-- GAP-01: Payroll Register PDF view missing — commented out until implemented
    @if ($isComputed)
        <a href="{{ route('reports.payroll-register', ['batch_id' => $payroll->id]) }}"
           class="btn btn-outline btn-sm" target="_blank">
            📄 Payroll Register PDF
        </a>
    @endif
    --}} -->

    {{-- Payslip generation — only after release --}}
    @if (in_array($payroll->status, ['released', 'locked']))
        <button class="btn btn-outline btn-sm" onclick="openPayslipModal()">
            🧾 Generate Payslips
        </button>
    @elseif ($isComputed)
        <button class="btn btn-outline btn-sm" disabled
                title="Payslips available after the batch is released"
                style="opacity:0.45; cursor:not-allowed;">
            🧾 Payslips (Pending Release)
        </button>
    @endif

            @if ($payroll->status === 'released' || auth()->user()->hasRole('cashier'))
                <a href="{{ route('payroll.verify', $payroll) }}" class="btn btn-outline btn-sm">
                    📋 Verify Net Pay
                </a>
            @endif
        </div>
    </div>

    <!-- Approval Stepper Section -->
    @include('payroll::payroll._approval_bar')
    </div>
</div>

@if ($payroll->remarks)
    <div class="alert" style="background:#FAFBFF; border-color:var(--border); margin-top:14px;">
        <strong style="color:var(--navy);">Remarks:</strong> {{ $payroll->remarks }}
    </div>
@endif
@if (session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif


{{-- ═══════════════════════════════════════════════════════════════
     SUMMARY STAT CARDS
═══════════════════════════════════════════════════════════════ --}}
<div style="padding: 20px 24px; margin-top: 20px;">
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Employees</div>
            <div class="stat-value">{{ $employeeCount }}</div>
            <div class="stat-sub">Active regular employees</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-label">Total Gross</div>
            <div class="stat-value">₱{{ number_format($totalGross, 2) }}</div>
            <div class="stat-sub">Basic + all allowances</div>
        </div>
        <div class="stat-card red">
            <div class="stat-label">Total Deductions</div>
            <div class="stat-value">₱{{ number_format($totalDeds, 2) }}</div>
            <div class="stat-sub">All deduction lines</div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Total Net Pay</div>
            <div class="stat-value">₱{{ number_format($totalNet, 2) }}</div>
            <div class="stat-sub">Gross − Total Deductions</div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     ATTENDANCE PANEL (draft / computed only)
═══════════════════════════════════════════════════════════════ --}}
@if (in_array($payroll->status, ['draft', 'computed']))
<div class="card" style="margin-bottom:20px;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3>📋 Attendance Data</h3>
        @if ($snapshotCount > 0)
            <span class="badge badge-computed" style="font-size:0.75rem;">
                {{ $snapshotCount }}/{{ $activeCount }} pulled
                @if ($correctedCount > 0) · {{ $correctedCount }} corrected @endif
            </span>
        @else
            <span class="badge badge-draft" style="font-size:0.75rem;">Not pulled yet</span>
        @endif
    </div>
    <div class="card-body">

        @if ($snapshotCount === 0)
            <div class="alert alert-warning" style="margin-bottom:0;">
                <strong>⚠ Attendance has not been pulled yet.</strong>
                The Compute button is disabled until attendance is pulled.
                Without this step all employees would be computed with zero tardiness and zero LWOP.
            </div>
        @elseif ($snapshotCount < $activeCount)
            <div class="alert alert-warning" style="margin-bottom:12px;">
                <strong>⚠ Partial pull:</strong> {{ $snapshotCount }} of {{ $activeCount }} employees have attendance data. Consider re-pulling.
            </div>
        @else
            <div class="alert" style="background:#F1FAF5; border-color:#A8D5B5; margin-bottom:12px;">
                ✅ Attendance pulled for all {{ $snapshotCount }} employees.
                @if ($correctedCount > 0)
                    <strong>{{ $correctedCount }} record(s) manually corrected by HR.</strong>
                @endif
                Review below, then compute.
            </div>
        @endif

        @if ($snapshots->count() > 0)
            <button type="button" class="btn btn-outline btn-sm" style="margin-bottom:12px;"
                    onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">
                👁 Show / Hide Attendance Records ({{ $snapshots->count() }})
            </button>
            <div style="display:none; overflow-x:auto;">
                <table style="font-size:0.82rem; min-width:600px; width:100%;">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th class="text-center">Days Present</th>
                            <th class="text-center">LWOP Days</th>
                            <th class="text-center">Late (min)</th>
                            <th class="text-center">Undertime (min)</th>
                            <th class="text-center">Source</th>
                            @if ($canCompute)
                                <th class="text-center">Edit</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($snapshots as $snap)
                            <tr style="{{ $snap->is_corrected ? 'background:#FFF8E1;' : '' }}">
                                <td>
                                    <div class="fw-bold" style="font-size:0.83rem;">
                                        {{ optional($snap->employee)->last_name }}, {{ optional($snap->employee)->first_name }}
                                    </div>
                                    <div class="text-muted" style="font-size:0.72rem;">{{ optional($snap->employee)->employee_no }}</div>
                                </td>
                                <td class="text-center">{{ number_format($snap->days_present, 1) }}</td>
                                <td class="text-center {{ $snap->lwop_days > 0 ? 'text-red fw-bold' : '' }}">
                                    {{ number_format($snap->lwop_days, 3) }}
                                </td>
                                <td class="text-center {{ $snap->late_minutes > 0 ? 'text-red' : '' }}">
                                    {{ $snap->late_minutes }}
                                </td>
                                <td class="text-center {{ $snap->undertime_minutes > 0 ? 'text-red' : '' }}">
                                    {{ $snap->undertime_minutes }}
                                </td>
                                <td class="text-center">
                                    @if ($snap->is_corrected)
                                        <span class="badge badge-pending" title="{{ $snap->correction_note }}">✏ HR Corrected</span>
                                    @else
                                        <span class="badge badge-draft">HRIS API</span>
                                    @endif
                                </td>
                                @if ($canCompute)
                                    <td class="text-center">
                                        <a href="{{ route('payroll.attendance.edit', [$payroll, $snap]) }}"
                                           class="btn btn-outline btn-sm"
                                           style="font-size:0.72rem; padding:2px 8px;">
                                            ✏ Edit
                                        </a>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
</div>
@endif

@if ($payroll->status === 'draft' && $employeeCount === 0)
    <div class="alert alert-warning">
        No entries yet. Click <strong>Compute Payroll</strong> above to generate all employee entries.
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     PAYROLL REGISTER TABLE
═══════════════════════════════════════════════════════════════ --}}
@if ($employeeCount > 0)
<div class="card" style="overflow:visible;">
    <div class="card-header">
        <h3>Payroll Register — {{ $periodLabel }} ({{ $employeeCount }} Employees)</h3>
<div class="d-flex gap-2 align-center flex-wrap">
    <span class="text-muted" style="font-size:0.78rem;">
        Click <em>Deductions ▾</em> to expand per-employee breakdown.
    </span>
    @if (in_array($payroll->status, ['released', 'locked']))
        <span class="text-muted" style="font-size:0.78rem;">
            · Click <em>Payslip</em> to view / print individual slips.
        </span>
    @endif
</div>
    </div>

    <div class="scroll-hint">Scroll vertically for more employees</div>

    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table id="payrollRegisterTable" class="virtual-scroll-table">
                <colgroup>
                    <col style="width:40px;">
                    <col style="width:180px;">
                    <col style="width:80px;">
                    <col style="width:100px;">
                    @foreach ($allowanceColumns as $col)
                    <col style="width:85px;">
                    @endforeach
                    <col style="width:110px;">
                    <col style="width:100px;">
                    <col style="width:80px;">
                    <col style="width:90px;">
                    <col style="width:110px;">
                    <col style="width:110px;">
                    <col style="width:150px;">
                    <col style="width:90px;">
                </colgroup>
                <thead class="virtual-scroll-thead">
                    <tr>
                        <th style="color:white;">#</th>
                        <th style="color:white;">Employee</th>
                        <th style="color:white;">SG–Step</th>
                        <th style="color:white;" class="text-right">Basic Earned</th>
                        @foreach ($allowanceColumns as $col)
                        <th style="color:white;" class="text-right">{{ $col->name }}</th>
                        @endforeach
                        <th style="background:rgba(249,168,37,0.22); color:white;" class="text-right">Gross</th>
                        <th style="color:white;" class="text-right">Tardiness</th>
                        <th style="color:white;" class="text-right">LWOP</th>
                        <th style="color:white;" class="text-right">Ded. Lines</th>
                        <th style="background:rgba(183,28,28,0.12); color:white;" class="text-right">Total Ded.</th>
                        <th style="background:rgba(27,94,32,0.12); color:white;" class="text-right">Net Pay</th>
                        <th style="color:white;">Remarks</th>
                        <th style="color:white;">Actions</th>
                    </tr>
                </thead>
            </table>

            {{-- Virtual Scroll Container for tbody --}}
            <div class="virtual-scroll-container" id="virtualScrollContainer">
                <table class="virtual-scroll-table">
                    <colgroup>
                        <col style="width:40px;">
                        <col style="width:180px;">
                        <col style="width:80px;">
                        <col style="width:100px;">
                        <col style="width:85px;">
                        <col style="width:85px;">
                        <col style="width:110px;">
                        <col style="width:100px;">
                        <col style="width:80px;">
                        <col style="width:90px;">
                        <col style="width:110px;">
                        <col style="width:110px;">
                        <col style="width:150px;">
                        <col style="width:90px;">
                    </colgroup>
                    <tbody id="virtualScrollTbody">
                        {{-- Rows rendered by JavaScript --}}
                    </tbody>
                </table>
            </div>

            <table id="payrollRegisterTableFooter" class="virtual-scroll-table" style="margin-top: -1px;">
                <colgroup>
                    <col style="width:40px;">
                    <col style="width:180px;">
                    <col style="width:80px;">
                    <col style="width:100px;">
                    @foreach ($allowanceColumns as $col)
                    <col style="width:85px;">
                    @endforeach
                    <col style="width:110px;">
                    <col style="width:100px;">
                    <col style="width:80px;">
                    <col style="width:90px;">
                    <col style="width:110px;">
                    <col style="width:110px;">
                    <col style="width:150px;">
                    <col style="width:90px;">
                </colgroup>
                <tfoot class="virtual-scroll-tfoot">
                    <tr class="tfoot-totals" style="background:var(--navy); color:white;">
                        <td style="padding:12px 14px; color:rgba(255,255,255,0.7); font-size:0.82rem;">
                            #
                        </td>
                        <td colspan="2" style="padding:12px 14px; color:rgba(255,255,255,0.7); font-size:0.82rem;">
                            TOTALS - {{ $employeeCount }} employee{{ $employeeCount !== 1 ? 's' : '' }}
                        </td>
                        <td class="text-right" style="color:white;">
                            ₱{{ number_format($payroll->entries->sum('basic_salary'), 2) }}
                        </td>
                        @foreach ($allowanceColumns as $col)
                        <td class="text-right" style="color:white;">
                            @php $colTotal = $allowanceTotals[$col->code] ?? 0; @endphp
                            {{ $colTotal > 0 ? '₱' . number_format($colTotal, 2) : '' }}
                        </td>
                        @endforeach
                        <td class="text-right" style="color:var(--gold); background:rgba(249,168,37,0.15);">
                            ₱{{ number_format($totalGross, 2) }}
                        </td>
                        <td class="text-right" style="color:white;">
                            ₱{{ number_format($payroll->entries->sum('tardiness') + $payroll->entries->sum('undertime'), 2) }}
                        </td>
                        <td class="text-right" style="color:white;">
                            ₱{{ number_format($payroll->entries->sum('lwop_deduction'), 2) }}
                        </td>
                        <td></td>
                        <td class="text-right" style="color:white;">
                            ₱{{ number_format($totalDeds, 2) }}
                        </td>
                        <td class="text-right" style="color:white; font-size:1rem;">
                            ₱{{ number_format($totalNet, 2) }}
                        </td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            {{-- Hidden data store for virtual scrolling --}}
            @php
                $virtualRows = [];
                foreach ($entries as $i => $entry) {
                    $netWarn = $entry->net_amount < 5000;
                    $tardy = ($entry->tardiness ?? 0) + ($entry->undertime ?? 0);
                    $lwop = $entry->lwop_deduction ?? 0;
                    $dedCount = $entry->deductions->count();

                    $deductions = [];
                    foreach ($entry->deductions->sortBy(fn ($d) => optional($d->deductionType)->display_order ?? 99) as $ded) {
                        $deductions[] = [
                            'name' => $ded->name,
                            'amount' => $ded->amount,
                        ];
                    }

                    $deductionNames = collect($deductions)->pluck('name')->values()->all();
                    $deductionSummary = '—';
                    if (count($deductionNames) > 0) {
                        $shown = array_slice($deductionNames, 0, 2);
                        $more  = max(0, count($deductionNames) - count($shown));
                        $deductionSummary = implode(', ', $shown) . ($more > 0 ? " +{$more}" : '');
                    }

                    $virtualRows[] = [
                        'id' => $entry->id,
                        'index' => $i + 1,
                        'netWarn' => $netWarn,
                        'employee_name' => $entry->employee->full_name,
                        'position' => $entry->employee->position_title,
                        'sg' => $entry->employee->salary_grade,
                        'step' => $entry->employee->step,
                        'basic_salary' => $entry->basic_salary,
                        'allowances' => $allowanceAmounts($entry),
                        'gross_income' => $entry->gross_income,
                        'tardy' => $tardy,
                        'lwop' => $lwop,
                        'dedCount' => $dedCount,
                        'deductionSummary' => $deductionSummary,
                        'total_deductions' => $entry->total_deductions,
                        'net_amount' => $entry->net_amount,
                        'remarks' => $entry->override_notes ?? '',
                        'has_payslip' => in_array($payroll->status, ['released', 'locked']),
                        'payroll_id' => $payroll->id,
                        'deductions' => $deductions,
                        'attendance_deduction' => $entry->tardiness + $entry->undertime + ($entry->lwop_deduction ?? 0),
                    ];
                }
            @endphp
            <script>
                window.virtualRowData = @json($virtualRows);
                window.allowanceColumnCodes = @json($allowanceColumns->pluck('code'));
                window.payrollStatus = @json($payroll->status);
                window.payrollId = @json($payroll->id);
                window.canRemoveEntry = @json($canRemoveEntry);
                window.csrfToken = @json(csrf_token());
            </script>
        </div>
    </div>

    @if ($isComputed)
        <div class="card-body cert-footer" style="background:#FAFBFF; border-top:1px solid var(--border); padding:14px 20px;">
            <div class="d-flex gap-2 flex-wrap"
                 style="justify-content:space-between; align-items:flex-end; font-size:0.82rem; color:var(--text-mid);">
                <div>
                    <strong>Prepared by:</strong>
                    {{ $payroll->creator->name ?? '—' }}
                    <span class="text-muted">· {{ $payroll->created_at->format('M d, Y') }}</span>
                </div>
                @if ($payroll->approved_by)
                    <div>
                        <strong>Approved by:</strong>
                        {{ optional($payroll->approver)->name ?? '—' }}
                        @if ($payroll->approved_at)
                            <span class="text-muted">· {{ \Carbon\Carbon::parse($payroll->approved_at)->format('M d, Y') }}</span>
                        @endif
                    </div>
                @endif
                @if ($payroll->released_at)
                    <div>
                        <strong>Released:</strong>
                        <span class="text-muted">{{ \Carbon\Carbon::parse($payroll->released_at)->format('M d, Y g:i A') }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@else

<div class="card">
    <div class="card-body empty-state">
        <div class="empty-state-icon">📊</div>
        <h3>No Entries Yet</h3>
        <p>Click <strong>Compute Payroll</strong> above to generate entries for all active employees.</p>
    </div>
</div>

@endif

{{-- ═══════════════════════════════════════════════════════════════
     AUDIT LOG
═══════════════════════════════════════════════════════════════ --}}
@if ($auditLogs->isNotEmpty())
<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h3>Audit Log</h3>
        <span class="text-muted" style="font-size:0.80rem;">
            {{ $auditLogs->count() }} entr{{ $auditLogs->count() === 1 ? 'y' : 'ies' }}
        </span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Date / Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Status Change</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($auditLogs as $log)
                        <tr>
                            <td style="white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($log->performed_at)->format('M d, Y g:i A') }}
                            </td>
                            <td>{{ $log->user->name ?? '—' }}</td>
                            <td>{{ $log->action }}</td>
                            <td>
                                @if ($log->old_value || $log->new_value)
                                    <span class="badge badge-draft" style="font-size:0.70rem;">
                                        {{ $log->old_value ?? '—' }}
                                    </span>
                                    <span class="audit-arrow">→</span>
                                    <span class="badge badge-computed" style="font-size:0.70rem;">
                                        {{ $log->new_value ?? '—' }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     PAYSLIP GENERATION MODAL
═══════════════════════════════════════════════════════════════ --}}
<div id="payslipModal" style="
    display:none; position:fixed; inset:0; z-index:1000;
    background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div style="
        background:#fff; border-radius:var(--radius); box-shadow:0 8px 32px rgba(0,0,0,0.18);
        padding:28px 32px; width:100%; max-width:440px; margin:16px;">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="color:var(--navy); margin:0;">Generate Payslips</h3>
            <button onclick="closePayslipModal()"
                    style="background:none; border:none; font-size:1.4rem; color:var(--text-light);
                           cursor:pointer; line-height:1;">&times;</button>
        </div>

        <p style="font-size:0.85rem; color:var(--text-mid); margin-bottom:20px;">
            <strong>{{ $periodLabel }}</strong> ·
            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
        </p>

        {{-- Monthly consolidated payslip (whole month) --}}
        <label id="opt-consolidated" class="payslip-opt selected"
               style="display:flex; align-items:flex-start; gap:12px; padding:14px 16px;
                      border:2px solid var(--navy); border-radius:var(--radius);
                      cursor:pointer; margin-bottom:20px; transition:border-color .15s;">
            <input type="radio" name="payslipMode" value="consolidated"
                   checked onchange="selectOpt('consolidated')"
                   style="margin-top:3px; accent-color:var(--navy);">
            <div>
                <div style="font-weight:700; font-size:0.88rem; color:var(--navy);">
                    Monthly Payslip
                    <!-- <span style="font-size:0.72rem; background:#E8F0FE; color:var(--navy);
                                 padding:1px 8px; border-radius:10px; margin-left:6px;">
                        Recommended
                    </span> -->
                </div>
                <div style="font-size:0.78rem; color:var(--text-mid); margin-top:3px;">
                    Single payslip showing both 1–15 and 16–30/31 cut-offs side by side.

                </div>
            </div>
        </label>

        {{-- Employee filter (optional) --}}
        <div style="margin-bottom:20px;">
            <label style="font-size:0.75rem; font-weight:700; text-transform:uppercase;
                          letter-spacing:.05em; color:var(--text-mid); display:block; margin-bottom:6px;">
                Employee (leave blank for all)
            </label>
            <select id="payslipEmployee"
                    style="width:100%; height:38px; border:1px solid var(--border);
                           border-radius:var(--radius); padding:0 10px; font-size:0.85rem;">
                <option value="">— All Employees —</option>
                @foreach ($entries as $entry)
                    <option value="{{ $entry->id }}">{{ $entry->employee->full_name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Progress bar (hidden by default) --}}
        <div id="payslipProgress" style="display:none; margin-top:20px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span style="font-size:0.78rem; font-weight:600; color:var(--navy);">Generating Payslips...</span>
                <span id="progressPercent" style="font-size:0.78rem; font-weight:600; color:var(--navy);">0%</span>
            </div>
            <div style="width:100%; height:8px; background:#E5E7EB; border-radius:4px; overflow:hidden;">
                <div id="progressBar" style="width:0%; height:100%; background:var(--navy); transition:width 0.3s ease;"></div>
            </div>
            <div id="progressStatus" style="font-size:0.75rem; color:var(--text-mid); margin-top:6px;">Initializing...</div>
        </div>

        <div id="payslipButtons" style="display:flex; gap:10px; justify-content:flex-end;">
            <button onclick="closePayslipModal()" class="btn btn-outline btn-sm">Cancel</button>
            <button onclick="submitPayslip()" class="btn btn-primary btn-sm">
                📄 Generate PDF
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const snapshotCount = {{ $snapshotCount ?? 0 }};

function confirmPullAttendance() {
    const message = snapshotCount > 0
        ? 'Re-pulling will reset any manual HR corrections. Continue?'
        : 'Pull attendance from HRIS for all active employees?';

    Swal.fire({
        title: 'Pull Attendance?',
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Pull Attendance',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0F1B4C',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading with progress bar
            Swal.fire({
                title: 'Pulling Attendance...',
                html: '<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div></div>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Submit form via AJAX
            const form = document.getElementById('pullAttendanceForm');
            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message || 'Attendance pulled successfully.',
                        icon: 'success',
                        confirmButtonColor: '#0F1B4C'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to pull attendance.',
                        icon: 'error',
                        confirmButtonColor: '#0F1B4C'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while pulling attendance.',
                    icon: 'error',
                    confirmButtonColor: '#0F1B4C'
                });
            });
        }
    });
}

function confirmCompute() {
    const isRecompute = '{{ $payroll->status }}' !== 'draft';

    Swal.fire({
        title: isRecompute ? 'Re-compute Payroll' : 'Compute Payroll',
        html: `
            <div style="text-align:left; font-size:0.92rem;">
                <p style="color:#6b7280; margin-bottom:12px;">
                    Choose which components to apply this pass. Unchecked components
                    keep their last computed values.
                </p>
                <label style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                    <input type="checkbox" id="opt_attendance" checked> Apply Attendance (tardiness/undertime)
                </label>
                <label style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                    <input type="checkbox" id="opt_deductions" checked> Apply Deductions (statutory)
                </label>
                <label style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                    <input type="checkbox" id="opt_allowances" checked> Apply Allowances (PERA/RATA/etc.)
                </label>
                <label style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                    <input type="checkbox" id="opt_lwop" checked> Apply LWOP
                </label>
                <label style="display:flex; gap:8px; align-items:center;">
                    <input type="checkbox" id="opt_other"> Apply Other Adjustments
                </label>
                <p id="noneSelectedNote" style="display:none; color:#b45309; font-size:0.82rem; margin-top:10px;">
                    No components selected — this will compute base salary only, carrying over
                    everything else from the last compute pass.
                </p>
                <hr style="margin:14px 0; border-color:#eee;">
                <label style="display:flex; gap:8px; align-items:center; color:#b91c1c;">
                    <input type="checkbox" id="opt_force"> Force re-compute (overwrites manually overridden entries)
                </label>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: isRecompute ? 'Re-compute' : 'Compute',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0F1B4C',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true,
        didOpen: () => {
            const boxes = Swal.getPopup().querySelectorAll('input[type="checkbox"]:not(#opt_force)');
            const note = document.getElementById('noneSelectedNote');
            boxes.forEach(b => b.addEventListener('change', () => {
                const anyChecked = [...boxes].some(c => c.checked);
                note.style.display = anyChecked ? 'none' : 'block';
            }));
        },
        preConfirm: () => ({
            apply_attendance: document.getElementById('opt_attendance').checked,
            apply_deductions: document.getElementById('opt_deductions').checked,
            apply_allowances: document.getElementById('opt_allowances').checked,
            apply_lwop: document.getElementById('opt_lwop').checked,
            apply_other_adjustments: document.getElementById('opt_other').checked,
            force: document.getElementById('opt_force').checked,
        })
    }).then((result) => {
        if (!result.isConfirmed) return;

        if (result.value.force) {
            Swal.fire({
                title: 'Force re-compute?',
                text: 'This will overwrite any manually overridden entries in this batch. This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, force re-compute',
                confirmButtonColor: '#b91c1c',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true
            }).then((confirmResult) => {
                if (confirmResult.isConfirmed) {
                    submitComputeOptions(result.value);
                }
            });
        } else {
            submitComputeOptions(result.value);
        }
    });
}

function submitComputeOptions(options) {
    Swal.fire({
        title: 'Computing Payroll...',
        html: '<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div></div>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const form = document.getElementById('computeForm');
    const formData = new FormData(form);
    Object.entries(options).forEach(([k, v]) => formData.set(k, v ? '1' : '0'));

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: (data.skipped > 0) ? 'Completed with skipped entries' : 'Success!',
                text: data.message || 'Payroll computed successfully.',
                icon: (data.skipped > 0) ? 'warning' : 'success',
                confirmButtonColor: '#0F1B4C'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message || 'Failed to compute payroll.',
                icon: 'error',
                confirmButtonColor: '#0F1B4C'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Error',
            text: 'An error occurred while computing payroll.',
            icon: 'error',
            confirmButtonColor: '#0F1B4C'
        });
    });
}

function openDeductionModal(entryId) {
    const row = (window.virtualRowData || []).find(r => String(r.id) === String(entryId));
    if (!row) return;

    const lines = (row.deductions || []).map(d => `
        <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #eee;">
            <span>${d.name}</span>
            <span>${formatCurrency(d.amount)}</span>
        </div>
    `).join('');

    const attendanceLine = row.attendance_deduction > 0 ? `
        <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #eee; color:#b91c1c;">
            <span>Attendance deduction (tardiness/undertime/LWOP)</span>
            <span>${formatCurrency(row.attendance_deduction)}</span>
        </div>` : '';

    Swal.fire({
        title: `Deduction Breakdown — ${row.employee_name}`,
        html: `
            <div style="text-align:left; font-size:0.88rem; max-height:320px; overflow-y:auto;">
                ${lines || '<p style="color:#9ca3af;">No statutory deduction lines.</p>'}
                ${attendanceLine}
                <div style="display:flex; justify-content:space-between; padding-top:10px; margin-top:6px; border-top:2px solid #0F1B4C; font-weight:700;">
                    <span>Total</span>
                    <span>${formatCurrency(row.total_deductions)}</span>
                </div>
            </div>
        `,
        confirmButtonText: 'Close',
        confirmButtonColor: '#0F1B4C',
        width: 420
    });
}


// ── Payslip modal ──────────────────────────────────────────
function openPayslipModal() {
    const m = document.getElementById('payslipModal');
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closePayslipModal() {
    const m = document.getElementById('payslipModal');
    m.style.display = 'none';
    document.body.style.overflow = '';
}
// ── Combined Pull Attendance & Compute ───────────────────────
function confirmPullAndCompute() {
    const periodLabel = '{{ $payroll->period_month_name }} {{ $payroll->cutoff }}, {{ $payroll->period_year }}';

    Swal.fire({
        title: 'Pull Attendance & Compute Payroll?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.25rem;font-weight:600;color:#0F1B4C;margin-bottom:8px;">${periodLabel}</div>
            <p style="color:#6b7280;font-size:0.95rem;">This will pull attendance from HRIS and compute payroll for all active employees.</p>
            <p style="color:#ef4444;font-size:0.85rem;margin-top:8px;"><strong>Existing entries will be overwritten.</strong></p>
        </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Pull & Compute',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0F1B4C',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            executePullAndCompute(periodLabel);
        }
    });
}

async function executePullAndCompute(periodLabel) {
    // Show loading modal
    Swal.fire({
        title: '<span style="color:#0F1B4C;">Processing Payroll...</span>',
        html: `<div style="margin-top:10px;text-align:center;">
            <div style="font-size:1.1rem;color:#0F1B4C;margin-bottom:8px;">${periodLabel}</div>
            <p style="font-size:0.9rem;color:#6b7280;">Step 1: Pulling attendance from HRIS...</p>
            <p style="font-size:0.9rem;color:#6b7280;">Step 2: Computing payroll entries...</p>
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
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');

        const response = await fetch('{{ route("payroll.pullAndCompute", $payroll) }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Process Completed!',
                html: `<div style="text-align:center;">
                    <div style="font-size:1.1rem;color:#0F1B4C;margin-bottom:8px;">${periodLabel}</div>
                    <p style="color:#6b7280;font-size:0.9rem;">${result.message}</p>
                </div>`,
                confirmButtonColor: '#0F1B4C'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Process Failed',
                html: `<div style="text-align:left;">
                    <p>${result.message}</p>
                </div>`,
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#0F1B4C',
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#6B7280'
            });
        }

    } catch (error) {
        let errorMessage = 'An unexpected error occurred while processing payroll.';

        if (error.message.includes('NetworkError') || error.message.includes('fetch')) {
            errorMessage = 'Unable to complete the request. Please check your internet connection.';
        } else if (error.message.includes('500')) {
            errorMessage = 'The server encountered an error during payroll processing. Please contact the system administrator.';
        } else if (error.message) {
            errorMessage = error.message;
        }

        Swal.fire({
            icon: 'error',
            title: 'Processing Error',
            html: `<div style="text-align:left;">
                <p>${errorMessage}</p>
                <p style="margin-top:12px;font-size:0.85rem;color:#6b7280;">
                    <strong>Troubleshooting:</strong><br>
                    • Verify payroll batch is in draft status<br>
                    • Check HRIS connection for attendance data<br>
                    • Contact IT if the problem persists
                </p>
            </div>`,
            confirmButtonText: 'Try Again',
            confirmButtonColor: '#0F1B4C',
            showCancelButton: true,
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#6B7280'
        });
    }
}

function selectOpt(val) {
    document.getElementById('opt-consolidated').style.borderColor =
        val === 'consolidated' ? 'var(--navy)' : 'var(--border)';
}
function submitPayslip() {
    const mode     = document.querySelector('input[name="payslipMode"]:checked').value;
    const entryId  = document.getElementById('payslipEmployee').value;
    const base     = '{{ route("payroll.payslips.generate", $payroll) }}';

    // Show progress bar, hide buttons
    document.getElementById('payslipButtons').style.display = 'none';
    document.getElementById('payslipProgress').style.display = 'block';

    // Generate unique job ID for progress tracking
    const jobId = 'payslip_' + Date.now();

    // Start AJAX request to generate payslips
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('mode', mode);
    if (entryId) formData.append('entry_id', entryId);
    formData.append('job_id', jobId);

    // Simulate progress updates
    let progress = 0;
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const progressStatus = document.getElementById('progressStatus');

    const progressInterval = setInterval(() => {
        if (progress < 90) {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            progressBar.style.width = progress + '%';
            progressPercent.textContent = Math.round(progress) + '%';

            if (progress < 30) {
                progressStatus.textContent = 'Preparing data...';
            } else if (progress < 60) {
                progressStatus.textContent = 'Generating payslips...';
            } else {
                progressStatus.textContent = 'Finalizing...';
            }
        }
    }, 500);

    fetch(base, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        clearInterval(progressInterval);

        if (data.success) {
            // Download the file from base64 content
            if (data.file_content) {
                const binaryString = atob(data.file_content);
                const bytes = new Uint8Array(binaryString.length);
                for (let i = 0; i < binaryString.length; i++) {
                    bytes[i] = binaryString.charCodeAt(i);
                }
                const blob = new Blob([bytes], { type: data.content_type || 'application/octet-stream' });
                const url = URL.createObjectURL(blob);

                const link = document.createElement('a');
                link.href = url;
                link.download = data.filename || 'payslips.zip';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }

            // Show success message
            progressStatus.textContent = 'Complete! Downloading...';
            progressBar.style.width = '100%';
            progressPercent.textContent = '100%';

            setTimeout(() => {
                closePayslipModal();
                // Reset modal state
                document.getElementById('payslipButtons').style.display = 'flex';
                document.getElementById('payslipProgress').style.display = 'none';
                document.getElementById('progressBar').style.width = '0%';
                document.getElementById('progressPercent').textContent = '0%';
                document.getElementById('progressStatus').textContent = 'Initializing...';
            }, 2000);
        } else {
            // Show error
            clearInterval(progressInterval);
            progressStatus.textContent = 'Error: ' + (data.message || 'Failed to generate payslips');
            progressBar.style.background = '#EF4444';

            setTimeout(() => {
                closePayslipModal();
                document.getElementById('payslipButtons').style.display = 'flex';
                document.getElementById('payslipProgress').style.display = 'none';
                document.getElementById('progressBar').style.width = '0%';
                document.getElementById('progressBar').style.background = 'var(--navy)';
                document.getElementById('progressPercent').textContent = '0%';
                document.getElementById('progressStatus').textContent = 'Initializing...';
            }, 3000);
        }
    })
    .catch(error => {
        clearInterval(progressInterval);
        progressStatus.textContent = 'Error: ' + error.message;
        progressBar.style.background = '#EF4444';

        setTimeout(() => {
            closePayslipModal();
            document.getElementById('payslipButtons').style.display = 'flex';
            document.getElementById('payslipProgress').style.display = 'none';
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('progressBar').style.background = 'var(--navy)';
            document.getElementById('progressPercent').textContent = '0%';
            document.getElementById('progressStatus').textContent = 'Initializing...';
        }, 3000);
    });
}
// Close modal on backdrop click
document.getElementById('payslipModal').addEventListener('click', function(e) {
    if (e.target === this) closePayslipModal();
});

async function confirmNextAction() {
    const form = document.getElementById('nextActionForm');
    const confirmMessage = "{{ $nextAction['confirm'] ?? 'Are you sure?' }}";

    const result = await Swal.fire({
        title: 'Confirm Action',
        text: confirmMessage,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0F1B4C',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        Swal.fire({
            title: '<span style="color:#0F1B4C;">Processing…</span>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            // Ensure csrfToken is a string, not a function
            const csrfToken = typeof window.csrfToken === 'function' ? window.csrfToken() : window.csrfToken;

            console.log('Submitting action to:', form.action);
            const resp = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(form)
            });

            console.log('Response status:', resp.status, 'OK:', resp.ok);

            if (!resp.ok) {
                const errorText = await resp.text();
                console.error('Response not OK:', errorText);
                throw new Error(`HTTP ${resp.status}: ${errorText}`);
            }

            const data = await resp.json();
            console.log('Response data:', data);

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message || 'Action completed successfully.',
                    confirmButtonColor: '#0F1B4C'
                }).then(() => window.location.reload());
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Action Failed',
                    text: data.message || 'Unable to complete the action.',
                    confirmButtonColor: '#0F1B4C'
                });
            }
        } catch (error) {
            console.error('Error submitting action:', error);
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'An error occurred while processing the action.',
                confirmButtonColor: '#0F1B4C'
            });
        }
    }
}

// ═══════════════════════════════════════════════════════════════
// VIRTUAL SCROLLING FOR PAYROLL REGISTER TABLE
// ═══════════════════════════════════════════════════════════════
(function() {
    const ROW_HEIGHT = 44; // Height of each main row in pixels
    const OVERSCAN = 3;    // Extra rows to render above/below viewport
    const VIEWPORT_HEIGHT = 480;

    const container = document.getElementById('virtualScrollContainer');
    const tbody = document.getElementById('virtualScrollTbody');
    const rows = window.virtualRowData || [];
    const totalRows = rows.length;

    if (totalRows === 0) return;

    function formatCurrency(amount) {
        return '₱' + Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    // Exposed globally — openDeductionModal() (defined outside this IIFE)
    // also needs it, and it's harmless/idempotent to call from elsewhere.
    window.formatCurrency = formatCurrency;

    function renderRow(row, index) {
        const netWarnClass = row.netWarn ? 'net-warn' : '';
        const tardyClass = row.tardy > 0 ? 'text-red' : '';
        const lwopClass = row.lwop > 0 ? 'text-red' : '';
        const netClass = row.netWarn ? 'text-red' : '';
        const netWarnBadge = row.netWarn ? '<span class="net-warn-badge">Below ₱5K</span>' : '';

        const tardyDisplay = row.tardy > 0 ? formatCurrency(row.tardy) : '—';
        const lwopDisplay = row.lwop > 0 ? formatCurrency(row.lwop) : '—';

        const allowanceCells = (window.allowanceColumnCodes || []).map(code => {
            const amt = row.allowances?.[code] || 0;
            const display = amt > 0 ? formatCurrency(amt) : '—';
            return `<td class="text-right" style="white-space:nowrap; color:var(--text-light);">${display}</td>`;
        }).join('');

        const dedToggle = row.dedCount > 0
            ? `<div style="display:flex; gap:6px; align-items:center; justify-content:flex-end;">
                    <span class="text-muted" title="${row.deductionSummary}" style="font-size:0.74rem; max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        ${row.deductionSummary}
                    </span>
                    <button class="ded-toggle" data-entry-id="${row.id}" title="View deduction breakdown">
                        ${row.dedCount} lines ▾
                    </button>
               </div>`
            : '<span class="text-muted" style="font-size:0.78rem;">—</span>';

const payslipBtn = row.has_payslip
    ? `<a href="/payroll/${row.payroll_id}/payslips/generate?mode=consolidated&entry_id=${row.id}" class="btn btn-outline btn-sm" target="_blank">Payslip</a>`
    : '<span class="text-muted" style="font-size:0.75rem;">—</span>';

const removeBtn = (window.canRemoveEntry && !row.has_payslip)
    ? `<button type="button" class="btn btn-danger btn-sm" data-remove-entry="${row.id}">Remove</button>`
    : '';

const actionsHtml = removeBtn ? `${payslipBtn} ${removeBtn}` : `${payslipBtn}`;

        // Main row HTML
        const mainRow = document.createElement('tr');
        mainRow.className = netWarnClass;
        mainRow.id = `row-${row.id}`;
        mainRow.style.height = ROW_HEIGHT + 'px';
        mainRow.innerHTML = `
            <td class="text-muted" style="font-size:0.75rem;">${row.index}</td>
            <td>
                <div class="fw-bold" style="font-size:0.86rem; white-space:nowrap;">${row.employee_name}</div>
                <div class="text-muted" style="font-size:0.73rem;">${row.position}</div>
            </td>
            <td style="font-size:0.82rem; white-space:nowrap;">SG ${row.sg}–${row.step}</td>
            <td class="text-right" style="white-space:nowrap;">${formatCurrency(row.basic_salary)}</td>
            ${allowanceCells}
            <td class="text-right fw-bold" style="white-space:nowrap; background:rgba(249,168,37,0.06);">${formatCurrency(row.gross_income)}</td>
            <td class="text-right ${tardyClass}" style="white-space:nowrap;">${tardyDisplay}</td>
            <td class="text-right ${lwopClass}" style="white-space:nowrap;">${lwopDisplay}</td>
            <td class="text-right" style="white-space:nowrap;">${dedToggle}</td>
            <td class="text-right" style="white-space:nowrap; background:rgba(183,28,28,0.04);">${formatCurrency(row.total_deductions)}</td>
            <td class="text-right fw-bold ${netClass}" style="white-space:nowrap; background:rgba(27,94,32,0.04);">
                ${formatCurrency(row.net_amount)}${netWarnBadge}
            </td>
            <td style="font-size:0.78rem; color:var(--text-mid); white-space:nowrap;">${row.remarks || '—'}</td>
            <td>${actionsHtml}</td>
        `;

        return mainRow;
    }

    // renderDeductionRow() removed — DED. Lines now opens a modal
    // (openDeductionModal) instead of an inline expand row.

    function updateVisibleRows() {
        const scrollTop = container.scrollTop;
        const startIndex = Math.max(0, Math.floor(scrollTop / ROW_HEIGHT) - OVERSCAN);
        const endIndex = Math.min(totalRows, Math.ceil((scrollTop + VIEWPORT_HEIGHT) / ROW_HEIGHT) + OVERSCAN);

        // Clear current content
        tbody.innerHTML = '';

        // Top spacer
        const topSpacer = document.createElement('tr');
        topSpacer.className = 'virtual-spacer';
        topSpacer.style.height = (startIndex * ROW_HEIGHT) + 'px';
        tbody.appendChild(topSpacer);

        // Visible rows
        for (let i = startIndex; i < endIndex; i++) {
            const row = rows[i];
            if (!row) continue;

            tbody.appendChild(renderRow(row, i));
        }

        // Bottom spacer
        const bottomSpacer = document.createElement('tr');
        bottomSpacer.className = 'virtual-spacer';
        bottomSpacer.style.height = ((totalRows - endIndex) * ROW_HEIGHT) + 'px';
        tbody.appendChild(bottomSpacer);

        // Re-attach event listeners for deduction toggles
        attachDeductionListeners();
    }

    function attachDeductionListeners() {
        tbody.querySelectorAll('.ded-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const entryId = this.dataset.entryId;
                openDeductionModal(entryId);
            });
        });

        tbody.querySelectorAll('button[data-remove-entry]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const entryId = this.dataset.removeEntry;
                confirmRemoveEntry(entryId);
            });
        });
    }

    async function confirmRemoveEntry(entryId) {
        console.log('confirmRemoveEntry called with entryId:', entryId);
        const row = (window.virtualRowData || []).find(r => String(r.id) === String(entryId));
        console.log('Found row:', row);
        const name = row?.employee_name || 'this employee';

        const result = await Swal.fire({
            title: 'Remove employee from batch?',
            html: `<div style="text-align:left;">
                <div style="font-weight:700;color:#dc3545;margin-bottom:6px;">${name}</div>
                <div style="color:#6b7280;font-size:0.92rem;">
                    This will delete the payroll entry and its deduction lines for this batch.
                </div>
            </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Remove',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6B7280',
            reverseButtons: true,
            focusCancel: true
        });

        if (!result.isConfirmed) return;

        Swal.fire({
            title: '<span style="color:#0F1B4C;">Removing…</span>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });

        const url = `/payroll/${window.payrollId}/entries/${entryId}`;

        if (!window.payrollId || !entryId) {
            console.error('Invalid parameters:', { payrollId: window.payrollId, entryId });
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Invalid parameters for removal.',
                confirmButtonColor: '#0F1B4C'
            });
            return;
        }

        // Ensure csrfToken is a string, not a function
        const csrfToken = typeof window.csrfToken === 'function' ? window.csrfToken() : window.csrfToken;

        try {
            console.log('Removing entry:', entryId, 'URL:', url);
            const headers = {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            };
            console.log('Headers:', headers);

            const resp = await fetch(url, {
                method: 'DELETE',
                headers: headers,
            });

            console.log('Response status:', resp.status, 'OK:', resp.ok);

            if (!resp.ok) {
                const errorText = await resp.text();
                console.error('Response not OK:', errorText);
                throw new Error(`HTTP ${resp.status}: ${errorText}`);
            }

            const data = await resp.json();
            console.log('Response data:', data);

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Removed',
                    text: data.message || 'Employee removed successfully.',
                    confirmButtonColor: '#0F1B4C'
                }).then(() => window.location.reload());
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Remove failed',
                    text: data.message || 'Unable to remove the employee from this batch.',
                    confirmButtonColor: '#0F1B4C'
                });
            }
        } catch (error) {
            console.error('Error removing entry:', error);
            Swal.close(); // Close loading dialog first
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'An error occurred while removing the employee.',
                confirmButtonColor: '#0F1B4C'
            });
        }
    }

    // Note: a duplicate toggleDed() override used to live here, targeting
    // the old ded-row-/ded-panel- inline-expand elements. Removed — DED.
    // Lines now opens openDeductionModal() instead (see attachDeductionListeners).

    // Throttled scroll handler
    let ticking = false;
    container.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                updateVisibleRows();
                ticking = false;
            });
            ticking = true;
        }
    });

    // Initial render
    updateVisibleRows();
})();
</script>
@endsection
