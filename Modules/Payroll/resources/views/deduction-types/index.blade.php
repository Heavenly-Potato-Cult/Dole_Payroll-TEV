@extends('layouts.app')

@section('title', 'Deduction Types')
@section('page-title', 'Deduction Types')

@section('styles')
<style>
/* ════════════════════════════════════════════════════════════════
   DEDUCTION TYPES — Dashboard-matching styles
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
}

.db-greeting-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 12px;
}

.db-greeting h1 {
    font-size: clamp(1.1rem, 3vw, 1.4rem);
    margin: 0;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
}

.db-greeting-body {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.db-greeting-date {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.8);
    font-weight: 500;
}

.db-greeting-location {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.65);
}

/* ── Stat Grid ────────────────────────────────────────────────── */
.db-stat-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin-bottom: 18px;
}
@media (min-width: 480px) { .db-stat-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 768px) { .db-stat-grid { grid-template-columns: repeat(4, 1fr); } }

.db-stat {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
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

/* ── Search & Filter Bar ─────────────────────────────────────────── */
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
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
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
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

/* ── Category Accordion ───────────────────────────────────────────── */
.dt-category {
    margin-bottom: 20px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    background: var(--surface);
    box-shadow: var(--shadow);
}
.dt-category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: var(--bg);
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s;
}
.dt-category-header:hover {
    background: var(--navy-light);
}
.dt-category-title {
    display: flex;
    align-items: center;
    gap: 12px;
}
.dt-category-label {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--navy);
    margin: 0;
    font-family: var(--font);
}
.dt-category-count {
    background: var(--navy);
    color: var(--white);
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
    min-width: 20px;
    text-align: center;
}
.dt-category-toggle {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--text-mid);
    transition: transform 0.2s;
    font-size: 1.2rem;
}
.dt-category-toggle.collapsed {
    transform: rotate(-90deg);
}
.dt-category-content {
    transition: max-height 0.3s ease-out;
    overflow: hidden;
}
.dt-category-content.collapsed {
    max-height: 0;
}

/* ── Expand/Collapse All ─────────────────────────────────────────── */
.dt-expand-controls {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
}
.dt-expand-btn {
    height: 38px;
    padding: 8px 16px;
    font-size: 0.75rem;
    font-weight: 600;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    color: var(--text-mid);
    cursor: pointer;
    transition: all 0.2s;
    font-family: var(--font);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.dt-expand-btn:hover {
    background: var(--navy);
    color: #fff;
    border-color: var(--navy);
}

/* ── Category Accordion ───────────────────────────────────────────── */
.dt-category {
    margin-bottom: 20px;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.dt-category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: var(--bg);
    border-bottom: 0.5px solid #e2e8f0;
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s;
}
.dt-category-header:hover {
    background: var(--navy-light);
}
.dt-category-title {
    display: flex;
    align-items: center;
    gap: 12px;
}
.dt-category-label {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--navy);
    margin: 0;
    font-family: var(--font);
}
.dt-category-count {
    background: var(--navy);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
    min-width: 20px;
    text-align: center;
}
.dt-category-toggle {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--text-mid);
    transition: transform 0.2s;
    font-size: 1.2rem;
}
.dt-category-toggle.collapsed {
    transform: rotate(-90deg);
}
.dt-category-content {
    transition: max-height 0.3s ease-out;
    overflow: hidden;
}
.dt-category-content.collapsed {
    max-height: 0;
}

/* ── Table ───────────────────────────────────────────────────────── */
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

/* ── Badges ──────────────────────────────────────────────────────── */
.badge-computed {
    background: var(--navy-light);
    color: var(--navy);
    font-size: 0.63rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    padding: 3px 8px;
    border-radius: 99px;
    border: 1px solid var(--navy);
    white-space: nowrap;
    font-family: var(--font);
}
.badge-manual {
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

/* ── Code chip ───────────────────────────────────────────────────── */
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

/* ── Order number ────────────────────────────────────────────────── */
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

/* ── Action buttons ──────────────────────────────────────────────── */
.dt-actions {
    display: flex;
    gap: 6px;
    align-items: center;
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
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.btn-icon.danger:hover {
    background: var(--red);
    border-color: var(--red);
    color: #fff;
}

/* ── Notes truncation ────────────────────────────────────────────── */
.dt-notes {
    max-width: 280px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: var(--text-light);
    font-size: 0.78rem;
}

/* ── Legend Footer ────────────────────────────────────────────────── */
.dt-legend {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    margin-top: 32px;
    margin-bottom: 40px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.dt-legend-content {
    display: flex;
    gap: 32px;
    flex-wrap: wrap;
    align-items: center;
    padding: 20px 24px;
}
.dt-legend-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-mid);
    text-transform: uppercase;
    letter-spacing: .07em;
    margin-right: 8px;
    font-family: var(--font);
}
.dt-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.78rem;
    color: var(--text-mid);
    font-family: var(--font);
}

/* ── Alternative: Collapsible Legend ───────────────────────────────── */
.dt-legend-collapsible {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    margin-top: 32px;
    margin-bottom: 40px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    overflow: hidden;
}
.dt-legend-toggle {
    width: 100%;
    padding: 16px 24px;
    background: var(--bg);
    border: none;
    border-bottom: 0.5px solid #e2e8f0;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--navy);
    font-family: var(--font);
    transition: background-color 0.2s;
}
.dt-legend-toggle:hover {
    background: var(--navy-light);
}
.dt-legend-toggle-icon {
    transition: transform 0.2s;
}
.dt-legend-toggle-icon.collapsed {
    transform: rotate(-90deg);
}
.dt-legend-body {
    padding: 20px 24px;
    transition: max-height 0.3s ease-out;
    overflow: hidden;
}
.dt-legend-body.collapsed {
    max-height: 0;
    padding: 0 24px;
}

.badge-locked {
    background: #dcfce7;
    color: #166534;
    font-size: 0.63rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    padding: 3px 8px;
    border-radius: 99px;
    border: 1px solid #16a34a;
    white-space: nowrap;
    font-family: var(--font);
}

/* ── Responsive Design ───────────────────────────────────────────── */
@media (max-width: 768px) {
    .dt-search-filter {
        flex-direction: column;
        align-items: stretch;
    }
    .dt-search-input {
        min-width: auto;
    }
    .dt-legend {
        position: static;
    }
    .dt-notes {
        max-width: 120px;
    }
    .dt-table th.dt-col-notes,
    .dt-table td.dt-col-notes {
        display: none;
    }
    .dt-legend-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
</style>
@endsection

@section('content')

<div class="page-content">
    {{-- Greeting Section --}}
    <div class="db-greeting">
        <div class="db-greeting-header">
            <div>
                <h1>Deduction Types</h1>
                <p class="db-greeting-location">Manage all deduction and loan types used across payroll and employee enrollments.</p>
            </div>
            <div class="dt-header-actions" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <a href="{{ route('deduction-types.create') }}" class="btn btn-primary">+ New Deduction Type</a>
                <a href="{{ route('deduction-categories.index') }}"
                   style="display:inline-flex;align-items:center;gap:6px;height:38px;padding:0 16px;border-radius:var(--radius);border:1.5px solid rgba(255,255,255,0.7);background:rgba(255,255,255,0.12);color:#fff;font-size:0.875rem;font-weight:600;text-decoration:none;transition:all 0.2s;font-family:var(--font);"
                   onmouseover="this.style.background='rgba(255,255,255,0.25)';this.style.borderColor='#fff';"
                   onmouseout="this.style.background='rgba(255,255,255,0.12)';this.style.borderColor='rgba(255,255,255,0.7)';">
                    ⊞ Manage Categories
                </a>
            </div>
        </div>
    </div>

    {{-- Summary Stats Grid --}}
    @php
        $allTypes   = $grouped->flatten();
        $totalCount = $allTypes->count();
        $activeCount    = $allTypes->where('is_active', true)->count();
        $computedCount  = $allTypes->where('is_computed', true)->count();
        $inactiveCount  = $allTypes->where('is_active', false)->count();
    @endphp

    <div class="db-stat-grid">
        <div class="db-stat">
            <div class="db-stat-left">
                <div class="db-stat-title">Total Types</div>
                <div class="db-stat-subtitle">All deduction types</div>
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
                <div class="db-stat-value" style="color: var(--success);">{{ $activeCount }}</div>
            </div>
        </div>

        <div class="db-stat">
            <div class="db-stat-left">
                <div class="db-stat-title">Inactive</div>
                <div class="db-stat-subtitle">Disabled types</div>
            </div>
            <div class="db-stat-divider"></div>
            <div class="db-stat-right">
                <div class="db-stat-value" style="color: var(--red);">{{ $inactiveCount }}</div>
            </div>
        </div>

        <div class="db-stat">
            <div class="db-stat-left">
                <div class="db-stat-title">Auto-Computed</div>
                <div class="db-stat-subtitle">System calculated</div>
            </div>
            <div class="db-stat-divider"></div>
            <div class="db-stat-right">
                <div class="db-stat-value" style="color: #534AB7;">{{ $computedCount }}</div>
            </div>
        </div>
    </div>

{{-- Search & Filter Bar --}}
<div class="dt-search-filter">
    <input type="text" class="dt-search-input" id="searchInput" placeholder="Search by Code or Name...">
    <select class="dt-filter-select" id="typeFilter">
        <option value="">All Types</option>
        <option value="computed">Auto-Computed</option>
        <option value="manual">Manual</option>
    </select>
    <select class="dt-filter-select" id="statusFilter">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
    </select>
</div>

{{-- Expand/Collapse All Controls --}}
<div class="dt-expand-controls">
    <button type="button" class="dt-expand-btn" onclick="toggleAllCategories(true)">Expand All</button>
    <button type="button" class="dt-expand-btn" onclick="toggleAllCategories(false)">Collapse All</button>
</div>

@if ($grouped->isEmpty())
    <div class="card">
        <div class="card-body" style="text-align:center;padding:48px;color:var(--text-light);">
            <div style="font-size:2rem;margin-bottom:12px;">📋</div>
            <p>No deduction types found. <a href="{{ route('deduction-types.create') }}">Create the first one</a> or run the seeder.</p>
        </div>
    </div>
@endif

<div id="categoriesContainer">
@foreach ($categoryLabels as $catKey => $catLabel)
    @if (isset($grouped[$catKey]))
    <div class="dt-category" data-category="{{ $catKey }}">
        <div class="dt-category-header" onclick="toggleCategory('{{ $catKey }}')">
            <div class="dt-category-title">
                <h3 class="dt-category-label">{{ $catLabel }}</h3>
                <span class="dt-category-count">{{ $grouped[$catKey]->count() }}</span>
            </div>
            <button class="dt-category-toggle" aria-label="Toggle category">▼</button>
        </div>
        <div class="dt-category-content" id="category-{{ $catKey }}">
            <table class="dt-table">
                <thead>
                    <tr>
                        <th style="width:42px;">#</th>
                        <th style="width:160px;">Code</th>
                        <th>Name</th>
                        <th style="width:110px;">Type</th>
                        <th style="width:80px;">Status</th>
                        <th class="dt-col-notes">Notes</th>
                        <th style="width:130px;text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grouped[$catKey]->sortBy('display_order') as $type)
                    <tr class="{{ $type->is_active ? '' : 'dt-inactive' }} {{ $type->is_computed ? 'dt-computed' : '' }}"
                        data-code="{{ strtolower($type->code) }}"
                        data-name="{{ strtolower($type->name) }}"
                        data-type="{{ $type->is_computed ? 'computed' : 'manual' }}"
                        data-status="{{ $type->is_active ? 'active' : 'inactive' }}">

                        {{-- Sequential number within this category --}}
                        <td><span class="dt-order">{{ $loop->iteration }}</span></td>

                        {{-- Code (immutable) --}}
                        <td><span class="code-chip">{{ $type->code }}</span></td>

                        {{-- Name --}}
                        <td>
                            <span style="font-weight:600;color:var(--navy);">{{ $type->name }}</span>
                        </td>

{{-- Type badge --}}
<td>
    @if ($type->is_computed)
        <span class="badge-computed">⚙️ Auto-computed</span>
    @elseif ($type->isEffectivelyLocked())
        <span class="badge-locked">🔒 Global Fixed</span>
    @else
        <span class="badge-manual">Manual</span>
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

                        {{-- Notes --}}
                        <td class="dt-col-notes">
                            <span class="dt-notes" title="{{ $type->notes }}">
                                {{ $type->notes ?: '—' }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td>
                            <div class="dt-actions" style="justify-content:flex-end;">
                                {{-- Edit --}}
                                <a href="{{ route('deduction-types.edit', $type) }}"
                                   class="btn-icon" title="Edit">✎</a>

                                {{-- Toggle active --}}
                                <form id="toggleForm-{{ $type->id }}" method="POST"
                                      action="{{ route('deduction-types.toggle', $type) }}"
                                      style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="button"
                                            class="btn-icon {{ $type->is_active ? 'danger' : '' }}"
                                            title="{{ $type->is_active ? 'Deactivate' : 'Activate' }}"
                                            onclick="confirmToggleDeductionType({{ $type->id }}, '{{ $type->name }}', {{ $type->is_active ? 'true' : 'false' }})">
                                        {{ $type->is_active ? '⊘' : '✓' }}
                                    </button>
                                </form>

                                {{-- Delete — only shown for inactive types --}}
                                @if (! $type->is_active)
                                <form id="deleteForm-{{ $type->id }}" method="POST"
                                      action="{{ route('deduction-types.destroy', $type) }}"
                                      style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            class="btn-icon danger"
                                            title="Delete permanently"
                                            onclick="confirmDeleteDeductionType({{ $type->id }}, '{{ addslashes($type->name) }}')">
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
    </div>
    @endif
@endforeach
</div>

<div id="noResults" class="dt-no-results" style="display: none;">
    <div style="font-size:2rem;margin-bottom:12px;">🔍</div>
    <p>No results found. Try adjusting your search or filters.</p>
</div>

{{-- Legend --}}
<div class="dt-legend-collapsible">
    <button class="dt-legend-toggle" onclick="toggleLegend()">
        <span>Legend & Help</span>
        <span class="dt-legend-toggle-icon" id="legendToggleIcon">▼</span>
    </button>
    <div class="dt-legend-body" id="legendBody">
        <div class="dt-legend-content">
            <div class="dt-legend-item">
                <span class="badge-computed">🔒 Auto-computed</span>
                <span>Amount is calculated by the payroll engine (GSIS, PhilHealth, Pag-IBIG, WHT). Cannot be manually enrolled.</span>
            </div>
            <div class="dt-legend-item">
                <span class="badge-manual">Manual</span>
                <span>Amount is set per employee via the Deductions enrollment form.</span>
            </div>
            <div class="dt-legend-item">
                <span class="code-chip">CODE</span>
                <span>Code is permanent and cannot be changed after creation.</span>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Initialize category states (all expanded by default)
const categoryStates = {};
const categories = document.querySelectorAll('.dt-category');
categories.forEach(cat => {
    const catKey = cat.dataset.category;
    categoryStates[catKey] = true; // all expanded by default
});

// Toggle individual category
function toggleCategory(categoryKey) {
    const content = document.getElementById(`category-${categoryKey}`);
    const toggle = document.querySelector(`[data-category="${categoryKey}"] .dt-category-toggle`);

    categoryStates[categoryKey] = !categoryStates[categoryKey];

    if (categoryStates[categoryKey]) {
        content.classList.remove('collapsed');
        toggle.classList.remove('collapsed');
        content.style.maxHeight = content.scrollHeight + 'px';
    } else {
        content.classList.add('collapsed');
        toggle.classList.add('collapsed');
        content.style.maxHeight = '0';
    }
}

// Toggle all categories
function toggleAllCategories(expand) {
    categories.forEach(cat => {
        const catKey = cat.dataset.category;
        const content = document.getElementById(`category-${catKey}`);
        const toggle = cat.querySelector('.dt-category-toggle');

        categoryStates[catKey] = expand;

        if (expand) {
            content.classList.remove('collapsed');
            toggle.classList.remove('collapsed');
            content.style.maxHeight = content.scrollHeight + 'px';
        } else {
            content.classList.add('collapsed');
            toggle.classList.add('collapsed');
            content.style.maxHeight = '0';
        }
    });
}

// Toggle legend
function toggleLegend() {
    const legendBody = document.getElementById('legendBody');
    const toggleIcon = document.getElementById('legendToggleIcon');

    if (legendBody.classList.contains('collapsed')) {
        legendBody.classList.remove('collapsed');
        toggleIcon.classList.remove('collapsed');
        legendBody.style.maxHeight = legendBody.scrollHeight + 'px';
    } else {
        legendBody.classList.add('collapsed');
        toggleIcon.classList.add('collapsed');
        legendBody.style.maxHeight = '0';
    }
}

// Initialize max-height for animations
document.addEventListener('DOMContentLoaded', function() {
    categories.forEach(cat => {
        const catKey = cat.dataset.category;
        const content = document.getElementById(`category-${catKey}`);
        content.style.maxHeight = content.scrollHeight + 'px';
    });

    // Initialize legend height
    const legendBody = document.getElementById('legendBody');
    if (legendBody) {
        legendBody.style.maxHeight = legendBody.scrollHeight + 'px';
    }

    // Setup search and filter functionality
    setupSearchAndFilter();
});

function setupSearchAndFilter() {
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const categoriesContainer = document.getElementById('categoriesContainer');
    const noResults = document.getElementById('noResults');

    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const typeValue = typeFilter.value;
        const statusValue = statusFilter.value;

        let hasVisibleResults = false;
        let categoriesWithVisibleResults = new Set();

        // Filter each row
        const rows = document.querySelectorAll('.dt-table tbody tr');
        rows.forEach(row => {
            const code = row.dataset.code || '';
            const name = row.dataset.name || '';
            const type = row.dataset.type || '';
            const status = row.dataset.status || '';

            const matchesSearch = !searchTerm || code.includes(searchTerm) || name.includes(searchTerm);
            const matchesType = !typeValue || type === typeValue;
            const matchesStatus = !statusValue || status === statusValue;

            const isVisible = matchesSearch && matchesType && matchesStatus;
            row.style.display = isVisible ? '' : 'none';

            if (isVisible) {
                hasVisibleResults = true;
                // Find which category this row belongs to
                const category = row.closest('.dt-category');
                if (category) {
                    categoriesWithVisibleResults.add(category.dataset.category);
                }
            }
        });

        // Show/hide categories and expand those with results
        categories.forEach(cat => {
            const catKey = cat.dataset.category;
            const categoryRows = cat.querySelectorAll('.dt-table tbody tr');
            const hasVisibleRows = Array.from(categoryRows).some(row => row.style.display !== 'none');

            if (hasVisibleRows) {
                cat.style.display = '';
                // Auto-expand category if it has visible results and search is active
                if (searchTerm || typeValue || statusValue) {
                    if (!categoryStates[catKey]) {
                        toggleCategory(catKey);
                    }
                }
            } else {
                // Only hide category if there's a filter active
                if (searchTerm || typeValue || statusValue) {
                    cat.style.display = 'none';
                } else {
                    cat.style.display = '';
                }
            }
        });

        // Show/hide no results message
        if (searchTerm || typeValue || statusValue) {
            categoriesContainer.style.display = hasVisibleResults ? '' : 'none';
            noResults.style.display = hasVisibleResults ? 'none' : '';
        } else {
            categoriesContainer.style.display = '';
            noResults.style.display = 'none';
            // Show all categories when no filters
            categories.forEach(cat => {
                cat.style.display = '';
            });
        }
    }

    // Add event listeners
    searchInput.addEventListener('input', applyFilters);
    typeFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
}

function confirmToggleDeductionType(typeId, typeName, isActive) {
    const formId = typeId ? 'toggleForm-' + typeId : 'toggleForm';
    const action = isActive ? 'Deactivate' : 'Activate';
    const actionLower = isActive ? 'deactivate' : 'activate';
    Swal.fire({
        title: action + ' Deduction Type?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.2rem;font-weight:600;color:#dc3545;margin-bottom:8px;">${typeName}</div>
            <p style="color:#6b7280;font-size:0.95rem;">Are you sure you want to ${actionLower} this deduction type?</p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: action,
        cancelButtonText: 'Cancel',
        confirmButtonColor: isActive ? '#dc3545' : '#10B981',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById(formId);
            if (form) {
                const buttons = form.querySelectorAll('button');
                buttons.forEach(btn => {
                    btn.disabled = true;
                    btn.textContent = 'Processing...';
                });
                form.submit();
            }
        }
    });
}

function confirmDeleteDeductionType(typeId, typeName) {
    Swal.fire({
        title: 'Permanently Delete?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.1rem;font-weight:600;color:#dc3545;margin-bottom:8px;">${typeName}</div>
            <p style="color:#6b7280;font-size:0.9rem;">
                This will <strong>permanently remove</strong> the record from the database.<br>
                Only do this for test/unused entries with no payroll history.
            </p>
        </div>`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: '🗑 Delete Permanently',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('deleteForm-' + typeId);
            if (form) form.submit();
        }
    });
}
</script>
@endsection
