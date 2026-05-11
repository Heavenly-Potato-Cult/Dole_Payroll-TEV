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
   TABS INTERFACE
──────────────────────────────────────────────────── */
.tev-tabs {
    display: flex;
    border-bottom: 2px solid var(--border);
    margin-bottom: 20px;
    background: var(--surface);
    border-radius: 8px 8px 0 0;
}
.tev-tab {
    padding: 12px 24px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-mid);
    transition: all 0.2s ease;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
}
.tev-tab:hover {
    color: var(--text);
    background: rgba(0, 0, 0, 0.02);
}
.tev-tab.active {
    color: var(--navy);
    border-bottom-color: var(--navy);
    background: white;
}
.tev-tab-content {
    display: none;
}
.tev-tab-content.active {
    display: block;
}

/* ─────────────────────────────────────────────────────
   FILTER FORM — buttons match input/select height
──────────────────────────────────────────────────── */
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

{{-- ── Tabs ── --}}
<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="tev-tabs">
            <button class="tev-tab {{ $activeTab === 'process' ? 'active' : '' }}" onclick="switchTab('process')">
                🔄 In Process ({{ $inProcessRequests->total() }})
            </button>
            <button class="tev-tab {{ $activeTab === 'liquidated' ? 'active' : '' }}" onclick="switchTab('liquidated')">
                ✅ Liquidated ({{ $liquidatedRequests->total() }})
            </button>
        </div>
        
        {{-- ── In Process Tab ── --}}
        <div id="process-tab" class="tev-tab-content {{ $activeTab === 'process' ? 'active' : '' }}">
            <div style="padding:20px;">
                {{-- Filter bar for in-process --}}
                <div class="card mb-3">
                    <div class="card-body" style="padding:14px 20px;">
                        <form method="GET" action="{{ route('tev.requests.index') }}" class="filter-form">
                            <input type="hidden" name="tab" value="process">
                            
                            <div class="ff-group" style="min-width:150px;">
                                <label for="process_track">Track</label>
                                <select name="process_track" id="process_track">
                                    <option value="">All Tracks</option>
                                    <option value="cash_advance"  {{ request('process_track') === 'cash_advance'  ? 'selected' : '' }}>Cash Advance</option>
                                    <option value="reimbursement" {{ request('process_track') === 'reimbursement' ? 'selected' : '' }}>Reimbursement</option>
                                </select>
                            </div>
                            
                            <div class="ff-group" style="min-width:180px;">
                                <label for="process_status">Status</label>
                                <select name="process_status" id="process_status">
                                    <option value="">All Status</option>
                                    <option value="submitted" {{ request('process_status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                                    <option value="accountant_certified" {{ request('process_status') === 'accountant_certified' ? 'selected' : '' }}>Accountant Certified</option>
                                    <option value="rd_approved" {{ request('process_status') === 'rd_approved' ? 'selected' : '' }}>RD Approved</option>
                                    <option value="cashier_released" {{ request('process_status') === 'cashier_released' ? 'selected' : '' }}>Cashier Released</option>
                                    <option value="liquidation_filed" {{ request('process_status') === 'liquidation_filed' ? 'selected' : '' }}>Liquidation Filed</option>
                                    <option value="rejected" {{ request('process_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>

                            <div class="ff-btns">
                                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                <a href="{{ route('tev.requests.index', ['tab' => 'process']) }}" class="btn btn-outline btn-sm">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                @include('tev::partials.tev-table', ['tevRequests' => $inProcessRequests, 'pageName' => 'process_page'])
            </div>
        </div>
        
        {{-- ── Liquidated Tab ── --}}
        <div id="liquidated-tab" class="tev-tab-content {{ $activeTab === 'liquidated' ? 'active' : '' }}">
            <div style="padding:20px;">
                {{-- Filter bar for liquidated --}}
                <div class="card mb-3">
                    <div class="card-body" style="padding:14px 20px;">
                        <form method="GET" action="{{ route('tev.requests.index') }}" class="filter-form">
                            <input type="hidden" name="tab" value="liquidated">
                            
                            <div class="ff-group" style="min-width:150px;">
                                <label for="track">Track</label>
                                <select name="track" id="track">
                                    <option value="">All Tracks</option>
                                    <option value="cash_advance"  {{ request('track') === 'cash_advance'  ? 'selected' : '' }}>Cash Advance</option>
                                    <option value="reimbursement" {{ request('track') === 'reimbursement' ? 'selected' : '' }}>Reimbursement</option>
                                </select>
                            </div>

                            <div class="ff-btns">
                                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                <a href="{{ route('tev.requests.index', ['tab' => 'liquidated']) }}" class="btn btn-outline btn-sm">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                @include('tev::partials.tev-table', ['tevRequests' => $liquidatedRequests, 'pageName' => 'liquidated_page'])
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tev-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelector(`.tev-tab[onclick="switchTab('${tabName}')"]`).classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tev-tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Update URL without page reload
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
}

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