{{-- resources/views/payroll/index.blade.php --}}
{{--
    CONTROLLER REQUIREMENT — PayrollController@index must eager-load aggregates:
    $query->withCount('entries')
          ->withSum('entries', 'gross_income')
          ->withSum('entries', 'total_deductions')
          ->withSum('entries', 'net_amount')

    Otherwise $batch->entries_count, ->entries_sum_gross_income, etc. will be null
    and the totals columns will show ₱0.00 for every row.
--}}

@extends('layouts.app')

@section('title', 'Regular Payroll')
@section('page-title', 'Regular Payroll')

@section('styles')
<style>
/* ─────────────────────────────────────────────────────
   TABS
───────────────────────────────────────────────────── */
.pr-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 16px;
    border-bottom: 2px solid var(--border);
}
.pr-tab-btn {
    padding: 10px 20px;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-mid);
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: -2px;
}
.pr-tab-btn:hover {
    color: var(--text);
    background: var(--bg, #f8f9fb);
}
.pr-tab-btn.active {
    color: var(--navy, #1e3a5f);
    border-bottom-color: var(--navy, #1e3a5f);
}
.pr-tab-content {
    display: none !important;
}
.pr-tab-content.active,
div#tab-locked.active,
div#tab-active.active {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

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
.filter-form .ff-btns .btn,
.filter-form .ff-btns .btn-sm {
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
   RESPONSIVE TABLE
───────────────────────────────────────────────────── */

/* Detail rows + expand button: always hidden unless mobile overrides */
.pr-detail-row { display: none !important; }
.pr-expand-btn { display: none !important; }

/* ── DESKTOP (≥ 769px): pure normal table ── */
@media (min-width: 769px) {
    .pr-table              { display: table; width: 100%; border-collapse: collapse; }
    .pr-table thead        { display: table-header-group; }
    .pr-table tbody        { display: table-row-group; }
    .pr-table tr           { display: table-row; }
    .pr-table th,
    .pr-table td           { display: table-cell; }
}

/* ── MOBILE (≤ 768px): card rows ── */
@media (max-width: 768px) {

    /* Filter: stack vertically */
    .filter-form              { flex-direction: column; align-items: stretch; }
    .filter-form .ff-group,
    .filter-form .ff-btns     { width: 100%; }
    .filter-form .ff-btns     { height: auto; }
    .filter-form .ff-btns .btn,
    .filter-form .ff-btns .btn-sm { flex: 1; }

    .table-wrap { overflow: visible; }

    .pr-table        { display: block; }
    .pr-table thead  { display: none; }
    .pr-table tbody  { display: block; }

    /* Card row */
    .pr-table tr.pr-main-row {
        display: flex;
        align-items: center;
        gap: 0;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        cursor: pointer;
        transition: background .15s;
        min-height: 64px;
    }
    .pr-table tr.pr-main-row:active { background: var(--bg); }

    /* Hide columns moved to detail panel */
    .pr-table tr.pr-main-row td.col-employees,
    .pr-table tr.pr-main-row td.col-gross,
    .pr-table tr.pr-main-row td.col-deductions,
    .pr-table tr.pr-main-row td.col-netpay,
    .pr-table tr.pr-main-row td.col-creator,
    .pr-table tr.pr-main-row td.col-actions {
        display: none;
    }

    /* Period — takes all space */
    .pr-table tr.pr-main-row td.col-period {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
        padding: 0;
        min-width: 0;
    }
    .pr-table tr.pr-main-row td.col-period .pr-period-label {
        font-weight: 700;
        font-size: 0.92rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pr-table tr.pr-main-row td.col-period .pr-period-sub {
        font-size: 0.74rem;
        color: var(--text-mid);
    }

    /* Status badge */
    .pr-table tr.pr-main-row td.col-status {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        padding: 0 10px;
    }

    /* Expand button */
    .pr-expand-btn {
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
        margin-left: 6px;
    }
    .pr-main-row.open .pr-expand-btn {
        transform: rotate(180deg);
        background: var(--navy-light, #e8ecf4);
        border-color: var(--navy);
        color: var(--navy);
    }

    /* ── Expanded detail panel ── */
    tr.pr-detail-row.open {
        display: block !important;
        border-bottom: 1px solid var(--border);
        background: var(--bg, #f8f9fb);
    }
    tr.pr-detail-row.open td {
        display: block;
        padding: 12px 16px 16px;
    }
    .pr-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 20px;
        margin-bottom: 14px;
    }
    .pr-detail-item label {
        display: block;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-light);
        margin-bottom: 3px;
    }
    .pr-detail-item span {
        font-size: 0.85rem;
        color: var(--text);
        font-weight: 500;
    }
    .pr-detail-item span.mono {
        font-family: monospace;
    }
    .pr-detail-actions {
        display: flex;
        gap: 8px;
    }
    .pr-detail-actions .btn,
    .pr-detail-actions button {
        flex: 1;
        justify-content: center;
        text-align: center;
    }
}
</style>
@endsection

@section('content')

@php
    $activeBatches = $batches->filter(fn($b) => $b->status !== 'locked');
    // $lockedBatches is now passed from controller separately
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Regular Payroll Batches</h1>
        <p>Monthly payroll for all DOLE RO9 regular employees.</p>
    </div>
@canCreatePayroll
<a href="{{ route('payroll.create') }}" class="btn btn-primary">
    + New Payroll Batch
</a>
@endcanCreatePayroll
</div>

{{-- ── Filter bar ──────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" action="{{ route('payroll.index') }}" class="filter-form">

            <div class="ff-group" style="min-width:120px;">
                <label for="year">Year</label>
                <select name="year" id="year">
                    <option value="">All Years</option>
                    @foreach (range(now()->year, 2020) as $y)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="ff-group" style="min-width:140px;">
                <label for="month">Month</label>
                <select name="month" id="month">
                    <option value="">All Months</option>
                    @foreach (['January','February','March','April','May','June',
                               'July','August','September','October','November','December']
                              as $i => $m)
                        <option value="{{ $i + 1 }}" {{ request('month') == $i + 1 ? 'selected' : '' }}>
                            {{ $m }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="ff-group" style="min-width:180px;">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">All Statuses</option>
                    <option value="draft"               {{ request('status') === 'draft'               ? 'selected' : '' }}>Draft</option>
                    <option value="computed"            {{ request('status') === 'computed'            ? 'selected' : '' }}>Computed</option>
                    <option value="pending_accountant"  {{ request('status') === 'pending_accountant'  ? 'selected' : '' }}>Pending Accountant</option>
                    <option value="pending_rd"          {{ request('status') === 'pending_rd'          ? 'selected' : '' }}>Pending RD/ARD</option>
                    <option value="released"            {{ request('status') === 'released'            ? 'selected' : '' }}>Released</option>
                    <option value="locked"              {{ request('status') === 'locked'              ? 'selected' : '' }}>Locked</option>
                </select>
            </div>

            <div class="ff-btns">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('payroll.index') }}" class="btn btn-outline btn-sm">Reset</a>
            </div>

        </form>
    </div>
</div>

{{-- ── Tab Navigation ──────────────────────────────────────────── --}}
<div class="pr-tabs">
    <button class="pr-tab-btn active" data-tab="active" onclick="switchTab('active')">
        Active Batches ({{ $activeBatches->count() }})
    </button>
    <button class="pr-tab-btn" data-tab="locked" onclick="switchTab('locked')">
        Locked Batches ({{ $lockedBatches->count() }})
    </button>
</div>

{{-- ── Active Batches Table ─────────────────────────────────────── --}}
<div class="pr-tab-content active" id="tab-active">
    <div class="card">
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
                <table class="pr-table">
                    <thead>
                        <tr>
                            <th>Period</th>

                            <th>Status</th>
                            <th class="text-right">Employees</th>
                            <th class="text-right">Total Gross</th>
                            <th class="text-right">Total Deductions</th>
                            <th class="text-right">Total Net Pay</th>
                            <th>Remarks</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activeBatches as $batch)
                        @php
                            $months = [
                                '', 'January', 'February', 'March', 'April', 'May', 'June',
                                'July', 'August', 'September', 'October', 'November', 'December',
                            ];
                            $periodLabel = ($months[$batch->period_month] ?? '?')
                                . ' ' . \Carbon\Carbon::parse($batch->period_start)->format('j')
                                . '–' . \Carbon\Carbon::parse($batch->period_end)->format('j')
                                . ', ' . $batch->period_year;

                            $entryCount = $batch->entries_count ?? 0;
                            $totalGross = $batch->entries_sum_gross_income ?? 0;
                            $totalDeds  = $batch->entries_sum_total_deductions ?? 0;
                            $totalNet   = $batch->entries_sum_net_amount ?? 0;

                            $statusClass = match ($batch->status) {
                                'draft'              => 'badge-draft',
                                'computed'           => 'badge-computed',
                                'pending_accountant',
                                'pending_rd'         => 'badge-pending',
                                'released'           => 'badge-released',
                                'locked'             => 'badge-locked',
                                default              => 'badge-draft',
                            };

                            $statusLabels = [
                                'draft'               => 'Draft',
                                'computed'            => 'Computed',
                                'pending_accountant'  => 'Pending Accountant',
                                'pending_rd'          => 'Pending RD / ARD',
                                'released'            => 'Released',
                                'locked'              => 'Locked',
                            ];
                            $statusLabel = $statusLabels[$batch->status] ?? ucfirst(str_replace('_', ' ', $batch->status));
                        @endphp

                        {{-- ── Main visible row ── --}}
                        <tr class="pr-main-row" data-id="{{ $batch->id }}" onclick="togglePrRow(this)">
                            <td class="col-period">
                                <span class="pr-period-label">{{ $periodLabel }}</span>
                                <span class="pr-period-sub">Monthly · {{ $batch->period_year }}</span>
                            </td>
                            {{-- col-cutoff removed — no cutoff in monthly model --}}
                            <td class="col-status">
                                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="col-employees text-right">{{ $entryCount }}</td>
                            <td class="col-gross text-right">
                                {{ $entryCount > 0 ? '₱' . number_format($totalGross, 2) : '—' }}
                            </td>
                            <td class="col-deductions text-right">
                                {{ $entryCount > 0 ? '₱' . number_format($totalDeds, 2) : '—' }}
                            </td>
                            <td class="col-netpay text-right fw-bold">
                                {{ $entryCount > 0 ? '₱' . number_format($totalNet, 2) : '—' }}
                            </td>
                            <td class="col-remarks text-muted" style="font-size:0.80rem; max-width:260px;">
                                {{ $batch->remarks ?: '—' }}
                            </td>
                            <td class="col-creator text-muted" style="font-size:0.82rem;">
                                {{ $batch->creator->name ?? '—' }}<br>
                                <span style="font-size:0.75rem;">
                                    {{ $batch->created_at->format('M d, Y') }}
                                </span>
                            </td>
                            <td class="col-actions">
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="{{ route('payroll.show', $batch) }}"
                                       class="btn btn-outline btn-sm"
                                       onclick="event.stopPropagation();">View</a>
                                    @role('payroll_officer|super_admin')
                                        @if (in_array($batch->status, ['draft', 'computed', 'pending_accountant']))
                                            <form id="deleteForm-{{ $batch->id }}" method="POST"
                                                  action="{{ route('payroll.destroy', $batch) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-sm"
                                                        onclick="event.stopPropagation(); confirmDeletePayroll({{ $batch->id }}, '{{ $batch->period_year }}-{{ str_pad($batch->period_month, 2, '0', STR_PAD_LEFT) }}')">Delete</button>
                                            </form>
                                        @endif
                                    @endrole
                                </div>
                                <span class="pr-expand-btn" aria-label="Expand">▼</span>
                            </td>
                        </tr>

                        {{-- ── Expandable detail row (mobile only) ── --}}
                        <tr class="pr-detail-row" id="pr-detail-{{ $batch->id }}">
                            <td colspan="10">
                                <div class="pr-detail-grid">
                                    <div class="pr-detail-item">
                                        <label>Employees</label>
                                        <span>{{ $entryCount }}</span>
                                    </div>
                                    <div class="pr-detail-item">
                                        <label>Total Gross</label>
                                        <span class="mono">{{ $entryCount > 0 ? '₱' . number_format($totalGross, 2) : '—' }}</span>
                                    </div>
                                    <div class="pr-detail-item">
                                        <label>Total Deductions</label>
                                        <span class="mono">{{ $entryCount > 0 ? '₱' . number_format($totalDeds, 2) : '—' }}</span>
                                    </div>
                                    <div class="pr-detail-item">
                                        <label>Total Net Pay</label>
                                        <span class="mono" style="font-weight:700;">{{ $entryCount > 0 ? '₱' . number_format($totalNet, 2) : '—' }}</span>
                                    </div>
                                    <div class="pr-detail-item">
                                        <label>Created By</label>
                                        <span>{{ $batch->creator->name ?? '—' }}<br>
                                            <small style="color:var(--text-light);">{{ $batch->created_at->format('M d, Y') }}</small>
                                        </span>
                                    </div>
                                    <div class="pr-detail-item" style="grid-column: 1 / -1;">
                                        <label>Remarks</label>
                                        <span>{{ $batch->remarks ?: '—' }}</span>
                                    </div>
                                </div>
                                <div class="pr-detail-actions">
                                    <a href="{{ route('payroll.show', $batch) }}"
                                       class="btn btn-outline btn-sm">View</a>
                                    @canCreatePayroll
                                        @if (in_array($batch->status, ['draft', 'computed', 'pending_accountant']))
                                            <button type="button" class="btn btn-danger btn-sm" style="width:100%;" onclick="confirmDeletePayroll({{ $batch->id }}, '{{ $batch->period_year }}-{{ str_pad($batch->period_month, 2, '0', STR_PAD_LEFT) }}')">Delete</button>
                                        @endif
                                    @endcanCreatePayroll
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center; padding:40px; color:var(--text-light);">
                                No active payroll batches found.
                                @canCreatePayroll
                                    <a href="{{ route('payroll.create') }}">Create one now →</a>
                                @endcanCreatePayroll
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

{{-- ── Locked Batches Table ─────────────────────────────────────── --}}
<!-- DEBUG: tab-locked starts here -->
<div class="pr-tab-content" id="tab-locked">
    <div class="card">
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
                <table class="pr-table">
                    <thead>
                        <tr>
                            <th>Period</th>

                            <th>Status</th>
                            <th class="text-right">Employees</th>
                            <th class="text-right">Total Gross</th>
                            <th class="text-right">Total Deductions</th>
                            <th class="text-right">Total Net Pay</th>
                            <th>Remarks</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lockedBatches as $batch)
                        @php
                            $months = [
                                '', 'January', 'February', 'March', 'April', 'May', 'June',
                                'July', 'August', 'September', 'October', 'November', 'December',
                            ];
                            $periodLabel = ($months[$batch->period_month] ?? '?')
                                . ' ' . \Carbon\Carbon::parse($batch->period_start)->format('j')
                                . '–' . \Carbon\Carbon::parse($batch->period_end)->format('j')
                                . ', ' . $batch->period_year;

                            $entryCount = $batch->entries_count ?? 0;
                            $totalGross = $batch->entries_sum_gross_income ?? 0;
                            $totalDeds  = $batch->entries_sum_total_deductions ?? 0;
                            $totalNet   = $batch->entries_sum_net_amount ?? 0;

                            $statusClass = match ($batch->status) {
                                'draft'              => 'badge-draft',
                                'computed'           => 'badge-computed',
                                'pending_accountant',
                                'pending_rd'         => 'badge-pending',
                                'released'           => 'badge-released',
                                'locked'             => 'badge-locked',
                                default              => 'badge-draft',
                            };

                            $statusLabels = [
                                'draft'               => 'Draft',
                                'computed'            => 'Computed',
                                'pending_accountant'  => 'Pending Accountant',
                                'pending_rd'          => 'Pending RD / ARD',
                                'released'            => 'Released',
                                'locked'              => 'Locked',
                            ];
                            $statusLabel = $statusLabels[$batch->status] ?? ucfirst(str_replace('_', ' ', $batch->status));
                        @endphp

                        {{-- ── Main visible row ── --}}
                        <tr class="pr-main-row" data-id="{{ $batch->id }}" onclick="togglePrRow(this)">

                            <td class="col-period">
                                <span class="pr-period-label">{{ $periodLabel }}</span>
                                <span class="pr-period-sub">Monthly · {{ $batch->period_year }}</span>
                            </td>

                            {{-- col-cutoff removed — no cutoff in monthly model --}}

                            <td class="col-status">
                                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>

                            <td class="col-employees text-right">{{ $entryCount }}</td>

                            <td class="col-gross text-right">
                                {{ $entryCount > 0 ? '₱' . number_format($totalGross, 2) : '—' }}
                            </td>

                            <td class="col-deductions text-right">
                                {{ $entryCount > 0 ? '₱' . number_format($totalDeds, 2) : '—' }}
                            </td>

                            <td class="col-netpay text-right fw-bold">
                                {{ $entryCount > 0 ? '₱' . number_format($totalNet, 2) : '—' }}
                            </td>

                            <td class="col-remarks text-muted" style="font-size:0.80rem; max-width:260px;">
                                {{ $batch->remarks ?: '—' }}
                            </td>

                            <td class="col-creator text-muted" style="font-size:0.82rem;">
                                {{ $batch->creator->name ?? '—' }}<br>
                                <span style="font-size:0.75rem;">
                                    {{ $batch->created_at->format('M d, Y') }}
                                </span>
                            </td>

                            <td class="col-actions">
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="{{ route('payroll.show', $batch) }}"
                                       class="btn btn-outline btn-sm"
                                       onclick="event.stopPropagation();">View</a>
                                </div>

                                {{-- Mobile expand chevron --}}
                                <span class="pr-expand-btn" aria-label="Expand">▼</span>
                            </td>

                        </tr>

                        {{-- ── Expandable detail row (mobile only) ── --}}
                        <tr class="pr-detail-row" id="pr-detail-{{ $batch->id }}">
                            <td colspan="10">
                                <div class="pr-detail-grid">
                                    <div class="pr-detail-item">
                                        <label>Employees</label>
                                        <span>{{ $entryCount }}</span>
                                    </div>
                                    <div class="pr-detail-item">
                                        <label>Total Gross</label>
                                        <span class="mono">{{ $entryCount > 0 ? '₱' . number_format($totalGross, 2) : '—' }}</span>
                                    </div>
                                    <div class="pr-detail-item">
                                        <label>Total Deductions</label>
                                        <span class="mono">{{ $entryCount > 0 ? '₱' . number_format($totalDeds, 2) : '—' }}</span>
                                    </div>
                                    <div class="pr-detail-item">
                                        <label>Total Net Pay</label>
                                        <span class="mono" style="font-weight:700;">{{ $entryCount > 0 ? '₱' . number_format($totalNet, 2) : '—' }}</span>
                                    </div>
                                    <div class="pr-detail-item">
                                        <label>Created By</label>
                                        <span>{{ $batch->creator->name ?? '—' }}<br>
                                            <small style="color:var(--text-light);">{{ $batch->created_at->format('M d, Y') }}</small>
                                        </span>
                                    </div>
                                    <div class="pr-detail-item" style="grid-column: 1 / -1;">
                                        <label>Remarks</label>
                                        <span>{{ $batch->remarks ?: '—' }}</span>
                                    </div>
                                </div>
                                <div class="pr-detail-actions">
                                    <a href="{{ route('payroll.show', $batch) }}"
                                       class="btn btn-outline btn-sm">View</a>
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center; padding:40px; color:var(--text-light);">
                                No locked payroll batches found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div style="margin-top:12px;">{{ $batches->links() }}</div>

@endsection

@section('scripts')
<script>
function switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.pr-tab-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.tab === tabName) {
            btn.classList.add('active');
        }
    });

    // Update tab content
    document.querySelectorAll('.pr-tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById('tab-' + tabName).classList.add('active');
}

function togglePrRow(mainRow) {
    if (window.innerWidth > 768) return;

    const id     = mainRow.dataset.id;
    const detail = document.getElementById('pr-detail-' + id);
    const isOpen = mainRow.classList.contains('open');

    document.querySelectorAll('.pr-main-row.open').forEach(r => r.classList.remove('open'));
    document.querySelectorAll('.pr-detail-row.open').forEach(r => r.classList.remove('open'));

    if (!isOpen) {
        mainRow.classList.add('open');
        detail.classList.add('open');
    }
}

function confirmDeletePayroll(batchId, periodLabel) {
    Swal.fire({
        title: 'Delete Payroll Batch?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.2rem;font-weight:600;color:#dc3545;margin-bottom:8px;">${periodLabel}</div>
            <p style="color:#6b7280;font-size:0.95rem;">This will permanently delete this draft payroll batch and cannot be undone.</p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete Batch',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit form and disable buttons to prevent double submission
            const form = document.getElementById('deleteForm-' + batchId);
            if (form) {
                // Disable all delete buttons for this batch
                const buttons = form.querySelectorAll('button');
                buttons.forEach(btn => {
                    btn.disabled = true;
                    btn.textContent = 'Deleting...';
                });
                form.submit();
            }
        }
    });
}
</script>
@endsection
