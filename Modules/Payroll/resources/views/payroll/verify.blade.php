{{-- resources/views/payroll/verify.blade.php --}}
{{--
    Expects from PayrollController@verify (Phase 4 - Monthly Model):
      $payroll             — current PayrollBatch (monthly)
      $verifyRows          — collection of stdClass objects with net_1st, net_2nd, total_net
      $totalNetMonthly     — float (total net for the month)
      $belowThresholdCount — int
--}}

@extends('layouts.app')

@section('title', 'Net Pay Verification')
@section('page-title', 'Net Pay Verification')

@section('styles')
<style>
/* ══════════════════════════════════════════════════
   APPROVAL STAGE BAR
══════════════════════════════════════════════════ */
.approval-bar {
    display: flex;
    align-items: stretch;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow);
    margin-bottom: 24px;
}
.approval-step {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    font-size: 0.80rem;
    font-weight: 600;
    color: var(--text-light);
    background: var(--surface);
    border-right: 1px solid var(--border);
    transition: background 0.2s;
}
.approval-step:last-child { border-right: none; }
.approval-step.done   { background: #F1FAF5; color: #1B6B3A; }
.approval-step.active { background: #EEF1FA; color: var(--navy); }
.approval-step.locked { background: var(--navy); color: #ffffff; }
.approval-step-dot {
    width: 30px; height: 30px;
    border-radius: 50%;
    border: 2px solid currentColor;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; font-weight: 700;
    flex-shrink: 0;
    background: #ffffff; color: inherit;
}
.approval-step.done   .approval-step-dot { background: #2E7D52; border-color: #2E7D52; color: #ffffff; }
.approval-step.active .approval-step-dot { background: var(--navy); border-color: var(--navy); color: #ffffff; }
.approval-step.locked .approval-step-dot { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.6); color: #ffffff; }
.approval-step-label { line-height: 1.3; min-width: 0; }
.approval-step-label small {
    display: block; font-weight: 400; font-size: 0.70rem;
    opacity: 0.72; margin-top: 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* ══════════════════════════════════════════════════
   VERIFY TABLE
══════════════════════════════════════════════════ */
.verify-table th,
.verify-table td {
    vertical-align: middle;
    white-space: nowrap;
}
.verify-table td.text-right,
.verify-table th.text-right {
    text-align: right;
}
.row-flagged {
    background: #FFF0F0 !important;
}
.net-below {
    color: #B71C1C;
    font-weight: 700;
}
.badge-deducted {
    background: #E8F5E9;
    color: #2E7D52;
    border: 1px solid #A5D6A7;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 0.72rem;
    font-weight: 700;
}
.badge-not-deducted {
    background: #F5F5F5;
    color: #757575;
    border: 1px solid #E0E0E0;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 0.72rem;
    font-weight: 600;
}
.badge-below {
    background: #FFEBEE;
    color: #B71C1C;
    border: 1px solid #FFCDD2;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 0.72rem;
    font-weight: 700;
}
.badge-ok {
    background: #E8F5E9;
    color: #2E7D52;
    border: 1px solid #A5D6A7;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 0.72rem;
    font-weight: 700;
}
.verify-tfoot td {
    font-weight: 700;
    font-size: 0.88rem;
    padding: 12px 14px;
    background: var(--navy);
    color: white;
}
.verify-tfoot td.gold  { color: var(--gold); }
.verify-tfoot td.green { color: #69F0AE; }
.admin-override-card {
    border: 2px solid #B71C1C;
    border-radius: var(--radius);
    padding: 20px;
    margin-top: 24px;
    background: #FFF8F8;
}
.admin-override-card h4 {
    color: #B71C1C;
    margin-bottom: 12px;
    font-size: 0.95rem;
}

/* ══════════════════════════════════════════════════
   MOBILE
══════════════════════════════════════════════════ */
@media (max-width: 768px) {
    .approval-bar {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .approval-step {
        min-width: 130px;
        flex: 0 0 auto;
    }
    .stat-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important;
    }
    .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
}
</style>
@endsection

@section('content')

@php
    $months = [
        '', 'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];
    $periodLabel = ($months[$payroll->period_month] ?? '?')
        . ' ' . $payroll->period_year;

    $statusClass = match ($payroll->status) {
        'draft'                             => 'badge-draft',
        'computed'                          => 'badge-computed',
        'pending_accountant', 'pending_rd'  => 'badge-pending',
        'released'                          => 'badge-released',
        'locked'                            => 'badge-locked',
        default                             => 'badge-draft',
    };
    $statusLabels = [
        'draft'              => 'Draft',
        'computed'           => 'Computed',
        'pending_accountant' => 'Pending Accountant',
        'pending_rd'         => 'Pending RD / ARD',
        'released'           => 'Released',
        'locked'             => 'Locked',
    ];
    $statusLabel = $statusLabels[$payroll->status] ?? ucfirst(str_replace('_', ' ', $payroll->status));

    // Label columns by cut-off (computed on the fly from monthly data)
    $col1stLabel  = '1–15 Net Pay';
    $col2ndLabel  = '16–30/31 Net Pay';

    // Calculate cutoff totals from verifyRows
    $totalNet1st = $verifyRows->sum('net_1st');
    $totalNet2nd = $verifyRows->sum('net_2nd');
    $totalCombined = $totalNet1st + $totalNet2nd;
@endphp

{{-- ═══════════════════════════════════════════════════════════════
     PAGE HEADER
═══════════════════════════════════════════════════════════════ --}}
<div class="page-header">
    <div class="page-header-left">
        <h1>Net Pay Verification</h1>
        <p>
            {{ $periodLabel }} ·
            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('payroll.show', $payroll) }}" class="btn btn-outline btn-sm">← Back to Batch</a>
    </div>
</div>

{{-- Alerts --}}
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif
@if (session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     APPROVAL STAGE BAR
═══════════════════════════════════════════════════════════════ --}}
@include('payroll::payroll._approval_bar')

{{-- ═══════════════════════════════════════════════════════════════
     STAT CARDS
═══════════════════════════════════════════════════════════════ --}}
<div class="stat-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-label">Employees</div>
        <div class="stat-value">{{ $verifyRows->count() }}</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-label">Total 1st Cut-off Net</div>
        <div class="stat-value">₱{{ number_format($totalNet1st, 2) }}</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-label">Total 2nd Cut-off Net</div>
        <div class="stat-value">₱{{ number_format($totalNet2nd, 2) }}</div>
    </div>
    <div class="stat-card {{ $belowThresholdCount > 0 ? 'red' : '' }}">
        <div class="stat-label">Below ₱5,000 Threshold</div>
        <div class="stat-value">{{ $belowThresholdCount }}</div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     BELOW-THRESHOLD ALERT
═══════════════════════════════════════════════════════════════ --}}
@if ($belowThresholdCount > 0)
    <div class="alert alert-warning" style="margin-bottom:20px;">
        ⚠ <strong>{{ $belowThresholdCount }} employee{{ $belowThresholdCount === 1 ? '' : 's' }}</strong>
        {{ $belowThresholdCount === 1 ? 'has a' : 'have' }} net pay below ₱5,000 in at least one cut-off.
        LBP Loan deductions may need to be deferred per bank policy.
        Rows are highlighted in the table below.
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     NET PAY TABLE
═══════════════════════════════════════════════════════════════ --}}
<div class="card">
    <div class="card-header">
        <h3>Net Pay — {{ $periodLabel }}</h3>
        <span class="text-muted" style="font-size:0.80rem;">
            Matches the "New Net Pay" sheet format
        </span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table class="verify-table">
                <thead>
                    <tr>
                        <th style="width:36px;">#</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Plantilla Item No.</th>
                        <th class="text-right">{{ $col1stLabel }}</th>
                        <th class="text-right">{{ $col2ndLabel }}</th>
                        <th class="text-right">Total Net</th>
                        <th style="text-align:center;">LBP Loan</th>
                        <th style="text-align:center;">Below ₱5K</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($verifyRows as $i => $row)
                        @php
                            $flagged = $row->below_threshold;
                            $net1st = $row->net_1st;
                            $net2nd = $row->net_2nd;

                            $net1stBelow = $net1st !== null && $net1st < 5000;
                            $net2ndBelow = $net2nd !== null && $net2nd < 5000;
                        @endphp
                        <tr class="{{ $flagged ? 'row-flagged' : '' }}">
                            <td style="color:var(--text-light);">{{ $i + 1 }}</td>
                            <td class="fw-bold">
                                {{ optional($row->employee)->last_name }},
                                {{ optional($row->employee)->first_name }}
                                @if (optional($row->employee)->middle_name)
                                    {{ substr($row->employee->middle_name, 0, 1) }}.
                                @endif
                            </td>
                            <td class="text-muted" style="font-size:0.82rem;">
                                {{ optional($row->employee)->position_title ?? '—' }}
                            </td>
                            <td class="text-muted" style="font-size:0.82rem;">
                                {{ optional($row->employee)->plantilla_item_no ?? '—' }}
                            </td>

                            {{-- Net Pay 1–15 --}}
                            <td class="text-right {{ $net1stBelow ? 'net-below' : '' }}">
                                @if ($net1st !== null)
                                    ₱{{ number_format($net1st, 2) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Net Pay 16–30/31 --}}
                            <td class="text-right {{ $net2ndBelow ? 'net-below' : '' }}">
                                @if ($net2nd !== null)
                                    ₱{{ number_format($net2nd, 2) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Total Net --}}
                            <td class="text-right fw-bold">
                                ₱{{ number_format($row->total_net, 2) }}
                            </td>

                            {{-- LBP Loan --}}
                            <td style="text-align:center;">
                                @if ($row->has_lbp_loan)
                                    <span class="badge-deducted">Deducted</span>
                                @else
                                    <span class="badge-not-deducted">Not Deducted</span>
                                @endif
                            </td>

                            {{-- Below ₱5K --}}
                            <td style="text-align:center;">
                                @if ($row->below_threshold)
                                    <span class="badge-below">⚠ Below Threshold</span>
                                @else
                                    <span class="badge-ok">✓ OK</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot class="verify-tfoot">
                    <tr>
                        <td colspan="4" style="color:rgba(255,255,255,0.6); font-size:0.82rem;">
                            TOTALS — {{ $verifyRows->count() }} employee{{ $verifyRows->count() !== 1 ? 's' : '' }}
                        </td>
                        <td class="text-right gold">₱{{ number_format($totalNet1st, 2) }}</td>
                        <td class="text-right gold">₱{{ number_format($totalNet2nd, 2) }}</td>
                        <td class="text-right green">₱{{ number_format($totalCombined, 2) }}</td>
                        <td></td>
                        <td style="text-align:center; color:{{ $belowThresholdCount > 0 ? '#FF8A80' : '#69F0AE' }};">
                            {{ $belowThresholdCount }} flagged
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     ACTION SECTION
═══════════════════════════════════════════════════════════════ --}}
<div class="d-flex gap-2 flex-wrap" style="margin-top:24px; align-items:flex-start;">

    {{-- CASHIER: Finalize & Lock --}}
    @role('cashier')
        @if ($payroll->status === 'released')
            <form method="POST" action="{{ route('payroll.lock', $payroll) }}"
                  onsubmit="return confirm('Finalize and lock this payroll batch?\n\nThis marks disbursement as complete. The batch will be locked and cannot be further edited without an override.')">
                @csrf
                <button type="submit" class="btn btn-danger">
                    🔒 Finalize &amp; Lock
                </button>
            </form>
        @endif
    @endrole

</div>

{{-- PAYROLL OFFICER: Admin Override (shown when locked) --}}
@role('payroll_officer')
    @if ($payroll->status === 'locked')
        <div class="admin-override-card">
            <h4>⚠ Admin Override — Unlock Batch</h4>
            <p style="font-size:0.85rem; color:var(--text-mid); margin-bottom:14px;">
                This batch is currently <strong>Locked</strong>. As a Payroll Officer, you may force it
                back to <strong>Released</strong> status for corrections. This action is fully logged.
            </p>
            <form method="POST" action="{{ route('payroll.forceEdit', $payroll) }}"
                  onsubmit="return confirm('Override the lock and revert this batch to Released?\n\nThis action will be recorded in the audit log.')">
                @csrf
                <div class="form-group" style="margin-bottom:12px;">
                    <label for="remarks" style="font-weight:600; font-size:0.85rem;">
                        Reason for Override <span style="color:#B71C1C;">*</span>
                    </label>
                    <textarea id="remarks" name="remarks" rows="3" required minlength="10"
                              placeholder="Provide a detailed reason for unlocking this batch (minimum 10 characters)…"
                              class="@error('remarks') is-invalid @enderror"
                              style="width:100%; margin-top:6px; border:1px solid var(--border);
                                     border-radius:var(--radius); padding:10px; font-size:0.85rem;
                                     font-family:inherit; resize:vertical;">{{ old('remarks') }}</textarea>
                    @error('remarks')
                        <div style="color:#B71C1C; font-size:0.80rem; margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-danger btn-sm">
                    🔓 Override Lock — Revert to Released
                </button>
            </form>
        </div>
    @endif
@endrole

@endsection

@section('scripts')
{{-- No JS needed for this view --}}
@endsection
