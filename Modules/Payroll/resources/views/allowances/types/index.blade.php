@extends('layouts.app')

@section('title', 'Allowance Types')
@section('page-title', 'Allowance Types')

@section('styles')
<style>
/* ════════════════════════════════════════════════════════════════
   ALLOWANCE TYPES — Dashboard-matching styles
   ════════════════════════════════════════════════════════════════ */

/* ── Greeting ─────────────────────────────────────────────────── */
.db-greeting {
    margin-bottom: 20px;
    padding: 20px;
    background: linear-gradient(135deg, var(--navy) 0%, #1a2d6d 100%);
    border-radius: var(--radius);
    color: #fff;
    position: relative;
    overflow: hidden;
}
.db-greeting::after {
    content: '';
    position: absolute;
    right: -30px; top: -30px;
    width: 140px; height: 140px;
    background: rgba(249,168,37,0.12);
    border-radius: 50%;
    pointer-events: none;
}
.db-greeting-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 12px;
    position: relative;
    z-index: 1;
}
.db-greeting h1 {
    font-size: clamp(1.1rem, 3vw, 1.4rem);
    margin: 0;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
}
.db-greeting-location {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.65);
    margin-top: 4px;
}

/* ── Stat Grid ────────────────────────────────────────────────── */
.db-stat-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin-bottom: 18px;
}
@media (min-width: 768px) { .db-stat-grid { grid-template-columns: repeat(3, 1fr); } }

.db-stat {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    min-width: 0;
    display: flex;
    align-items: stretch;
    gap: 0;
}
.db-stat-left {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 90px;
    padding-right: 12px;
}
.db-stat-divider {
    width: 0.5px;
    background: #e2e8f0;
    flex-shrink: 0;
}
.db-stat-right {
    display: flex;
    align-items: center;
    justify-content: center;
    padding-left: 12px;
    min-width: 70px;
}
.db-stat-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 4px;
}
.db-stat-subtitle {
    font-size: 13px;
    color: #94a3b8;
}
.db-stat-value {
    font-size: 56px;
    font-weight: 600;
    letter-spacing: -3px;
    line-height: 1;
    color: #534AB7;
}

/* ── Search & Filter Bar ─────────────────────────────────────── */
.dt-search-filter {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 18px;
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.dt-search-input {
    flex: 1;
    min-width: 200px;
    height: 38px;
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 0.875rem;
    background: var(--surface);
    color: var(--text);
    font-family: var(--font);
}
.dt-search-input:focus {
    outline: none;
    border-color: var(--navy);
    box-shadow: 0 0 0 3px rgba(15,27,76,0.1);
}
.dt-filter-select {
    height: 38px;
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 0.875rem;
    background: var(--surface);
    color: var(--text);
    min-width: 140px;
    font-family: var(--font);
}
.dt-filter-select:focus {
    outline: none;
    border-color: var(--navy);
}
.dt-no-results {
    text-align: center;
    padding: 48px 20px;
    color: var(--text-light);
    font-size: 0.95rem;
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

/* ── Table card ───────────────────────────────────────────────── */
.at-card {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 20px;
}

/* ── Table ────────────────────────────────────────────────────── */
.dt-table {
    width: 100%;
    border-collapse: collapse;
    font-family: var(--font);
}
.dt-table th {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--text-mid);
    padding: 12px 16px;
    text-align: left;
    background: var(--bg);
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
    font-family: var(--font);
}
.dt-table td {
    padding: 14px 16px;
    border-bottom: 0.5px solid #e2e8f0;
    vertical-align: middle;
    font-size: 0.875rem;
    transition: background-color 0.15s;
    font-family: var(--font);
}
.dt-table tr:last-child td {
    border-bottom: none;
}
.dt-table tr:hover td {
    background: var(--bg);
}

/* Inactive row */
.dt-table tr.dt-inactive td {
    opacity: 0.5;
}
.dt-table tr.dt-inactive:hover td {
    opacity: 0.7;
}

/* ── Badges ───────────────────────────────────────────────────── */
.badge-active {
    background: var(--success-bg);
    color: var(--success);
    font-size: 0.68rem;
    padding: 3px 10px;
    border-radius: 99px;
    font-weight: 700;
    font-family: var(--font);
}
.badge-inactive {
    background: var(--red-light);
    color: var(--red);
    font-size: 0.68rem;
    padding: 3px 10px;
    border-radius: 99px;
    font-weight: 700;
    font-family: var(--font);
}
.badge-taxable {
    background: #fef3c7;
    color: #92400e;
    font-size: 0.63rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    padding: 3px 8px;
    border-radius: 99px;
    border: 1px solid #fbbf24;
    white-space: nowrap;
    font-family: var(--font);
}
.badge-nontaxable {
    background: var(--bg);
    color: var(--text-mid);
    font-size: 0.63rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    padding: 3px 8px;
    border-radius: 99px;
    border: 0.5px solid #e2e8f0;
    white-space: nowrap;
    font-family: var(--font);
}

/* ── Code chip ────────────────────────────────────────────────── */
.code-chip {
    font-family: monospace;
    font-size: 0.78rem;
    background: var(--bg);
    border: 0.5px solid #e2e8f0;
    padding: 4px 8px;
    border-radius: var(--radius);
    color: var(--navy);
    white-space: nowrap;
    font-weight: 600;
}

/* ── Order number ─────────────────────────────────────────────── */
.dt-order {
    display: inline-block;
    min-width: 28px;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-light);
    background: var(--bg);
    border: 0.5px solid #e2e8f0;
    border-radius: var(--radius);
    padding: 2px 6px;
}

/* ── Deductibility flags ──────────────────────────────────────── */
.at-flags {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}
.at-flag {
    font-size: 0.60rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: 2px 6px;
    border-radius: 99px;
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
    white-space: nowrap;
    font-family: var(--font);
}

/* ── Action buttons ───────────────────────────────────────────── */
.dt-actions {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: flex-end;
}
.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: var(--radius);
    border: 0.5px solid #e2e8f0;
    background: #fff;
    cursor: pointer;
    font-size: 0.85rem;
    color: var(--text-mid);
    transition: all 0.15s;
    text-decoration: none;
}
.btn-icon:hover {
    background: var(--navy);
    color: #fff;
    border-color: var(--navy);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.btn-icon.danger:hover {
    background: var(--red);
    border-color: var(--red);
    color: #fff;
}
.btn-icon.btn-delete {
    color: var(--red);
    border-color: #fca5a5;
    background: #fff5f5;
}
.btn-icon.btn-delete:hover {
    background: var(--red);
    border-color: var(--red);
    color: #fff;
}

/* ── Enrolled count pill ──────────────────────────────────────── */
.at-enroll-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    padding: 3px 10px;
    background: var(--navy-light);
    color: var(--navy);
    border: 1px solid var(--navy);
    border-radius: 99px;
    font-size: 0.72rem;
    font-weight: 700;
    font-family: var(--font);
}

/* ── Responsive ───────────────────────────────────────────────── */
@media (max-width: 768px) {
    .dt-search-filter { flex-direction: column; align-items: stretch; }
    .dt-search-input  { min-width: auto; }
    .dt-table th.dt-col-flags,
    .dt-table td.dt-col-flags { display: none; }
}
</style>
@endsection

@section('content')

<div class="page-content">

    {{-- ── Greeting ─────────────────────────────────────────────── --}}
    <div class="db-greeting">
        <div class="db-greeting-header">
            <div>
                <h1>Allowance Types</h1>
                <p class="db-greeting-location">Define allowance line items used in employee enrollments, batches, and payslips.</p>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <a href="{{ route('payroll.allowances.index') }}" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,0.4);">☰ Allowance Batches</a>
                <a href="{{ route('payroll.allowances.types.create') }}" class="btn btn-primary">+ New Type</a>
            </div>
        </div>
    </div>

    {{-- ── Summary Stats ────────────────────────────────────────── --}}
    @php
        $totalCount    = $types->count();
        $activeCount   = $types->where('is_active', true)->count();
        $inactiveCount = $types->where('is_active', false)->count();
    @endphp

    <div class="db-stat-grid">
        <div class="db-stat">
            <div class="db-stat-left">
                <div class="db-stat-title">Total Types</div>
                <div class="db-stat-subtitle">All allowance types</div>
            </div>
            <div class="db-stat-divider"></div>
            <div class="db-stat-right">
                <div class="db-stat-value">{{ $totalCount }}</div>
            </div>
        </div>
        <div class="db-stat">
            <div class="db-stat-left">
                <div class="db-stat-title">Active</div>
                <div class="db-stat-subtitle">Currently enabled</div>
            </div>
            <div class="db-stat-divider"></div>
            <div class="db-stat-right">
                <div class="db-stat-value" style="color:var(--success);">{{ $activeCount }}</div>
            </div>
        </div>
        <div class="db-stat">
            <div class="db-stat-left">
                <div class="db-stat-title">Inactive</div>
                <div class="db-stat-subtitle">Disabled types</div>
            </div>
            <div class="db-stat-divider"></div>
            <div class="db-stat-right">
                <div class="db-stat-value" style="color:var(--red);">{{ $inactiveCount }}</div>
            </div>
        </div>
    </div>

    {{-- ── Search & Filter ─────────────────────────────────────── --}}
    <div class="dt-search-filter">
        <input type="text" class="dt-search-input" id="searchInput" placeholder="Search by Code or Name…">
        <select class="dt-filter-select" id="statusFilter">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    {{-- ── Types Table ─────────────────────────────────────────── --}}
    @if ($types->isEmpty())
        <div class="at-card">
            <div style="text-align:center;padding:48px;color:var(--text-light);">
                <div style="font-size:2rem;margin-bottom:12px;">📋</div>
                <p>No allowance types yet. <a href="{{ route('payroll.allowances.types.create') }}">Create the first one</a> or run the seeder.</p>
            </div>
        </div>
    @else
    <div class="at-card" id="typesContainer">
        <table class="dt-table">
            <thead>
                <tr>
                    <th style="width:42px;">#</th>
                    <th style="width:130px;">Code</th>
                    <th>Name</th>
                    <th style="width:100px;text-align:center;">Enrolled</th>
                    <th style="width:80px;">Status</th>
                    <th style="width:110px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($types as $type)
                <tr class="{{ $type->is_active ? '' : 'dt-inactive' }}"
                    data-code="{{ strtolower($type->code) }}"
                    data-name="{{ strtolower($type->name) }}"
                    data-status="{{ $type->is_active ? 'active' : 'inactive' }}">

                    {{-- # --}}
                    <td><span class="dt-order">{{ $type->display_order }}</span></td>

                    {{-- Code --}}
                    <td><span class="code-chip">{{ $type->code }}</span></td>

                    {{-- Name --}}
                    <td>
                        <span style="font-weight:600;color:var(--navy);">{{ $type->name }}</span>
                        @if ($type->description)
                            <div style="font-size:0.75rem;color:var(--text-light);margin-top:2px;">{{ $type->description }}</div>
                        @endif
                    </td>

                    {{-- Enrolled employees --}}
                    <td style="text-align:center;">
                        @php $count = $type->active_enrollments_count ?? 0; @endphp
                        @if ($count > 0)
                            <span class="at-enroll-count">{{ $count }}</span>
                        @else
                            <span style="color:var(--text-light);font-size:0.78rem;">—</span>
                        @endif
                    </td>

                    {{-- Status --}}
                    <td>
                        @if ($type->is_active)
                            <span class="badge-active">Active</span>
                        @else
                            <span class="badge-inactive">Inactive</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td>
                        <div class="dt-actions">
                            <a href="{{ route('payroll.allowances.types.edit', $type) }}"
                               class="btn-icon" title="Edit">✎</a>

                            <form id="toggleForm-{{ $type->id }}" method="POST"
                                  action="{{ route('payroll.allowances.types.toggle', $type) }}"
                                  style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <button type="button"
                                        class="btn-icon {{ $type->is_active ? 'danger' : '' }}"
                                        title="{{ $type->is_active ? 'Deactivate' : 'Activate' }}"
                                        onclick="confirmToggleAllowanceType({{ $type->id }}, '{{ addslashes($type->name) }}', {{ $type->is_active ? 'true' : 'false' }})">
                                    {{ $type->is_active ? '⊘' : '✓' }}
                                </button>
                            </form>

                            @if (! $type->is_active)
                            <form id="deleteForm-{{ $type->id }}" method="POST"
                                  action="{{ route('payroll.allowances.types.destroy', $type) }}"
                                  style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="button"
                                        class="btn-icon btn-delete"
                                        title="Delete permanently"
                                        onclick="confirmDeleteAllowanceType({{ $type->id }}, '{{ addslashes($type->name) }}')">
                                    🗑
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div id="noResults" class="dt-no-results" style="display:none;">
        <div style="font-size:2rem;margin-bottom:12px;">🔍</div>
        <p>No results found. Try adjusting your search or filters.</p>
    </div>
    @endif

</div>{{-- /.page-content --}}

@endsection

@section('scripts')
<script>
// ── Search & filter ──────────────────────────────────────────────────────
function setupSearchAndFilter() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter= document.getElementById('statusFilter');
    const container   = document.getElementById('typesContainer');
    const noResults   = document.getElementById('noResults');
    if (!searchInput) return;

    function applyFilters() {
        const search = searchInput.value.toLowerCase().trim();
        const status = statusFilter.value;
        let hasVisible = false;

        document.querySelectorAll('.dt-table tbody tr').forEach(row => {
            const matchSearch = !search || row.dataset.code.includes(search) || row.dataset.name.includes(search);
            const matchStatus = !status || row.dataset.status === status;
            const visible     = matchSearch && matchStatus;
            row.style.display = visible ? '' : 'none';
            if (visible) hasVisible = true;
        });

        const isFiltered = search || status;
        if (container) container.style.display = (!isFiltered || hasVisible) ? '' : 'none';
        if (noResults)  noResults.style.display  = (isFiltered && !hasVisible) ? '' : 'none';
    }

    searchInput.addEventListener('input',  applyFilters);
    statusFilter.addEventListener('change',applyFilters);
}

// ── Toggle confirm ────────────────────────────────────────────────────────
function confirmToggleAllowanceType(typeId, typeName, isActive) {
    const action = isActive ? 'Deactivate' : 'Activate';
    Swal.fire({
        title: action + ' Allowance Type?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.1rem;font-weight:600;color:#0F1B4C;margin-bottom:8px;">${typeName}</div>
            <p style="color:#6b7280;font-size:0.9rem;">Are you sure you want to ${action.toLowerCase()} this allowance type?</p>
            ${isActive ? '<p style="color:#6b7280;font-size:0.85rem;margin-top:6px;">Once deactivated, a delete option will appear.</p>' : ''}
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: action,
        cancelButtonText: 'Cancel',
        confirmButtonColor: isActive ? '#dc3545' : '#10B981',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true,
    }).then(result => {
        if (!result.isConfirmed) return;
        const form = document.getElementById('toggleForm-' + typeId);
        if (form) {
            form.querySelectorAll('button').forEach(b => { b.disabled = true; b.textContent = '…'; });
            form.submit();
        }
    });
}

// ── Delete confirm ────────────────────────────────────────────────────────
function confirmDeleteAllowanceType(typeId, typeName) {
    Swal.fire({
        title: 'Permanently Delete?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.1rem;font-weight:600;color:#0F1B4C;margin-bottom:8px;">${typeName}</div>
            <p style="color:#6b7280;font-size:0.9rem;">This will permanently remove the allowance type. This action <strong>cannot be undone</strong>.</p>
            <p style="color:#6b7280;font-size:0.85rem;margin-top:6px;">Types with employee enrollments cannot be deleted.</p>
        </div>`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: '🗑 Delete Permanently',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true,
    }).then(result => {
        if (!result.isConfirmed) return;
        const form = document.getElementById('deleteForm-' + typeId);
        if (form) {
            form.querySelectorAll('button').forEach(b => { b.disabled = true; b.textContent = '…'; });
            form.submit();
        }
    });
}

// ── Init ─────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    setupSearchAndFilter();
});
</script>
@endsection
