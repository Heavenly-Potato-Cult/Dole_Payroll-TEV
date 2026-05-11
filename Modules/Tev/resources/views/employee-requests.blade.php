{{-- resources/views/tev/employee-requests.blade.php --}}
{{--
    Expects from TevController@index:
      $inProcessRequests  — paginated TevRequest (in-process)
      $liquidatedRequests — paginated TevRequest (liquidated)
      $currentYear        — int
--}}

@extends('layouts.employee')

@section('title', 'My TEV Requests')
@section('page-title', 'My TEV Requests')

@section('styles')
<style>
/* ─────────────────────────────────────────────────────
   TABLE WRAPPER & BASE STYLES
───────────────────────────────────────────────────── */
.table-wrap {
    background: white;
    border-radius: var(--radius, 8px);
    overflow: hidden;
    box-shadow: var(--shadow, 0 2px 8px rgba(15,27,76,0.09));
    border: 1px solid var(--border, #DDE1EE);
}

/* ─────────────────────────────────────────────────────
   FILTER FORM
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
    border: 1.5px solid var(--border) !important;
    border-radius: 6px !important;
    background: white !important;
    padding: 8px 12px !important;
    font-family: var(--font) !important;
    font-size: 0.92rem !important;
    color: var(--text) !important;
    outline: none !important;
    transition: border-color 0.15s, box-shadow 0.15s !important;
}
.filter-form select:focus {
    border-color: var(--navy) !important;
    box-shadow: 0 0 0 3px rgba(15,27,76,0.09) !important;
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
   TAB NAV
───────────────────────────────────────────────────── */
.tev-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--border, #e2e8f0);
    margin-bottom: 0;
}
.tev-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-mid, #64748b);
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    transition: color .15s, border-color .15s;
    text-decoration: none;
}
.tev-tab-btn:hover {
    color: var(--navy, #1e2d4f);
}
.tev-tab-btn.active {
    color: var(--navy, #1e2d4f);
    border-bottom-color: var(--navy, #1e2d4f);
}
.tev-tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1;
}
.tev-tab-icon-process  { color: #F59E0B; }
.tev-tab-icon-liquidated { color: #10B981; }

/* ─────────────────────────────────────────────────────
   TAB PANELS
───────────────────────────────────────────────────── */
.tev-tab-panel { display: none; }
.tev-tab-panel.active { display: block; }

/* ─────────────────────────────────────────────────────
   EXPANDABLE TABLE — mobile card pattern
───────────────────────────────────────────────────── */
.sd-detail-row  { display: none !important; }
.sd-expand-btn  { display: none !important; }

/* ── DESKTOP (≥ 769px) ── */
@media (min-width: 769px) {
    .sd-detail-row  { display: none !important; }
    .sd-table              { display: table; width: 100%; border-collapse: collapse; }
    .sd-table thead        { display: table-header-group; }
    .sd-table tbody        { display: table-row-group; }
    .sd-table tr           { display: table-row; }
    .sd-table th,
    .sd-table td           { display: table-cell; }
    
    /* Override global badge styles for TEV status indicators */
    .badge-warning {
        background-color: #F59E0B !important;
        color: white !important;
    }
    .badge-info {
        background-color: #3B82F6 !important;
        color: white !important;
    }
    .badge-success {
        background-color: #10B981 !important;
        color: white !important;
    }
    .badge-primary {
        background-color: #0F1B4C !important;
        color: white !important;
    }
    .badge-danger {
        background-color: #B71C1C !important;
        color: white !important;
    }
    .badge-dark {
        background-color: #343A40 !important;
        color: white !important;
    }
    .badge-secondary {
        background-color: #6C757D !important;
        color: white !important;
    }
    
    /* Override global table styles for this specific table */
    .sd-table {
        font-size: 0.875rem;
        border: none !important;
        margin: 0 !important;
    }
    .sd-table th {
        background: var(--navy) !important;
        color: white !important;
        padding: 14px 16px !important;
        text-align: left !important;
        font-weight: 600 !important;
        font-size: 0.77rem !important;
        letter-spacing: 0.03em !important;
        text-transform: uppercase !important;
        white-space: nowrap !important;
        border: none !important;
        border-bottom: 2px solid var(--navy) !important;
    }
    .sd-table td {
        padding: 14px 16px !important;
        vertical-align: middle !important;
        border-bottom: 1px solid var(--border) !important;
        border-top: none !important;
        border-left: none !important;
        border-right: none !important;
    }
    .sd-table tbody tr:last-child td {
        border-bottom: none !important;
    }
    .sd-table tbody tr:hover {
        background: var(--navy-light) !important;
    }
    .sd-table tbody tr {
        border-bottom: 1px solid var(--border) !important;
        transition: background-color 0.15s ease !important;
    }
    
    /* Fix button alignment in actions column */
    .sd-table .col-actions .d-flex {
        gap: 8px;
        justify-content: center;
        align-items: center;
    }
}

/* ── MOBILE (≤ 768px) ── */
@media (max-width: 768px) {

    .filter-form             { flex-direction: column; align-items: stretch; }
    .filter-form .ff-group,
    .filter-form .ff-btns    { width: 100%; }
    .filter-form .ff-btns    { height: auto; }
    .filter-form .ff-btns .btn { flex: 1; justify-content: center; text-align: center; }

    .tev-tabs { overflow-x: auto; }
    .tev-tab-btn { white-space: nowrap; padding: 10px 14px; font-size: 0.82rem; }

    .table-wrap { overflow: visible; }

    .sd-table        { display: block; }
    .sd-table thead  { display: none; }
    .sd-table tbody  { display: block; }
    .sd-table tr       { display: flex; }

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
    .sd-table tr.sd-main-row td.col-track,
    .sd-table tr.sd-main-row td.col-dates,
    .sd-table tr.sd-main-row td.col-total,
    .sd-table tr.sd-main-row td.col-actions { display: none; }

    .sd-table tr.sd-main-row td.col-destination {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 3px;
        padding: 0;
        min-width: 0;
    }
    .sd-table tr.sd-main-row td.col-destination .sd-name-label {
        font-weight: 700;
        font-size: 0.92rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sd-table tr.sd-main-row td.col-destination .sd-name-sub {
        font-size: 0.74rem;
        color: var(--text-mid);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
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
    .sd-table tr.sd-main-row td.col-status {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        padding: 0 8px;
    }
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
    tr.sd-detail-row.open {
        display: block !important;
        border-bottom: 1px solid var(--border);
        background: var(--bg, #f8f9fb);
    }
    tr.sd-detail-row.open td {
        display: block;
        padding: 12px 16px 16px;
    }
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
        <h1>My TEV Requests</h1>
        <p>Your travel expense vouchers and cash advances</p>
    </div>
    <a href="{{ route('tev.requests.create') }}" class="btn btn-primary">+ New TEV</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

{{-- ── Tab Navigation Card ── --}}
<div class="card" style="margin-bottom: 24px;">
    <div style="padding: 20px;">
        <div class="tev-tabs">
            <button class="tev-tab-btn active"
                    id="tab-btn-process"
                    onclick="switchTab('process')">
                <span class="tev-tab-icon-process">&#9654;</span>
                In Process
                <span class="tev-tab-badge tev-tab-badge-process">{{ $inProcessRequests->total() }}</span>
            </button>
            <button class="tev-tab-btn"
                    id="tab-btn-liquidated"
                    onclick="switchTab('liquidated')">
                <span class="tev-tab-icon-liquidated">&#10003;</span>
                Liquidated
                @if($liquidatedRequests->count() > 0)
                    <span class="tev-tab-badge tev-tab-badge-liquidated">{{ $liquidatedRequests->total() }}</span>
                @endif
            </button>
        </div>
    </div>
</div>

{{-- ══════════════ IN-PROCESS PANEL ══════════════ --}}
<div class="tev-tab-panel active" id="tab-panel-process">

    {{-- Filter Card --}}
    <div class="card" style="margin-bottom: 24px;">
        <div style="padding: 20px;">
            <form method="GET" action="{{ route('tev.requests.index') }}" class="filter-form">
                <input type="hidden" name="tab" value="process">

                <div class="ff-group" style="min-width:180px;">
                    <label for="track_process">Track</label>
                    <select name="track" id="track_process">
                        <option value="">All Tracks</option>
                        <option value="cash_advance"  {{ request('track') === 'cash_advance'  && request('tab','process') === 'process' ? 'selected' : '' }}>Cash Advance</option>
                        <option value="reimbursement" {{ request('track') === 'reimbursement' && request('tab','process') === 'process' ? 'selected' : '' }}>Reimbursement</option>
                    </select>
                </div>

                <div class="ff-btns">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('tev.requests.index') }}?tab=process" class="btn btn-outline btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card" style="margin-bottom: 48px;">
        <div class="table-wrap">
            <table class="sd-table">
                <thead>
                    <tr>
                        <th>TEV No.</th>
                        <th>Destination</th>
                        <th>Track</th>
                        <th>Travel Dates</th>
                        <th class="text-right">Grand Total</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inProcessRequests as $tev)
                        @php
                            $emp = $tev->employee;
                            $trackLabel = $tev->track === 'cash_advance' ? 'Cash Advance' : 'Reimbursement';
                            $trackStyle = $tev->track === 'cash_advance'
                                ? 'background:#E8F5E9; color:#1B5E20; border:1px solid #43A047;'
                                : 'background:#E8EAF6; color:#1A237E; border:1px solid #3949AB;';
                            $statusClass = match ($tev->status) {
                                'submitted'            => 'badge-warning',
                                'hr_approved'          => 'badge-info',
                                'accountant_certified' => 'badge-info',
                                'rd_approved'          => 'badge-success',
                                'cashier_released'     => 'badge-primary',
                                'reimbursed'           => 'badge-primary',
                                'rejected'             => 'badge-danger',
                                'liquidated'           => 'badge-success',
                                default                => 'badge-secondary',
                            };
                            $statusLabel = ucwords(str_replace('_', ' ', $tev->status));
                            $isOwner  = $emp && ($emp->user_id === auth()->id() || $emp->employee_id === session('hris_employee_id'));
                            $canSubmit = $tev->status === 'draft'
                                && ($isOwner || auth()->user()->hasAnyRole(['payroll_officer', 'hrmo']));
                        @endphp

                        <tr class="sd-main-row" data-id="{{ $tev->id }}" onclick="toggleSdRow(this)">
                            <td class="col-tev fw-bold" style="color:var(--navy); white-space:nowrap;">
                                {{ $tev->tev_no }}
                            </td>
                            <td class="col-destination">
                                <span class="sd-name-label">{{ $tev->destination }}</span>
                                <span class="sd-name-sub">TEV Request</span>
                            </td>
                            <td class="col-track">
                                <span style="font-size:0.72rem; font-weight:700; padding:3px 8px; border-radius:12px; {{ $trackStyle }}">
                                    {{ $trackLabel }}
                                </span>
                            </td>
                            <td class="col-dates text-muted" style="font-size:0.82rem; white-space:nowrap;">
                                {{ $tev->travel_date_start->format('M d') }} – {{ $tev->travel_date_end->format('M d, Y') }}
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
                                <span class="sd-expand-btn" aria-label="Expand">▼</span>
                            </td>
                        </tr>

                        <tr class="sd-detail-row" id="sd-detail-{{ $tev->id }}">
                            <td colspan="7">
                                <div class="sd-detail-grid">
                                    <div class="sd-detail-item">
                                        <label>TEV No.</label>
                                        <span style="color:var(--navy); font-weight:700;">{{ $tev->tev_no }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Destination</label>
                                        <span>{{ $tev->destination }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Track</label>
                                        <span>
                                            <span style="font-size:0.72rem; font-weight:700; padding:2px 8px; border-radius:10px; {{ $trackStyle }}">{{ $trackLabel }}</span>
                                        </span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Grand Total</label>
                                        <span class="mono" style="color:var(--navy); font-weight:700;">₱{{ number_format($tev->grand_total, 2) }}</span>
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
                                       class="btn btn-primary btn-sm">View</a>
                                    @if ($canSubmit)
                                        <form method="POST"
                                              action="{{ route('tev.requests.submit', $tev->id) }}"
                                              style="flex:1;"
                                              onsubmit="return confirm('Submit this TEV for approval?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" style="width:100%;">Submit</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:40px; color:var(--text-light);">
                                No in-process TEV requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($inProcessRequests->hasPages())
        <div style="padding: 16px 20px;">
            {{ $inProcessRequests->appends(['tab' => 'process', 'track' => request('track')])->links() }}
        </div>
    @endif

</div>{{-- end #tab-panel-process --}}

{{-- ══════════════ LIQUIDATED PANEL ══════════════ --}}
<div class="tev-tab-panel" id="tab-panel-liquidated">

    {{-- Filter Card --}}
    <div class="card" style="margin-bottom: 24px;">
        <div style="padding: 20px;">
            <form method="GET" action="{{ route('tev.requests.index') }}" class="filter-form">
                <input type="hidden" name="tab" value="liquidated">

                <div class="ff-group" style="min-width:180px;">
                    <label for="track_liquidated">Track</label>
                    <select name="track" id="track_liquidated">
                        <option value="">All Tracks</option>
                        <option value="cash_advance"  {{ request('track') === 'cash_advance'  && request('tab') === 'liquidated' ? 'selected' : '' }}>Cash Advance</option>
                        <option value="reimbursement" {{ request('track') === 'reimbursement' && request('tab') === 'liquidated' ? 'selected' : '' }}>Reimbursement</option>
                    </select>
                </div>

                <div class="ff-btns">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('tev.requests.index') }}?tab=liquidated" class="btn btn-outline btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card" style="margin-bottom: 48px;">
        <div class="table-wrap">
            <table class="sd-table">
                <thead>
                    <tr>
                        <th>TEV No.</th>
                        <th>Destination</th>
                        <th>Track</th>
                        <th>Travel Dates</th>
                        <th class="text-right">Grand Total</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($liquidatedRequests as $tev)
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
                                'liquidated'           => 'badge-locked',
                                'rejected'             => 'badge-inactive',
                                default                => 'badge-draft',
                            };
                            $statusLabel = ucwords(str_replace('_', ' ', $tev->status));
                        @endphp

                        <tr class="sd-main-row" data-id="liq-{{ $tev->id }}" onclick="toggleSdRow(this)">
                            <td class="col-tev fw-bold" style="color:var(--navy); white-space:nowrap;">
                                {{ $tev->tev_no }}
                            </td>
                            <td class="col-destination">
                                <span class="sd-name-label">{{ $tev->destination }}</span>
                                <span class="sd-name-sub">TEV Request</span>
                            </td>
                            <td class="col-track">
                                <span style="font-size:0.72rem; font-weight:700; padding:3px 8px; border-radius:12px; {{ $trackStyle }}">
                                    {{ $trackLabel }}
                                </span>
                            </td>
                            <td class="col-dates text-muted" style="font-size:0.82rem; white-space:nowrap;">
                                {{ $tev->travel_date_start->format('M d') }} – {{ $tev->travel_date_end->format('M d, Y') }}
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
                                </div>
                                <span class="sd-expand-btn" aria-label="Expand">▼</span>
                            </td>
                        </tr>

                        <tr class="sd-detail-row" id="sd-detail-liq-{{ $tev->id }}">
                            <td colspan="7">
                                <div class="sd-detail-grid">
                                    <div class="sd-detail-item">
                                        <label>TEV No.</label>
                                        <span style="color:var(--navy); font-weight:700;">{{ $tev->tev_no }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Destination</label>
                                        <span>{{ $tev->destination }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Track</label>
                                        <span>
                                            <span style="font-size:0.72rem; font-weight:700; padding:2px 8px; border-radius:10px; {{ $trackStyle }}">{{ $trackLabel }}</span>
                                        </span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Grand Total</label>
                                        <span class="mono" style="color:var(--navy); font-weight:700;">₱{{ number_format($tev->grand_total, 2) }}</span>
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
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:40px; color:var(--text-light);">
                                No liquidated TEV requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($liquidatedRequests->hasPages())
        <div style="padding: 16px 20px;">
            {{ $liquidatedRequests->appends(['tab' => 'liquidated', 'track' => request('track')])->links() }}
        </div>
    @endif

</div>{{-- end #tab-panel-liquidated --}}

@endsection

@section('scripts')
<script>
/* ── Tab switching ── */
function switchTab(tab) {
    // Hide ALL panels first
    document.querySelectorAll('.tev-tab-panel').forEach(panel => {
        panel.classList.remove('active');
        panel.style.display = 'none';
    });
    
    // Remove active from all tabs
    document.querySelectorAll('.tev-tab-btn').forEach(btn => btn.classList.remove('active'));
    
    // Show selected panel and activate tab
    const tabBtn = document.getElementById('tab-btn-' + tab);
    const tabPanel = document.getElementById('tab-panel-' + tab);
    
    if (tabBtn && tabPanel) {
        tabBtn.classList.add('active');
        tabPanel.classList.add('active');
        tabPanel.style.display = 'block';
    }
}

/* ── Restore active tab from URL ── */
(function () {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    if (tab === 'liquidated') switchTab('liquidated');
})();
</script>
@endsection
