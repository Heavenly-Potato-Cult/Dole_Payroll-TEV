{{-- resources/views/tev/show.blade.php --}}
@extends('layouts.tev')

@section('title', 'TEV — ' . $tev->tev_no)
@section('page-title', 'Travel (TEV)')

@section('styles')
<style>
/* ── Approval timeline ── */
.tev-timeline {
    display: flex; margin-bottom: 24px; overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 8px; border: 1px solid var(--border); max-width: 100%;
}
.tev-step {
    flex: 1; min-width: 80px; padding: 10px 12px;
    font-size: 0.74rem; font-weight: 600; white-space: nowrap;
    display: flex; align-items: center; gap: 6px;
    background: var(--surface); color: var(--text-light);
    border-right: 1px solid var(--border);
}
.tev-step:last-child { border-right: none; }
.tev-step.done         { background: #F1FAF5; color: #1B6B3A; }
.tev-step.active       { background: #EEF1FA; color: var(--navy); }
.tev-step.terminal     { background: var(--navy); color: #fff; }
.tev-step.rejected-step{ background: #FFF0F0; color: #B71C1C; }
.tev-step-dot {
    width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
    border: 2px solid currentColor;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.70rem; font-weight: 700; background: #fff; color: inherit;
}
.tev-step.done .tev-step-dot     { background: #2E7D52; border-color: #2E7D52; color: #fff; }
.tev-step.active .tev-step-dot   { background: var(--navy); border-color: var(--navy); color: #fff; }
.tev-step.terminal .tev-step-dot { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.5); }

/* ── Detail grid ── */
.detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px 16px; }
.detail-item { display: flex; flex-direction: column; gap: 2px; }
.detail-item .label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-light); }
.detail-item .value { font-weight: 600; color: var(--text); font-size: 0.86rem; }

/* ── Itinerary table ── */
.itin-tbl-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.itin-tbl { width: 100%; border-collapse: collapse; font-size: 0.78rem; white-space: nowrap; min-width: 650px; }
.itin-tbl thead th {
    background: var(--navy); color: #fff; padding: 7px 10px; text-align: center;
    font-size: 0.70rem; font-weight: 600; letter-spacing: 0.03em;
    border: 1px solid rgba(255,255,255,0.15);
}
.itin-tbl tbody td { padding: 8px 10px; border: 1px solid var(--border); vertical-align: middle; }
.itin-tbl tfoot td { padding: 8px 10px; font-weight: 700; background: #f0f2ff; border: 1px solid var(--border); }
.itin-tbl td.text-right  { text-align: right; }
.itin-tbl td.text-center { text-align: center; }

/* ── Approval log ── */
.log-timeline { display: flex; flex-direction: column; }
.log-item { display: flex; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--border); }
.log-item:last-child { border-bottom: none; }
.log-dot { width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; }
.log-dot.approved { background: #E8F5E9; }
.log-dot.rejected { background: #FFEBEE; }
.log-dot.returned { background: #FFF3E0; }
.log-meta { font-size: 0.75rem; color: var(--text-light); margin-top: 2px; }

/* ── Balance widget ── */
.balance-widget { border-radius: 8px; padding: 14px 16px; background: #FAFAFA; border: 1px solid var(--border); }
.bw-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid rgba(0,0,0,0.06); font-size: 0.82rem; flex-wrap: wrap; gap: 4px; }
.bw-row:last-of-type { border-bottom: none; }
.bw-total { display: flex; justify-content: space-between; align-items: center; padding: 10px 0 0; font-size: 0.95rem; font-weight: 700; border-top: 2px solid var(--border); margin-top: 6px; flex-wrap: wrap; gap: 4px; }
.bw-refund  { color: #B71C1C; }
.bw-claim   { color: #1B5E20; }
.bw-settled { color: var(--navy); }

/* ── Layout ── */
.show-grid { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; max-width: 100%; }
.show-left { display: flex; flex-direction: column; gap: 20px; min-width: 0; }
.show-right { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 20px; min-width: 0; }

/* ── Cert grid ── */
.cert-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px; }

/* ── Action banner ── */
.action-banner {
    display: flex; align-items: flex-start; gap: 14px;
    border-radius: 10px; padding: 14px 18px; margin-bottom: 20px;
    border: 1px solid transparent;
}
.action-banner .ab-icon { font-size: 1.5rem; line-height: 1; flex-shrink: 0; margin-top: 1px; }
.action-banner .ab-body { flex: 1; min-width: 0; }
.action-banner .ab-title { font-size: 0.85rem; font-weight: 700; margin-bottom: 3px; line-height: 1.3; }
.action-banner .ab-desc  { font-size: 0.78rem; line-height: 1.55; opacity: 0.85; margin: 0; }
.action-banner .ab-meta  { font-size: 0.72rem; margin-top: 8px; opacity: 0.65; line-height: 1.4; }
.ab-action  { background: #EEF3FF; border-color: #4F73D9; color: #1A3A8F; }
.ab-waiting { background: #F5F5F5; border-color: #BDBDBD; color: #424242; }
.ab-success { background: #F1FAF5; border-color: #43A047; color: #1B5E20; }
.ab-warning { background: #FFF8E1; border-color: #F9A825; color: #7B5800; }
.ab-danger  { background: #FFF0F0; border-color: #EF5350; color: #B71C1C; }
.ab-purple  { background: #F3E5F5; border-color: #8E24AA; color: #4A148C; }
@media print { .action-banner { display: none !important; } }

/* ════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════ */
@media (max-width: 900px) {
    .show-grid  { grid-template-columns: 1fr; }
    .show-right { position: static; order: -1; }
}

@media (max-width: 768px) {
    .cert-grid  { grid-template-columns: 1fr; }
    .detail-grid{ grid-template-columns: 1fr 1fr; }
    .tev-step   { min-width: 70px; padding: 8px 10px; font-size: 0.70rem; }
}

@media (max-width: 600px) {
    body { overflow-x: hidden; }
    .show-grid, .show-left, .show-right,
    .card, .card-body, .card-header,
    .balance-widget, .bw-row, .bw-total,
    .detail-grid, .itin-tbl-wrap { max-width: 100%; overflow-x: hidden; }

    .card-body   { padding: 12px; }
    .card-header { padding: 12px 14px; }

    .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .page-header .d-flex { flex-wrap: wrap; gap: 6px; }

    .tev-step     { min-width: 64px; padding: 8px; font-size: 0.66rem; gap: 4px; }
    .tev-step-dot { width: 18px; height: 18px; font-size: 0.62rem; }

    .detail-grid { grid-template-columns: 1fr 1fr; gap: 8px 12px; }
    .detail-item .value { font-size: 0.83rem; }

    .cert-grid { grid-template-columns: 1fr; }

    .itin-tbl-wrap { overflow-x: auto; }

    .show-right { order: -1; position: static !important; }
    .show-right .btn { width: 100%; }
    .show-right .card-body form button { width: 100%; }

    .bw-row, .bw-total { font-size: 0.78rem; }
}

@media (max-width: 480px) {
    .detail-grid { grid-template-columns: 1fr; }
    .page-header h1 { font-size: 1.1rem; }
}

@media print {
    .no-print { display: none !important; }
    .tev-timeline { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
    body { font-size: 9pt; }
    @page { margin: 1.2cm 1cm; }
}
</style>
@endsection

@section('content')

@php
    $emp = $tev->employee;

    $trackLabel = $tev->track === 'cash_advance' ? 'Cash Advance' : 'Reimbursement';
    $trackStyle = $tev->track === 'cash_advance'
        ? 'background:#E8F5E9; color:#1B5E20; border:1px solid #43A047;'
        : 'background:#E8EAF6; color:#1A237E; border:1px solid #3949AB;';

    $typeStyle = match ($tev->travel_type) {
        'regional' => 'background:#FFF8E1; color:#F57F17; border:1px solid #F9A825;',
        'national' => 'background:#E8EAF6; color:#1A237E; border:1px solid #3949AB;',
        default    => 'background:#E8F5E9; color:#1B5E20; border:1px solid #43A047;',
    };

    $statusClass = match ($tev->status) {
        'submitted'            => 'badge-pending',
        'accountant_certified' => 'badge-computed',
        'rd_approved'          => 'badge-released',
        'cashier_released'     => 'badge-locked',
        'reimbursed'           => 'badge-locked',
        'liquidation_filed'    => 'badge-pending',
        'liquidated'           => 'badge-active',
        'rejected'             => 'badge-inactive',
        default                => 'badge-draft',
    };
    $statusLabel = ucwords(str_replace('_', ' ', $tev->status));

    // ── Workflow steps — no 'draft' since TEVs are auto-submitted on creation ──
    if ($tev->track === 'cash_advance') {
        $steps     = ['Submitted', 'Acct. Certified', 'RD Approved', 'Released', 'Liq. Filed', 'Liquidated'];
        $stepOrder = ['submitted', 'accountant_certified', 'rd_approved', 'cashier_released', 'liquidation_filed', 'liquidated'];
    } else {
        $steps     = ['Submitted', 'Acct. Certified', 'RD Approved', 'Reimbursed'];
        $stepOrder = ['submitted', 'accountant_certified', 'rd_approved', 'reimbursed'];
    }

    $currentIdx = array_search($tev->status, $stepOrder);
    if ($currentIdx === false) $currentIdx = -1;

    // ── Permissions ──
    // No canSubmit: TEVs are auto-submitted on creation, manual submit removed.
    $canReject = (
        ($tev->status === 'submitted'            && auth()->user()->hasAnyRole(['accountant'])) ||
        ($tev->status === 'accountant_certified' && auth()->user()->hasAnyRole(['ard', 'chief_admin_officer'])) ||
        ($tev->status === 'rd_approved'          && auth()->user()->hasAnyRole(['cashier']))
    );
    $canCertify = in_array($tev->status, ['rd_approved','cashier_released','reimbursed','liquidation_filed','liquidated'])
               && auth()->user()->hasAnyRole(['hrmo','accountant']);

    $canFileLiquidation    = $tev->track === 'cash_advance'
                          && $tev->status === 'cashier_released'
                          && (
                                ($tev->employee && $tev->employee->user_id === auth()->id())
                                || auth()->user()->hasAnyRole(['hrmo'])
                             );
    $canApproveLiquidation = $tev->status === 'liquidation_filed'
                          && auth()->user()->hasAnyRole(['cashier']);

    $showBalanceWidget = $tev->track === 'cash_advance'
                      && in_array($tev->status, ['cashier_released','liquidation_filed','liquidated']);
    $advanceAmount = (float) ($tev->cash_advance_amount ?? $tev->grand_total);
    $balanceDue    = (float) ($tev->balance_due ?? 0);
    $actualAmount  = in_array($tev->status, ['liquidation_filed','liquidated'])
                   ? $advanceAmount - $balanceDue : 0;

    $showDocs  = in_array($tev->status, ['rd_approved','cashier_released','reimbursed','liquidation_filed','liquidated']);
    $showDvPdf = in_array($tev->status, ['liquidation_filed','liquidated']);
@endphp

<div class="page-header no-print">
    <div class="page-header-left">
        <h1>{{ $tev->tev_no }}</h1>
        <p>
            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
            &nbsp;
            <span style="font-size:0.73rem; font-weight:700; padding:3px 10px; border-radius:12px; {{ $trackStyle }}">{{ $trackLabel }}</span>
            &nbsp;
            <span style="font-size:0.73rem; font-weight:700; padding:3px 10px; border-radius:12px; {{ $typeStyle }}">{{ ucfirst($tev->travel_type) }}</span>
        </p>
    </div>
    <div class="d-flex gap-2 no-print">
        <button onclick="window.print()" class="btn btn-outline btn-sm">🖨 Print</button>
        <a href="{{ route('tev.requests.index') }}" class="btn btn-outline btn-sm">← Back</a>
    </div>
</div>

{{-- Approval timeline ── --}}
<div class="tev-timeline no-print">
    @foreach ($steps as $i => $label)
        @php
            $isDone     = $i <= $currentIdx && $tev->status !== 'rejected';
            $isActive   = $i === $currentIdx;
            $isTerminal = $isActive && $i === count($steps) - 1;
            $isRejected = $tev->status === 'rejected' && $isActive;
            if ($isRejected)     $stepClass = 'rejected-step';
            elseif ($isTerminal) $stepClass = 'terminal';
            elseif ($isDone)     $stepClass = 'done';
            elseif ($isActive)   $stepClass = 'active';
            else                 $stepClass = '';
        @endphp
        <div class="tev-step {{ $stepClass }}">
            <div class="tev-step-dot">{{ $isDone ? '✓' : ($i + 1) }}</div>
            {{ $label }}
        </div>
    @endforeach
</div>

{{-- ══════════════════════════════════════════════════════════
     Action Banner — role-aware
══════════════════════════════════════════════════════════ --}}
@php
    $u        = auth()->user();
    $ab       = null;
    $lastLog  = $tev->approvalLogs->last();
    $lastWho  = optional($lastLog?->user)->name ?? 'System';
    $lastWhen = $lastLog?->performed_at?->format('M d, Y h:i A');
    $lastMeta = $lastLog ? "Last action by {$lastWho} · {$lastWhen}" : null;

    // ── HRMO ──────────────────────────────────────────────────────────────
    if ($u->hasAnyRole(['hrmo'])) {
        $ab = match ($tev->status) {
            // No 'draft' case — TEVs go straight to submitted on creation
            'submitted'            => ['ab-waiting', '📬', 'Submitted — awaiting Accountant certification.',
                                       'No further action needed from HR. The accountant will review and certify this TEV for ' . optional($emp)->last_name . '.', $lastMeta],
            'accountant_certified' => ['ab-waiting', '🔍', 'Accountant certified — awaiting RD/ARD approval.',
                                       'The TEV is moving through the approval chain. No HR action needed at this stage.', $lastMeta],
            'rd_approved'          => ['ab-waiting', '✅', 'RD approved — awaiting cashier release.',
                                       $tev->track === 'cash_advance'
                                           ? 'The cashier will release the cash advance. No HR action needed.'
                                           : 'The cashier will process the reimbursement. No HR action needed.',
                                       $lastMeta],
            'cashier_released'     => ['ab-warning', '💵', 'Cash advance released — liquidation required.',
                                       'The cash advance of ₱' . number_format($tev->cash_advance_amount ?? $tev->grand_total, 2) . ' has been released to ' . optional($emp)->last_name . '. File the liquidation once travel is complete.',
                                       $lastMeta],
            'reimbursed'           => ['ab-success', '🎉', 'Reimbursement processed — TEV closed.',
                                       'This TEV has been fully reimbursed and closed. No further action needed.', $lastMeta],
            'liquidation_filed'    => ['ab-waiting', '🗂',  'Liquidation filed — awaiting cashier approval.',
                                       'The liquidation has been filed. The cashier will review and finalise.', $lastMeta],
            'liquidated'           => ['ab-success', '🎉', 'Fully liquidated — process complete.',
                                       'This TEV has been reconciled and closed.', $lastMeta],
            'rejected'             => ['ab-danger',  '🚫', 'TEV rejected.',
                                       $lastLog?->remarks ? 'Reason: ' . $lastLog->remarks : 'See the Approval Timeline below for details.',
                                       $lastMeta],
            default                => null,
        };
    }
    // ── Accountant ────────────────────────────────────────────────────────
    elseif ($u->hasAnyRole(['accountant'])) {
        $ab = match ($tev->status) {
            'submitted'            => ['ab-action',  '📋', 'Action required — certify or reject this TEV.',
                                       'Review the itinerary and totals below, then use the panel on the right.', null],
            'accountant_certified' => ['ab-success', '✅', 'You have certified this TEV.',
                                       'Waiting for RD/ARD approval. Nothing further needed from you.', $lastMeta],
            default                => ['ab-waiting', 'ℹ️', 'No action required at this step.',
                                       'This TEV is at a stage outside your review scope.', null],
        };
    }
    // ── ARD / Chief Admin Officer ──────────────────────────────────────────
    elseif ($u->hasAnyRole(['ard', 'chief_admin_officer'])) {
        $ab = match ($tev->status) {
            'accountant_certified' => ['ab-action',  '🏆', 'Action required — approve or reject this TEV.',
                                       'The Accountant has certified this request. Use the panel on the right to proceed.', $lastMeta],
            'rd_approved'          => ['ab-success', '✅', 'You have approved this TEV.',
                                       'Waiting for the cashier to release the ' . ($tev->track === 'cash_advance' ? 'cash advance.' : 'reimbursement.'),
                                       $lastMeta],
            default                => ['ab-waiting', 'ℹ️', 'No action required at this step.',
                                       'This TEV is outside your current review scope.', null],
        };
    }
    // ── Cashier ────────────────────────────────────────────────────────────
    elseif ($u->hasAnyRole(['cashier'])) {
        $ab = match ($tev->status) {
            'rd_approved'       => ['ab-action',  '💵', 'Action required — release this TEV.',
                                    $tev->track === 'cash_advance'
                                        ? 'RD has approved. Release the cash advance of ₱' . number_format($tev->grand_total, 2) . ' to the employee.'
                                        : 'RD has approved. Process the reimbursement of ₱' . number_format($tev->grand_total, 2) . '.',
                                    $lastMeta],
            'cashier_released'  => ['ab-waiting', '⏳', 'Cash advance released — awaiting employee liquidation.',
                                    'Nothing to do yet. HRMO will file the liquidation on behalf of the employee once travel is complete.', $lastMeta],
            'liquidation_filed' => ['ab-purple',  '🗂',  'Action required — approve this liquidation.',
                                    'Review the reconciliation widget on the right and approve or reject.', $lastMeta],
            'liquidated'        => ['ab-success', '✅', 'Liquidation approved. TEV fully closed.',
                                    'No further action needed.', $lastMeta],
            default             => ['ab-waiting', 'ℹ️', 'No action required at this step.',
                                    'This TEV is outside your current release scope.', null],
        };
    }
    // ── Other roles ────────────────────────────────────────────────────────
    else {
        $ab = ['ab-waiting', 'ℹ️',
               ucwords(str_replace('_', ' ', $tev->status)) . ' — in progress.',
               'This TEV is moving through the approval workflow.', $lastMeta];
    }
@endphp
@if ($ab)
@php [$abVariant, $abIcon, $abTitle, $abDesc, $abMeta] = $ab; @endphp
<div class="action-banner {{ $abVariant }} no-print">
    <span class="ab-icon">{{ $abIcon }}</span>
    <div class="ab-body">
        <div class="ab-title">{{ $abTitle }}</div>
        <p class="ab-desc">{{ $abDesc }}</p>
        @if ($abMeta)<div class="ab-meta">{{ $abMeta }}</div>@endif
    </div>
</div>
@endif

<div class="show-grid">

    {{-- ── LEFT ── --}}
    <div class="show-left">

        {{-- TEV Info ── --}}
        <div class="card">
            <div class="card-header">
                <h3>✈ TEV Information</h3>
                <span style="font-size:0.78rem; color:var(--text-light);">Filed: {{ $tev->created_at->format('M d, Y') }}</span>
            </div>
            <div class="card-body">
                <div class="detail-grid" style="margin-bottom:16px;">
                    <div class="detail-item">
                        <span class="label">TEV No.</span>
                        <span class="value" style="color:var(--navy);">{{ $tev->tev_no }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Track</span>
                        <span class="value">
                            <span style="font-size:0.73rem; font-weight:700; padding:3px 8px; border-radius:12px; {{ $trackStyle }}">{{ $trackLabel }}</span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Travel Type</span>
                        <span class="value">
                            <span style="font-size:0.73rem; font-weight:700; padding:3px 8px; border-radius:12px; {{ $typeStyle }}">{{ ucfirst($tev->travel_type) }}</span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Office Order</span>
<span class="value">
    @if ($tev->office_order_id && $tev->officeOrder)
        <a href="{{ route('tev.office-orders.show', $tev->office_order_id) }}" style="color:var(--navy);">
            {{ $tev->officeOrder->office_order_no }}
        </a>
    @else
        —
    @endif
</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Employee</span>
                        <span class="value">
                            {{ optional($emp)->last_name }}, {{ optional($emp)->first_name }}
                            @if (optional($emp)->middle_name) {{ substr($emp->middle_name, 0, 1) }}. @endif
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Position</span>
                        <span class="value">{{ optional($emp)->position_title ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Travel Start</span>
                        <span class="value">{{ $tev->travel_date_start->format('F j, Y') }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Travel End</span>
                        <span class="value">{{ $tev->travel_date_end->format('F j, Y') }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Total Days</span>
                        <span class="value">{{ $tev->total_days }} day(s)</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Status</span>
                        <span class="value"><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></span>
                    </div>
                </div>
                <div class="detail-item" style="margin-bottom:12px;">
                    <span class="label">Destination</span>
                    <span class="value" style="font-weight:400;">{{ $tev->destination }}</span>
                </div>
                <div class="detail-item" style="margin-bottom:12px;">
                    <span class="label">Purpose</span>
                    <span class="value" style="font-weight:400; line-height:1.5;">{{ $tev->purpose }}</span>
                </div>
                @if ($tev->remarks)
                <div style="padding:10px 14px; background:#f8f9ff; border-left:3px solid var(--navy); border-radius:4px; font-size:0.82rem;">
                    <strong>Remarks:</strong> {{ $tev->remarks }}
                </div>
                @endif
            </div>
        </div>

        {{-- Itinerary ── --}}
        <div class="card">
            <div class="card-header"><h3>🗓 Itinerary of Travel</h3></div>
            <div class="card-body" style="padding:0;">
                <div class="itin-tbl-wrap">
                    <table class="itin-tbl">
                        <thead>
                            <tr>
                                <th>Date</th><th>From</th><th>To</th>
                                <th>Departure</th><th>Arrival</th><th>Mode</th>
                                <th>Transport (₱)</th><th>Half Day</th>
                                <th>Per Diem (₱)</th><th>Total (₱)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tev->itineraryLines as $line)
                                @php $lineTotal = (float)$line->transportation_cost + (float)$line->per_diem_amount; @endphp
                                <tr>
                                    <td>{{ $line->travel_date->format('M d, Y') }}</td>
                                    <td>{{ $line->origin }}</td>
                                    <td>{{ $line->destination }}</td>
                                    <td class="text-center">
                                        {{ $line->departure_time ? \Carbon\Carbon::parse($line->departure_time)->format('h:iA') : '—' }}
                                    </td>
                                    <td class="text-center">
                                        {{ $line->arrival_time ? \Carbon\Carbon::parse($line->arrival_time)->format('h:iA') : '—' }}
                                    </td>
                                    <td class="text-center">{{ ucfirst($line->mode_of_transport ?? '—') }}</td>
                                    <td class="text-right">{{ number_format($line->transportation_cost, 2) }}</td>
                                    <td class="text-center">{{ $line->is_half_day ? 'Yes' : '—' }}</td>
                                    <td class="text-right">{{ number_format($line->per_diem_amount, 2) }}</td>
                                    <td class="text-right fw-bold">{{ number_format($lineTotal, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" style="text-align:center; padding:24px; color:var(--text-light);">No itinerary lines added yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" style="text-align:right;">TOTALS</td>
                                <td class="text-right" style="color:var(--navy);">₱{{ number_format($tev->total_transportation, 2) }}</td>
                                <td></td>
                                <td class="text-right" style="color:var(--navy);">₱{{ number_format($tev->total_per_diem, 2) }}</td>
                                <td class="text-right" style="color:var(--navy); font-size:0.9rem;">₱{{ number_format($tev->grand_total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Approval log ── --}}
        @if ($tev->approvalLogs->count() > 0)
        <div class="card">
            <div class="card-header"><h3>📋 Approval Timeline</h3></div>
            <div class="card-body">
                <div class="log-timeline">
                    @foreach ($tev->approvalLogs as $log)
                        @php
                            $logIcon = match($log->action) { 'rejected' => '✕', 'returned' => '↩', default => '✓' };
                        @endphp
                        <div class="log-item">
                            <div class="log-dot {{ $log->action }}">{{ $logIcon }}</div>
                            <div>
                                <div style="font-weight:600; font-size:0.85rem;">
                                    {{ ucwords(str_replace('_', ' ', $log->step)) }}
                                    <span style="font-size:0.75rem; color:var(--text-light); font-weight:400;">— {{ strtoupper($log->action) }}</span>
                                </div>
                                <div class="log-meta">
                                    {{ optional($log->user)->name ?? 'System' }} &nbsp;·&nbsp;
                                    {{ $log->performed_at ? $log->performed_at->format('M d, Y h:i A') : '—' }}
                                </div>
                                @if ($log->remarks)
                                <div style="font-size:0.78rem; color:var(--text-mid); margin-top:4px; padding:4px 8px; background:#f8f9ff; border-radius:4px;">
                                    {{ $log->remarks }}
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Certification ── --}}
        @if ($tev->certification)
        <div class="card">
            <div class="card-header"><h3>📜 Certification of Travel Completed</h3></div>
            <div class="card-body">
                @php $cert = $tev->certification; @endphp
                <div class="detail-grid" style="margin-bottom:12px;">
                    <div class="detail-item"><span class="label">Travel Completed</span><span class="value">{{ $cert->travel_completed ? 'Yes' : 'No' }}</span></div>
                    <div class="detail-item"><span class="label">Date Returned</span><span class="value">{{ $cert->date_returned ? $cert->date_returned->format('M d, Y') : '—' }}</span></div>
                    <div class="detail-item"><span class="label">Place Reported Back</span><span class="value">{{ $cert->place_reported_back ?? '—' }}</span></div>
                    <div class="detail-item"><span class="label">Agency Visited</span><span class="value">{{ $cert->agency_visited ?? '—' }}</span></div>
                    <div class="detail-item"><span class="label">Appearance Date</span><span class="value">{{ $cert->appearance_date ? $cert->appearance_date->format('M d, Y') : '—' }}</span></div>
                    <div class="detail-item"><span class="label">Annex A Amount</span><span class="value">₱{{ number_format($cert->annex_a_amount ?? 0, 2) }}</span></div>
                    <div class="detail-item"><span class="label">Certified By</span><span class="value">{{ optional($cert->certifier)->name ?? '—' }}</span></div>
                    <div class="detail-item"><span class="label">Certified On</span><span class="value">{{ $cert->certified_at ? $cert->certified_at->format('M d, Y') : '—' }}</span></div>
                </div>
                @if ($cert->annex_a_particulars)
                <div style="font-size:0.82rem; padding:8px 12px; background:#f8f9ff; border-radius:4px; border-left:3px solid var(--navy);">
                    <strong>Annex A Particulars:</strong> {{ $cert->annex_a_particulars }}
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>{{-- end left --}}

    {{-- ── RIGHT PANEL ── --}}
    <div class="show-right">

        {{-- Summary ── --}}
        <div style="background:var(--navy); color:#fff; border-radius:8px; padding:16px 20px;">
            <div style="font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; opacity:0.6; margin-bottom:10px;">Summary</div>
            @foreach (['Transportation' => $tev->total_transportation, 'Per Diem' => $tev->total_per_diem, 'Other Expenses' => $tev->total_other_expenses] as $lbl => $val)
            <div style="display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid rgba(255,255,255,0.1); font-size:0.82rem;">
                <span>{{ $lbl }}</span><span>₱{{ number_format($val, 2) }}</span>
            </div>
            @endforeach
            <div style="display:flex; justify-content:space-between; padding:8px 0 0; font-size:1rem; font-weight:700; color:var(--gold);">
                <span>Grand Total</span><span>₱{{ number_format($tev->grand_total, 2) }}</span>
            </div>
        </div>

        {{-- ── Status Context Card ── --}}
        @php
            $lastLog = $tev->approvalLogs->last();

            $contextIcon = match ($tev->status) {
                'submitted'            => '📬',
                'accountant_certified' => '✅',
                'rd_approved'          => '🏆',
                'cashier_released'     => '💵',
                'reimbursed'           => '💸',
                'liquidation_filed'    => '🗂',
                'liquidated'           => '🎉',
                'rejected'             => '🚫',
                default                => 'ℹ️',
            };

            $contextTitle = match ($tev->status) {
                'submitted'            => 'Submitted — Awaiting Accountant Review',
                'accountant_certified' => 'Accountant Certified — Awaiting RD Approval',
                'rd_approved'          => $tev->track === 'cash_advance'
                                            ? 'RD Approved — Awaiting Cash Release'
                                            : 'RD Approved — Awaiting Reimbursement',
                'cashier_released'     => 'Cash Advance Released — Awaiting Liquidation',
                'reimbursed'           => 'Reimbursed — Process Complete',
                'liquidation_filed'    => 'Liquidation Filed — Awaiting Cashier Approval',
                'liquidated'           => 'Fully Liquidated — Process Complete',
                'rejected'             => 'TEV Rejected',
                default                => ucwords(str_replace('_', ' ', $tev->status)),
            };

            $u = auth()->user();
            $contextNote = null;

            if ($tev->status === 'submitted') {
                if ($u->hasAnyRole(['accountant'])) {
                    $contextNote = 'Review the itinerary and expenses below, then certify or reject.';
                } elseif ($u->hasAnyRole(['hrmo'])) {
                    $contextNote = 'TEV submitted on behalf of ' . optional($emp)->last_name . '. Awaiting accountant review — no HR action needed.';
                }
            } elseif ($tev->status === 'accountant_certified') {
                if ($u->hasAnyRole(['ard', 'chief_admin_officer'])) {
                    $contextNote = 'This TEV has been certified by the Accountant. Review and approve or reject.';
                } elseif ($u->hasAnyRole(['hrmo'])) {
                    $contextNote = 'Certified by the Accountant. Waiting for RD/ARD approval — no HR action needed.';
                } elseif ($u->hasAnyRole(['accountant'])) {
                    $contextNote = 'You have certified this TEV. It is now pending RD/ARD approval.';
                }
            } elseif ($tev->status === 'rd_approved') {
                if ($u->hasAnyRole(['cashier'])) {
                    $contextNote = $tev->track === 'cash_advance'
                        ? 'RD has approved this TEV. Release the cash advance to the employee.'
                        : 'RD has approved this TEV. Process the reimbursement.';
                } elseif ($u->hasAnyRole(['hrmo'])) {
                    $contextNote = 'RD approved. The cashier will process the ' . ($tev->track === 'cash_advance' ? 'cash advance release' : 'reimbursement') . ' for ' . optional($emp)->last_name . ' shortly.';
                }
            } elseif ($tev->status === 'cashier_released') {
                if ($u->hasAnyRole(['hrmo'])) {
                    $contextNote = 'Cash advance released. File the liquidation on behalf of ' . optional($emp)->last_name . ' once travel is complete.';
                } elseif ($u->hasAnyRole(['cashier'])) {
                    $contextNote = 'You have released the cash advance. Awaiting liquidation filing by HRMO.';
                }
            } elseif ($tev->status === 'liquidation_filed') {
                if ($u->hasAnyRole(['cashier'])) {
                    $contextNote = 'HRMO has filed the liquidation on behalf of ' . optional($emp)->last_name . '. Review the balance and approve.';
                } elseif ($u->hasAnyRole(['hrmo'])) {
                    $contextNote = 'Liquidation filed. Waiting for the cashier to review and close out.';
                }
            } elseif ($tev->status === 'liquidated') {
                $contextNote = 'This TEV has been fully liquidated. No further action required.';
            } elseif ($tev->status === 'reimbursed') {
                $contextNote = 'Reimbursement has been processed. This TEV is now closed.';
            } elseif ($tev->status === 'rejected') {
                $contextNote = $lastLog?->remarks
                    ? 'Reason: ' . $lastLog->remarks
                    : 'This TEV was rejected. Please review the approval timeline for details.';
            }

            $ctxBorder = match ($tev->status) {
                'submitted'                                    => '#1565C0',
                'accountant_certified', 'rd_approved'          => '#2E7D52',
                'cashier_released', 'reimbursed'               => '#F57F17',
                'liquidation_filed'                            => '#6A1B9A',
                'liquidated'                                   => '#2E7D52',
                'rejected'                                     => '#B71C1C',
                default                                        => 'var(--navy)',
            };
            $ctxBg = match ($tev->status) {
                'submitted'                                    => '#E8EEF8',
                'accountant_certified', 'rd_approved'          => '#F1FAF5',
                'cashier_released', 'reimbursed'               => '#FFF8E1',
                'liquidation_filed'                            => '#F3E5F5',
                'liquidated'                                   => '#E8F5E9',
                'rejected'                                     => '#FFF0F0',
                default                                        => '#F8F9FF',
            };
            $ctxColor = match ($tev->status) {
                'submitted'                                    => '#1565C0',
                'accountant_certified', 'rd_approved'          => '#1B5E20',
                'cashier_released', 'reimbursed'               => '#7B5800',
                'liquidation_filed'                            => '#4A148C',
                'liquidated'                                   => '#1B5E20',
                'rejected'                                     => '#B71C1C',
                default                                        => 'var(--navy)',
            };
        @endphp
        <div style="border-radius:8px; border-left:4px solid {{ $ctxBorder }}; background:{{ $ctxBg }}; padding:14px 16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:{{ $contextNote ? '8px' : '0' }};">
                <span style="font-size:1.1rem; line-height:1;">{{ $contextIcon }}</span>
                <span style="font-size:0.78rem; font-weight:700; color:{{ $ctxColor }}; line-height:1.3;">{{ $contextTitle }}</span>
            </div>
            @if ($contextNote)
            <p style="margin:0; font-size:0.76rem; color:{{ $ctxColor }}; opacity:0.85; line-height:1.55; padding-left:26px;">{{ $contextNote }}</p>
            @endif
            @if ($lastLog && $tev->status !== 'draft')
            <div style="margin-top:10px; padding-top:8px; border-top:1px solid {{ $ctxBorder }}22; padding-left:26px; font-size:0.72rem; color:{{ $ctxColor }}; opacity:0.7;">
                Last action by <strong>{{ optional($lastLog->user)->name ?? 'System' }}</strong>
                · {{ $lastLog->performed_at?->format('M d, Y h:i A') }}
            </div>
            @endif
        </div>

        {{-- Cash Advance Reconciliation ── --}}
        @if ($showBalanceWidget)
        <div class="card">
            <div class="card-header"><h3>💰 Cash Advance Reconciliation</h3></div>
            <div class="card-body" style="padding:12px 16px;">
                <div class="balance-widget">
                    <div class="bw-row">
                        <span class="text-muted">Cash Advance Released</span>
                        <span class="fw-bold">₱{{ number_format($advanceAmount, 2) }}</span>
                    </div>
                    @if (in_array($tev->status, ['liquidation_filed','liquidated']))
                    <div class="bw-row">
                        <span class="text-muted">Actual Expenses Filed</span>
                        <span class="fw-bold">₱{{ number_format($actualAmount, 2) }}</span>
                    </div>
                    @php
                        $bwClass = $balanceDue > 0 ? 'bw-refund' : ($balanceDue < 0 ? 'bw-claim' : 'bw-settled');
                        $bwLabel = $balanceDue > 0
                            ? 'To Refund (Employee Owes)'
                            : ($balanceDue < 0 ? 'To Claim (DOLE Owes Employee)' : 'Settled — No Balance');
                    @endphp
                    <div class="bw-total {{ $bwClass }}">
                        <span>{{ $bwLabel }}</span>
                        <span>₱{{ number_format(abs($balanceDue), 2) }}</span>
                    </div>
                    @else
                    <div style="padding:8px 0; font-size:0.80rem; color:var(--text-light); font-style:italic;">Awaiting liquidation filing.</div>
                    @endif
                </div>
                @if ($tev->status === 'liquidated')
                <div style="margin-top:10px; padding:8px 12px; background:#E8F5E9; border-radius:6px; font-size:0.80rem; color:#1B5E20; font-weight:600;">
                    ✓ Liquidation fully approved and settled.
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Documents ── --}}
        @if ($showDocs)
        <div class="card no-print">
            <div class="card-header"><h3>📁 Documents</h3></div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:8px;">
                <a href="{{ route('reports.tev-itinerary', $tev->id) }}" target="_blank" class="btn btn-outline" style="justify-content:flex-start; gap:8px; text-align:left;">📄 Itinerary of Travel (Appendix A)</a>
                <a href="{{ route('reports.tev-travel-completed', $tev->id) }}" target="_blank" class="btn btn-outline" style="justify-content:flex-start; gap:8px; text-align:left;">📄 Certification of Travel Completed</a>
                <a href="{{ route('reports.tev-annex-a', $tev->id) }}" target="_blank" class="btn btn-outline" style="justify-content:flex-start; gap:8px; text-align:left;">📄 Annex A — Expenses Not Requiring Receipts</a>
                @if ($showDvPdf)
                <a href="{{ route('reports.tev-liquidation-dv', $tev->id) }}" target="_blank" class="btn btn-outline" style="justify-content:flex-start; gap:8px; text-align:left;">📄 Liquidation / Disbursement Voucher</a>
                @endif
            </div>
        </div>
        @endif

        {{--
            ── NOTE: No Submit button here. ──────────────────────────────────
            TEVs are auto-submitted on creation via TevController@store.
            The manual $canSubmit / Submit for Approval card has been removed.
            ──────────────────────────────────────────────────────────────────
        --}}

        {{-- Generic Approve ── --}}
        @if ($canApprove && $tev->status !== 'liquidation_filed')
        <div class="card no-print">
            <div class="card-header"><h3>✓ {{ $nextAction }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('tev.requests.approve', $tev->id) }}">
                    @csrf
                    <div class="form-group">
                        <label for="approve_remarks">Remarks (optional)</label>
                        <textarea id="approve_remarks" name="remarks" rows="2" placeholder="Add remarks..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" onclick="event.preventDefault(); confirmApprove('{{ $nextAction }}', this.form)">✓ {{ $nextAction }}</button>
                </form>
            </div>
        </div>
        @endif

        {{-- File Liquidation ── --}}
        @if ($canFileLiquidation)
        <div class="card no-print">
            <div class="card-header" style="border-left:3px solid #F9A825;"><h3 style="color:#7B5800;">💸 File Liquidation</h3></div>
            <div class="card-body">
                <p style="font-size:0.82rem; color:var(--text-mid); margin-bottom:14px;">Enter the <strong>actual total expenses</strong> incurred during travel. The system will compute the balance to refund or claim.</p>
                <div style="padding:10px 12px; background:#FFF8E1; border-radius:6px; font-size:0.80rem; margin-bottom:14px; color:#7B5800;">
                    Cash advance released: <strong>₱{{ number_format($tev->cash_advance_amount ?? $tev->grand_total, 2) }}</strong>
                </div>
                <form method="POST" action="{{ route('tev.requests.liquidate', $tev->id) }}">
                    @csrf
                    <div class="form-group">
                        <label for="actual_amount">Actual Amount Spent (₱) <span style="color:var(--red);">*</span></label>
                        <input type="number" id="actual_amount" name="actual_amount" step="0.01" min="0" value="{{ old('actual_amount') }}" placeholder="0.00" required>
                        @error('actual_amount')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="liquidation_remarks">Remarks (optional)</label>
                        <textarea id="liquidation_remarks" name="remarks" rows="2" placeholder="Add notes about the liquidation..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-gold" onclick="event.preventDefault(); confirmFileLiquidation(this.form)">💸 File Liquidation</button>
                </form>
            </div>
        </div>
        @endif

        {{-- Approve Liquidation ── --}}
        @if ($canApproveLiquidation)
        <div class="card no-print">
            <div class="card-header" style="border-left:3px solid #2E7D52;"><h3 style="color:#1B5E20;">✓ Approve Liquidation</h3></div>
            <div class="card-body">
                @php
                    $liqActual = $advanceAmount - $balanceDue;
                    $liqClass  = $balanceDue > 0 ? 'bw-refund' : ($balanceDue < 0 ? 'bw-claim' : 'bw-settled');
                    $liqNote   = $balanceDue > 0
                        ? 'Employee must refund ₱' . number_format($balanceDue, 2)
                        : ($balanceDue < 0 ? 'DOLE owes employee ₱' . number_format(abs($balanceDue), 2) : 'Fully settled — no balance due.');
                @endphp
                <div style="padding:12px 14px; background:#F1FAF5; border-radius:6px; font-size:0.82rem; margin-bottom:14px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px; flex-wrap:wrap; gap:4px;">
                        <span class="text-muted">Cash Advance Released</span><strong>₱{{ number_format($advanceAmount, 2) }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px; flex-wrap:wrap; gap:4px;">
                        <span class="text-muted">Actual Expenses Filed</span><strong>₱{{ number_format($liqActual, 2) }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-weight:700; padding-top:8px; border-top:1px solid rgba(0,0,0,0.08); flex-wrap:wrap; gap:4px;" class="{{ $liqClass }}">
                        <span>Balance</span><span>{{ $liqNote }}</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('tev.requests.liquidation.approve', $tev->id) }}">
                    @csrf
                    <div class="form-group">
                        <label for="liq_approve_remarks">Remarks (optional)</label>
                        <textarea id="liq_approve_remarks" name="remarks" rows="2" placeholder="Add remarks..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" onclick="event.preventDefault(); confirmApproveLiquidation(this.form)">✓ Approve Liquidation</button>
                </form>
            </div>
        </div>
        @endif

        {{-- Reject ── --}}
        @if ($canReject)
        <div class="card no-print">
            <div class="card-header" style="border-left:3px solid var(--red);"><h3 style="color:var(--red);">✕ Reject</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('tev.requests.reject', $tev->id) }}">
                    @csrf
                    <div class="form-group">
                        <label for="reject_remarks">Reason for Rejection <span style="color:var(--red);">*</span></label>
                        <textarea id="reject_remarks" name="remarks" rows="3" placeholder="State the reason for rejection (required)..." required></textarea>
                        @error('remarks')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this TEV? This action will be logged.')">✕ Reject TEV</button>
                </form>
            </div>
        </div>
        @endif

        {{-- Certify ── --}}
        @if ($canCertify)
        <div class="card no-print">
            <div class="card-header"><h3>📜 Certify Travel</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('tev.requests.certify', $tev->id) }}">
                    @csrf
                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; text-transform:none; font-size:0.875rem; font-weight:500; letter-spacing:0; color:var(--text);">
                            <input type="checkbox" name="travel_completed" value="1" {{ optional($tev->certification)->travel_completed ? 'checked' : '' }}>
                            Travel Completed
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="date_returned">Date Returned</label>
                        <input type="date" id="date_returned" name="date_returned" value="{{ optional($tev->certification)->date_returned?->toDateString() }}">
                    </div>
                    <div class="form-group">
                        <label for="place_reported_back">Place Reported Back</label>
                        <input type="text" id="place_reported_back" name="place_reported_back" value="{{ optional($tev->certification)->place_reported_back }}" placeholder="e.g. DOLE RO9 Office">
                    </div>
                    <div class="form-group">
                        <label for="annex_a_amount">Annex A Amount (₱)</label>
                        <input type="number" id="annex_a_amount" name="annex_a_amount" value="{{ optional($tev->certification)->annex_a_amount ?? 0 }}" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="annex_a_particulars">Annex A Particulars</label>
                        <textarea id="annex_a_particulars" name="annex_a_particulars" rows="2" placeholder="Details of expenses not requiring receipts...">{{ optional($tev->certification)->annex_a_particulars }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="agency_visited">Agency Visited</label>
                        <input type="text" id="agency_visited" name="agency_visited" value="{{ optional($tev->certification)->agency_visited }}" placeholder="Name of agency...">
                    </div>
                    <div class="form-group">
                        <label for="appearance_date">Appearance Date</label>
                        <input type="date" id="appearance_date" name="appearance_date" value="{{ optional($tev->certification)->appearance_date?->toDateString() }}">
                    </div>
                    <div class="form-group">
                        <label for="contact_person">Contact Person</label>
                        <input type="text" id="contact_person" name="contact_person" value="{{ optional($tev->certification)->contact_person }}" placeholder="Name of contact person...">
                    </div>
                    <button type="submit" class="btn btn-primary">📜 Save Certification</button>
                </form>
            </div>
        </div>
        @endif

    </div>{{-- end right --}}

</div>{{-- end show-grid --}}

@section('scripts')
<script>
function confirmApprove(action, form) {
    Swal.fire({
        title: 'Confirm Approval',
        text: 'Are you sure you want to proceed with: ' + action + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0F1B4C',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}

function confirmFileLiquidation(form) {
    var actualAmount = document.getElementById('actual_amount').value;
    Swal.fire({
        title: 'File Liquidation',
        text: 'Are you sure you want to file liquidation with this actual amount: ₱' + actualAmount + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0F1B4C',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'File Liquidation',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}

function confirmApproveLiquidation(form) {
    Swal.fire({
        title: 'Approve Liquidation',
        text: 'Are you sure you want to approve this liquidation and mark the TEV as liquidated?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0F1B4C',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Approve Liquidation',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}
</script>
@endsection

@endsection
