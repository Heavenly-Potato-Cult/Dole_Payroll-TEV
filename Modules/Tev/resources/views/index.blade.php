{{-- resources/views/tev/index.blade.php --}}
{{--
    Expects from TevController@index:
      $tevRequests — paginated TevRequest with employee, officeOrder
      $currentYear — int
--}}

@extends('layouts.tev')

@section('title', 'TEV Requests')
@section('page-title', 'Travel (TEV)')

@section('content')

@section('styles')
<style>
/* ─────────────────────────────────────────────────────
   FILTER FORM — buttons match input/select height
───────────────────────────────────────────────────── */
.filter-form {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.filter-form .ff-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.filter-form .ff-group label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--text-mid);
    line-height: 1;
    margin: 0;
}
.filter-form input,
.filter-form select {
    height: 38px;
    margin-bottom: 0 !important;
    box-sizing: border-box;
}
.filter-form .ff-btns {
    display: flex;
    gap: 8px;
    align-items: center;
    height: 38px;
}
.filter-form .ff-btns .btn {
    height: 38px;
    padding-top: 0;
    padding-bottom: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    white-space: nowrap;
}

/* ─────────────────────────────────────────────────────
   EXPANDABLE TABLE — same pattern as differential-index
───────────────────────────────────────────────────── */
.sd-detail-row  { display: none !important; }
.sd-expand-btn  { display: none !important; }

/* Fix pagination to prevent oversized angle brackets */
.pagination { font-size: 0.875rem !important; }
.pagination .page-link { font-size: 0.85rem !important; }

/* ── DESKTOP (≥ 769px) ── */
@media (min-width: 769px) {
    .sd-table              { display: table; width: 100%; border-collapse: collapse; }
    .sd-table thead        { display: table-header-group; }
    .sd-table tbody        { display: table-row-group; }
    .sd-table tr           { display: table-row; }
    .sd-table th,
    .sd-table td           { display: table-cell; }
}

/* ── MOBILE (≤ 768px) ── */
@media (max-width: 768px) {

    .filter-form             { flex-direction: column; align-items: stretch; }
    .filter-form .ff-group,
    .filter-form .ff-btns    { width: 100%; }
    .filter-form .ff-btns    { height: auto; }
    .filter-form .ff-btns .btn { flex: 1; }

    .table-wrap { overflow: visible; }

    .sd-table        { display: block; }
    .sd-table thead  { display: none; }
    .sd-table tbody  { display: block; }

    /* Card-style main row */
    .sd-table tr.sd-main-row {
        display: flex;
        align-items: center;
        gap: 0;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        cursor: pointer;
        transition: background .15s;
        min-height: 64px;
    }
    .sd-table tr.sd-main-row:active { background: var(--bg); }

    /* Hide columns moved to detail panel */
    .sd-table tr.sd-main-row td.col-track,
    .sd-table tr.sd-main-row td.col-oo,
    .sd-table tr.sd-main-row td.col-dates,
    .sd-table tr.sd-main-row td.col-total,
    .sd-table tr.sd-main-row td.col-actions { display: none; }

    /* Employee column — takes remaining space */
    .sd-table tr.sd-main-row td.col-employee {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 3px;
        padding: 0;
        min-width: 0;
    }
    .sd-table tr.sd-main-row td.col-employee .sd-name-label {
        font-weight: 700;
        font-size: 0.92rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sd-table tr.sd-main-row td.col-employee .sd-name-sub {
        font-size: 0.74rem;
        color: var(--text-mid);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* TEV number */
    .sd-table tr.sd-main-row td.col-tev {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        padding: 0 10px 0 0;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--navy);
        white-space: nowrap;
    }

    /* Status badge */
    .sd-table tr.sd-main-row td.col-status {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        padding: 0 8px;
    }

    /* Expand button */
    .sd-expand-btn {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        flex-shrink: 0;
        border-radius: 50%;
        background: transparent;
        border: 1.5px solid var(--border);
        cursor: pointer;
        font-size: 0.65rem;
        color: var(--text-mid);
        transition: transform .2s, background .15s, border-color .15s;
        margin-left: 4px;
    }
    .sd-main-row.open .sd-expand-btn {
        transform: rotate(180deg);
        background: var(--navy-light, #e8ecf4);
        border-color: var(--navy);
        color: var(--navy);
    }

    /* Expanded detail panel */
    tr.sd-detail-row.open {
        display: block !important;
        border-bottom: 1px solid var(--border);
        background: var(--bg, #f8f9fb);
    }
    tr.sd-detail-row.open td {
        display: block;
        padding: 12px 16px 16px;
    }

    /* Detail grid */
    .sd-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 20px;
        margin-bottom: 14px;
    }
    .sd-detail-item label {
        display: block;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-light);
        margin-bottom: 3px;
    }
    .sd-detail-item span {
        font-size: 0.85rem;
        color: var(--text);
        font-weight: 500;
    }
    .sd-detail-item span.mono { font-family: monospace; }
    .sd-detail-actions {
        display: flex;
        gap: 8px;
    }
    .sd-detail-actions .btn,
    .sd-detail-actions button {
        flex: 1;
        justify-content: center;
        text-align: center;
    }
}
</style>
@endsection

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>TEV Requests</h1>
        <p>Travel Expense Vouchers — Cash Advance and Reimbursement.</p>
    </div>
    <a href="{{ route('tev.requests.create') }}" class="btn btn-primary">+ New TEV</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

{{-- ── Filter bar ── --}}
<div class="card mb-3">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" action="{{ route('tev.office-orders.index') }}" class="filter-form">

            <div class="ff-group" style="min-width:180px;">
                <label for="track">Track</label>
                <select name="track" id="track">
                    <option value="">All Tracks</option>
                    <option value="cash_advance"  {{ request('track') === 'cash_advance'  ? 'selected' : '' }}>Cash Advance</option>
                    <option value="reimbursement" {{ request('track') === 'reimbursement' ? 'selected' : '' }}>Reimbursement</option>
                </select>
            </div>

            <div class="ff-btns">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('tev.office-orders.index') }}" class="btn btn-outline btn-sm">Reset</a>
            </div>

        </form>
    </div>
</div>

{{-- ── Table ── --}}
<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table class="sd-table">
                <thead>
                    <tr>
                        <th>TEV No.</th>
                        <th>Employee</th>
                        <th>Track</th>
                        <th>Office Order</th>
                        <th>Travel Dates</th>
                        <th class="text-right">Grand Total</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tevRequests as $tev)
                        @php
                            $emp = $tev->employee;

                            $trackLabel = $tev->track === 'cash_advance' ? 'Cash Advance' : 'Reimbursement';
                            $trackStyle = $tev->track === 'cash_advance'
                                ? 'background:#E8F5E9; color:#1B5E20; border:1px solid #43A047;'
                                : 'background:#E8EAF6; color:#1A237E; border:1px solid #3949AB;';

                            $statusClass = match ($tev->status) {
                                'submitted'            => 'badge-pending',
                                'hr_approved'          => 'badge-computed',
                                'accountant_certified' => 'badge-computed',
                                'rd_approved'          => 'badge-released',
                                'cashier_released'     => 'badge-locked',
                                'reimbursed'           => 'badge-locked',
                                'rejected'             => 'badge-inactive',
                                default                => 'badge-draft',
                            };
                            $statusLabel = ucwords(str_replace('_', ' ', $tev->status));

                            $isOwner  = $emp && ($emp->user_id === auth()->id() || $emp->employee_id === session('hris_employee_id'));
                            $canSubmit = $tev->status === 'draft'
                                && ($isOwner || auth()->user()->hasAnyRole(['payroll_officer', 'hrmo']));
                        @endphp

                        {{-- ── Main visible row ── --}}
                        <tr class="sd-main-row" data-id="{{ $tev->id }}" onclick="toggleSdRow(this)">

                            <td class="col-tev fw-bold" style="color:var(--navy); white-space:nowrap;">
                                {{ $tev->tev_no }}
                            </td>

                            <td class="col-employee">
                                <span class="sd-name-label">
                                    {{ optional($emp)->last_name }},
                                    {{ optional($emp)->first_name }}
                                    @if (optional($emp)->middle_name)
                                        {{ substr($emp->middle_name, 0, 1) }}.
                                    @endif
                                </span>
                                <span class="sd-name-sub">
                                    {{ optional($tev->officeOrder)->office_order_no ?? '—' }}
                                </span>
                            </td>

                            <td class="col-track">
                                <span style="font-size:0.72rem; font-weight:700; padding:3px 8px;
                                             border-radius:12px; {{ $trackStyle }}">
                                    {{ $trackLabel }}
                                </span>
                            </td>

                            <td class="col-oo" style="font-size:0.82rem;">
                                {{ optional($tev->officeOrder)->office_order_no ?? '—' }}
                            </td>

                            <td class="col-dates text-muted" style="font-size:0.82rem; white-space:nowrap;">
                                {{ $tev->travel_date_start->format('M d') }}
                                –
                                {{ $tev->travel_date_end->format('M d, Y') }}
                            </td>

                            <td class="col-total text-right fw-bold">
                                ₱{{ number_format($tev->grand_total, 2) }}
                            </td>

                            <td class="col-status">
                                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>

                            <td class="col-actions">
                                <div class="d-flex gap-2" style="justify-content:center;">
                                    <a href="{{ route('tev.requests.show', $tev->id) }}"
                                       class="btn btn-outline btn-sm"
                                       onclick="event.stopPropagation();">View</a>

                                    @if ($canSubmit)
                                        <form method="POST"
                                              action="{{ route('tev.requests.submit', $tev->id) }}"
                                              onsubmit="event.stopPropagation(); return confirm('Submit this TEV for approval?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                    onclick="event.stopPropagation();">Submit</button>
                                        </form>
                                    @endif
                                </div>

                                {{-- Mobile expand chevron --}}
                                <span class="sd-expand-btn" aria-label="Expand">▼</span>
                            </td>

                        </tr>

                        {{-- ── Expandable detail row (mobile only) ── --}}
                        <tr class="sd-detail-row" id="sd-detail-{{ $tev->id }}">
                            <td colspan="8">
                                <div class="sd-detail-grid">
                                    <div class="sd-detail-item">
                                        <label>TEV No.</label>
                                        <span style="color:var(--navy); font-weight:700;">{{ $tev->tev_no }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Track</label>
                                        <span>
                                            <span style="font-size:0.72rem; font-weight:700;
                                                         padding:2px 8px; border-radius:10px;
                                                         {{ $trackStyle }}">{{ $trackLabel }}</span>
                                        </span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Office Order</label>
                                        <span>{{ optional($tev->officeOrder)->office_order_no ?? '—' }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Grand Total</label>
                                        <span class="mono" style="color:var(--navy); font-weight:700;">
                                            ₱{{ number_format($tev->grand_total, 2) }}
                                        </span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Travel Start</label>
                                        <span>{{ $tev->travel_date_start->format('M d, Y') }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Travel End</label>
                                        <span>{{ $tev->travel_date_end->format('M d, Y') }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Status</label>
                                        <span><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></span>
                                    </div>
                                </div>
                                <div class="sd-detail-actions">
                                    <a href="{{ route('tev.requests.show', $tev->id) }}"
                                       class="btn btn-outline btn-sm">View</a>

                                    @if ($canSubmit)
                                        <form method="POST"
                                              action="{{ route('tev.requests.submit', $tev->id) }}"
                                              style="flex:1;"
                                              onsubmit="return confirm('Submit this TEV for approval?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                    style="width:100%;">Submit</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding:40px; color:var(--text-light);">
                                No TEV requests found.
                                <a href="{{ route('tev.requests.create') }}">Create one now →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if ($tevRequests->hasPages())
<div style="padding:4px 20px 8px;">
    {{ $tevRequests->links('pagination::custom') }}
</div>
@endif

@endsection

@section('scripts')
<script>
function toggleSdRow(mainRow) {
    if (window.innerWidth > 768) return;

    const id     = mainRow.dataset.id;
    const detail = document.getElementById('sd-detail-' + id);
    const isOpen = mainRow.classList.contains('open');

    document.querySelectorAll('.sd-main-row.open').forEach(r => r.classList.remove('open'));
    document.querySelectorAll('.sd-detail-row.open').forEach(r => r.classList.remove('open'));

    if (!isOpen) {
        mainRow.classList.add('open');
        detail.classList.add('open');
    }
}
</script>
@endsection