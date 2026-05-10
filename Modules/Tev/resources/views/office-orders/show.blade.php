{{-- resources/views/office-orders/show.blade.php --}}
{{--
    Expects from OfficeOrderController@show:
      $order — OfficeOrder with employee, approver, tevRequests.employee
--}}

@extends('layouts.tev')

@section('title', 'Office Order — ' . $order->office_order_no)
@section('page-title', 'Travel (TEV)')

@section('styles')
<style>
.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px 24px;
    margin-bottom: 20px;
    font-size: 0.85rem;
}
.detail-item { display: flex; flex-direction: column; gap: 2px; }
.detail-item .label {
    font-size: 0.70rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: var(--text-light);
}
.detail-item .value { font-weight: 600; color: var(--text); }

/* ── Responsive show grid ── */
.show-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
    align-items: start;
}
@media (max-width: 900px) {
    .show-grid { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .page-header .d-flex { flex-wrap: wrap; }
    .detail-grid { grid-template-columns: 1fr 1fr; }
}

@media print {
    .no-print { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
    body { font-size: 9pt; }
    @page { margin: 1.2cm 1cm; }
}
</style>
@endsection

@section('content')

@php
    $emp = $order->employee;

    $statusClass = match ($order->status) {
        'approved'  => 'badge-released',
        'cancelled' => 'badge-inactive',
        default     => 'badge-draft',
    };
    $statusLabel = match ($order->status) {
        'draft'     => 'Draft',
        'approved'  => 'Approved',
        'cancelled' => 'Cancelled',
        default     => ucfirst($order->status),
    };

    $typeStyle = match ($order->travel_type) {
        'regional' => 'background:#FFF8E1; color:#F57F17; border:1px solid #F9A825;',
        'national' => 'background:#E8EAF6; color:#1A237E; border:1px solid #3949AB;',
        default    => 'background:#E8F5E9; color:#1B5E20; border:1px solid #43A047;',
    };

    $hasTev     = $order->tevRequests->count() > 0;
    $canApprove = auth()->user()->hasAnyRole(['ard', 'chief_admin_officer'])
                  && $order->status === 'draft';
    $canEdit    = auth()->user()->hasAnyRole(['payroll_officer', 'hrmo'])
                  && $order->status === 'draft';
    $canCancel  = auth()->user()->hasAnyRole(['hrmo', 'ard', 'chief_admin_officer'])
                  && $order->status === 'approved'
                  && !$hasTev;
@endphp

<div class="page-header no-print">
    <div class="page-header-left">
        <h1>{{ $order->office_order_no }}</h1>
        <p>
            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
            &nbsp;
            <span style="font-size:0.75rem; font-weight:700; padding:3px 10px;
                         border-radius:12px; {{ $typeStyle }}">
                {{ ucfirst($order->travel_type) }}
            </span>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap no-print">
        @if ($canEdit)
            <a href="{{ route('tev.office-orders.edit', $order->id) }}" class="btn btn-outline btn-sm">
                ✏ Edit
            </a>
        @endif
        <button onclick="window.print()" class="btn btn-outline btn-sm">🖨 Print</button>
        <a href="{{ route('tev.office-orders.index') }}" class="btn btn-outline btn-sm">← Back</a>
    </div>
</div>

<div class="show-grid">

    {{-- ── Left: main detail card ── --}}
    <div class="card">
        <div class="card-header">
            <h3>📝 Office Order Details</h3>
            <span style="font-size:0.78rem; color:var(--text-light);">
                Issued: {{ $order->created_at->format('M d, Y') }}
            </span>
        </div>
        <div class="card-body">

            <div class="detail-grid">
                <div class="detail-item">
                    <span class="label">OO Number</span>
                    <span class="value" style="color:var(--navy); font-size:1rem;">
                        {{ $order->office_order_no }}
                    </span>
                </div>
                <div class="detail-item">
                    <span class="label">Status</span>
                    <span class="value">
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="label">Travel Type</span>
                    <span class="value">
                        <span style="font-size:0.75rem; font-weight:700; padding:3px 10px;
                                     border-radius:12px; {{ $typeStyle }}">
                            {{ ucfirst($order->travel_type) }}
                        </span>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="label">Employee (Traveler)</span>
                    <span class="value">
                        {{ optional($emp)->last_name }}, {{ optional($emp)->first_name }}
                        @if (optional($emp)->middle_name)
                            {{ substr($emp->middle_name, 0, 1) }}.
                        @endif
                    </span>
                </div>
                <div class="detail-item">
                    <span class="label">Position</span>
                    <span class="value">{{ optional($emp)->position_title ?? '—' }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Destination</span>
                    <span class="value">{{ $order->destination }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Travel Date — Start</span>
                    <span class="value">{{ $order->travel_date_start->format('F j, Y') }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Travel Date — End</span>
                    <span class="value">{{ $order->travel_date_end->format('F j, Y') }}</span>
                </div>
                @if ($order->approved_at)
                    <div class="detail-item">
                        <span class="label">Approved By</span>
                        <span class="value">{{ optional($order->approver)->name ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Approved On</span>
                        <span class="value">{{ $order->approved_at->format('M d, Y h:i A') }}</span>
                    </div>
                @endif
            </div>

            <div class="detail-item" style="margin-bottom:16px;">
                <span class="label">Purpose</span>
                <span class="value" style="font-weight:400; line-height:1.5; margin-top:4px;">
                    {{ $order->purpose }}
                </span>
            </div>

            @if ($order->remarks)
            <div style="padding:10px 14px; background:var(--surface-alt, #f8f9ff);
                        border-left:3px solid var(--navy); border-radius:4px;
                        font-size:0.83rem;">
                <strong>Remarks:</strong> {{ $order->remarks }}
            </div>
            @endif

        </div>
    </div>

    {{-- ── Right panel: actions ── --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

        @if ($canApprove)
        <div class="card no-print">
            <div class="card-header"><h3>✓ Approve</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('tev.office-orders.approve', $order->id) }}">
                    @csrf
                    <div class="form-group">
                        <label for="approve_remarks">Remarks (optional)</label>
                        <textarea id="approve_remarks" name="remarks" rows="2"
                                  placeholder="Add approval remarks..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Approve this Office Order?')">
                        ✓ Approve Office Order
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if ($canCancel)
        <div class="card no-print">
            <div class="card-header"><h3>✕ Cancel</h3></div>
            <div class="card-body">
                <p style="font-size:0.82rem; color:var(--text-mid); margin-bottom:10px;">
                    Cancelling is irreversible and only allowed when no TEV requests are linked.
                </p>
                <form method="POST" action="{{ route('tev.office-orders.cancel', $order->id) }}">
                    @csrf
                    <div class="form-group">
                        <label for="cancel_remarks">Reason (optional)</label>
                        <textarea id="cancel_remarks" name="remarks" rows="2"
                                  placeholder="Reason for cancellation..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Cancel this Office Order? This cannot be undone.')">
                        ✕ Cancel Office Order
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- TEV count info --}}
        <div class="card">
            <div class="card-header"><h3>✈ Linked TEVs</h3></div>
            <div class="card-body" style="padding:14px 20px;">
                @if ($hasTev)
                    <p style="font-size:0.83rem; margin:0 0 8px; color:var(--text-mid);">
                        {{ $order->tevRequests->count() }} TEV request(s) linked to this order.
                    </p>
                    <a href="{{ route('tev.requests.index') }}?office_order_id={{ $order->id }}"
                       class="btn btn-outline btn-sm">View TEV Requests</a>
                @else
                    <p style="font-size:0.83rem; color:var(--text-light); margin:0 0 10px;">
                        No TEV requests yet.
                    </p>
                    @if ($order->status === 'approved' && auth()->user()->hasAnyRole(['payroll_officer', 'hrmo']))
                        <a href="{{ route('tev.requests.create') }}" class="btn btn-primary btn-sm">
                            + Create TEV
                        </a>
                    @endif
                @endif
            </div>
        </div>

    </div>

</div>

{{-- ── Linked TEV requests table ── --}}
@if ($hasTev)
<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <h3>✈ TEV Requests</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>TEV No.</th>
                        <th>Employee</th>
                        <th>Track</th>
                        <th>Travel Dates</th>
                        <th class="text-right">Grand Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->tevRequests as $tev)
                        @php
                            $tevStatusClass = match ($tev->status) {
                                'submitted'            => 'badge-pending',
                                'hr_approved'          => 'badge-computed',
                                'accountant_certified' => 'badge-computed',
                                'rd_approved'          => 'badge-released',
                                'cashier_released'     => 'badge-locked',
                                'reimbursed'           => 'badge-locked',
                                'rejected'             => 'badge-inactive',
                                default                => 'badge-draft',
                            };
                            $tevStatusLabel = ucwords(str_replace('_', ' ', $tev->status));
                            $trackLabel     = $tev->track === 'cash_advance' ? 'Cash Advance' : 'Reimbursement';
                            $trackStyle     = $tev->track === 'cash_advance'
                                ? 'background:#E8F5E9; color:#1B5E20; border:1px solid #43A047;'
                                : 'background:#E8EAF6; color:#1A237E; border:1px solid #3949AB;';
                        @endphp
                        <tr>
                            <td class="fw-bold" style="color:var(--navy);">{{ $tev->tev_no }}</td>
                            <td>
                                {{ optional($tev->employee)->last_name }},
                                {{ optional($tev->employee)->first_name }}
                            </td>
                            <td>
                                <span style="font-size:0.72rem; font-weight:700; padding:3px 8px;
                                             border-radius:12px; {{ $trackStyle }}">
                                    {{ $trackLabel }}
                                </span>
                            </td>
                            <td class="text-muted" style="font-size:0.82rem; white-space:nowrap;">
                                {{ $tev->travel_date_start->format('M d') }}
                                –
                                {{ $tev->travel_date_end->format('M d, Y') }}
                            </td>
                            <td class="text-right fw-bold">
                                ₱{{ number_format($tev->grand_total, 2) }}
                            </td>
                            <td>
                                <span class="badge {{ $tevStatusClass }}">{{ $tevStatusLabel }}</span>
                            </td>
                            <td>
                                <a href="{{ route('tev.requests.show', $tev->id) }}"
                                   class="btn btn-outline btn-sm">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection