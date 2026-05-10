{{-- resources/views/office-orders/index.blade.php --}}
{{--
    Expects from OfficeOrderController@index:
      $orders      — paginated OfficeOrder (with employee)
      $currentYear — int
--}}

@extends('layouts.tev')

@section('title', 'Office Orders')
@section('page-title', 'Travel (TEV)')

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
    .sd-table tr.sd-main-row td.col-purpose,
    .sd-table tr.sd-main-row td.col-destination,
    .sd-table tr.sd-main-row td.col-type,
    .sd-table tr.sd-main-row td.col-dates,
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

    /* OO number */
    .sd-table tr.sd-main-row td.col-oo {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        padding: 0 10px 0 0;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--navy);
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

    /* Detail grid inside expanded panel */
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
    .sd-detail-item.full-width { grid-column: 1 / -1; }
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
        <h1>Office Orders</h1>
        <p>Manage travel authority documents for DOLE RO9 employees.</p>
    </div>
    @if (auth()->user()->hasAnyRole(['payroll_officer', 'hrmo', 'super_admin']))
        <a href="{{ route('tev.office-orders.create') }}" class="btn btn-primary">
            + New Office Order
        </a>
    @endif
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

            <div class="ff-group" style="min-width:120px;">
                <label for="year">Year</label>
                <select name="year" id="year">
                    <option value="">All Years</option>
                    @foreach (range($currentYear, $currentYear - 3) as $y)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="ff-group" style="min-width:160px;">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">All Statuses</option>
                    <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>Draft</option>
                    <option value="approved"  {{ request('status') === 'approved'  ? 'selected' : '' }}>Approved</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                        <th>OO No.</th>
                        <th>Employee</th>
                        <th>Purpose</th>
                        <th>Destination</th>
                        <th>Travel Type</th>
                        <th>Date Range</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
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
                            $typeLabel = ucfirst($order->travel_type);
                        @endphp

                        {{-- ── Main visible row ── --}}
                        <tr class="sd-main-row" data-id="{{ $order->id }}" onclick="toggleSdRow(this)">

                            <td class="col-oo fw-bold" style="color:var(--navy); white-space:nowrap;">
                                {{ $order->office_order_no }}
                            </td>

                            <td class="col-employee">
                                <span class="sd-name-label">
                                    {{ optional($emp)->last_name }},
                                    {{ optional($emp)->first_name }}
                                    @if (optional($emp)->middle_name)
                                        {{ substr($emp->middle_name, 0, 1) }}.
                                    @endif
                                </span>
                                <span class="sd-name-sub">{{ $order->destination }}</span>
                            </td>

                            <td class="col-purpose" style="max-width:200px; font-size:0.83rem;">
                                {{ Str::limit($order->purpose, 60) }}
                            </td>

                            <td class="col-destination" style="font-size:0.83rem;">
                                {{ $order->destination }}
                            </td>

                            <td class="col-type">
                                <span style="font-size:0.72rem; font-weight:700;
                                             padding:3px 10px; border-radius:12px;
                                             {{ $typeStyle }}">
                                    {{ $typeLabel }}
                                </span>
                            </td>

                            <td class="col-dates text-muted" style="font-size:0.82rem; white-space:nowrap;">
                                {{ $order->travel_date_start->format('M d, Y') }}
                                –
                                {{ $order->travel_date_end->format('M d, Y') }}
                            </td>

                            <td class="col-status">
                                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>

                            <td class="col-actions">
                                <div class="d-flex gap-2" style="justify-content:center;">
                                    <a href="{{ route('tev.office-orders.show', $order->id) }}"
                                       class="btn btn-outline btn-sm"
                                       onclick="event.stopPropagation();">View</a>

                                    @if ($order->status === 'draft' && auth()->user()->hasAnyRole(['ard', 'chief_admin_officer']))
                                        <form method="POST"
                                              action="{{ route('tev.office-orders.approve', $order->id) }}"
                                              onsubmit="event.stopPropagation(); return confirm('Approve this Office Order?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                    onclick="event.stopPropagation();">
                                                ✓ Approve
                                            </button>
                                        </form>
                                    @endif

                                    @if ($order->status === 'approved' && auth()->user()->hasAnyRole(['hrmo', 'ard', 'chief_admin_officer']))
                                        <form method="POST"
                                              action="{{ route('tev.office-orders.cancel', $order->id) }}"
                                              onsubmit="event.stopPropagation(); return confirm('Cancel this Office Order? This cannot be undone.')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="event.stopPropagation();">
                                                Cancel
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                {{-- Mobile expand chevron --}}
                                <span class="sd-expand-btn" aria-label="Expand">▼</span>
                            </td>

                        </tr>

                        {{-- ── Expandable detail row (mobile only) ── --}}
                        <tr class="sd-detail-row" id="sd-detail-{{ $order->id }}">
                            <td colspan="8">
                                <div class="sd-detail-grid">
                                    <div class="sd-detail-item">
                                        <label>OO Number</label>
                                        <span style="color:var(--navy); font-weight:700;">{{ $order->office_order_no }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Travel Type</label>
                                        <span>
                                            <span style="font-size:0.72rem; font-weight:700;
                                                         padding:2px 8px; border-radius:10px;
                                                         {{ $typeStyle }}">{{ $typeLabel }}</span>
                                        </span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Destination</label>
                                        <span>{{ $order->destination }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Status</label>
                                        <span><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Travel Start</label>
                                        <span>{{ $order->travel_date_start->format('M d, Y') }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Travel End</label>
                                        <span>{{ $order->travel_date_end->format('M d, Y') }}</span>
                                    </div>
                                    <div class="sd-detail-item full-width">
                                        <label>Purpose</label>
                                        <span>{{ Str::limit($order->purpose, 120) }}</span>
                                    </div>
                                </div>
                                <div class="sd-detail-actions">
                                    <a href="{{ route('tev.office-orders.show', $order->id) }}"
                                       class="btn btn-outline btn-sm">View</a>

                                    @if ($order->status === 'draft' && auth()->user()->hasAnyRole(['ard', 'chief_admin_officer']))
                                        <form method="POST"
                                              action="{{ route('tev.office-orders.approve', $order->id) }}"
                                              style="flex:1;"
                                              onsubmit="return confirm('Approve this Office Order?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                    style="width:100%;">
                                                ✓ Approve
                                            </button>
                                        </form>
                                    @endif

                                    @if ($order->status === 'approved' && auth()->user()->hasAnyRole(['hrmo', 'ard', 'chief_admin_officer']))
                                        <form method="POST"
                                              action="{{ route('tev.office-orders.cancel', $order->id) }}"
                                              style="flex:1;"
                                              onsubmit="return confirm('Cancel this Office Order?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                    style="width:100%;">
                                                Cancel
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding:40px; color:var(--text-light);">
                                No office orders found.
                                @if (auth()->user()->hasAnyRole(['payroll_officer', 'hrmo', 'super_admin']))
                                    <a href="{{ route('tev.office-orders.create') }}">Create one now →</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div style="margin-top:12px;">{{ $orders->links() }}</div>

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