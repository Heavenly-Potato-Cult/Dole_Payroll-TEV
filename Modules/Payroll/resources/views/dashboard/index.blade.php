@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('styles')
<style>
/* ════════════════════════════════════════════════════════════════
   DASHBOARD — scoped with .db- prefix
   Mobile-first, fully responsive
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

.db-greeting .db-role-pill {
    display: inline-block;
    background: rgba(249,168,37,0.22);
    border: 1px solid rgba(249,168,37,0.45);
    color: var(--gold);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 3px 10px;
    border-radius: 20px;
    flex-shrink: 0;
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

/* Mobile responsiveness */
@media (max-width: 480px) {
    .db-greeting-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .db-greeting .db-role-pill {
        align-self: flex-start;
    }
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

.db-stat-tag {
    display: inline-flex;
    font-size: 12px;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 4px;
    align-self: flex-start;
}

.db-stat.purple .db-stat-tag {
    background: #EEEDFE;
    color: #534AB7;
}

.db-stat.coral .db-stat-tag {
    background: #FAECE7;
    color: #993C1D;
}

.db-stat.neutral .db-stat-tag {
    background: #EEEDFE;
    color: #534AB7;
}

.db-stat-value {
    font-size: 56px;
    font-weight: 600;
    letter-spacing: -3px;
    line-height: 1;
    color: #534AB7;
}

/* ── Pulsing dot ─────────────────────────────────────────────── */
.db-dot {
    display: inline-block;
    width: 8px; height: 8px;
    background: var(--red);
    border-radius: 50%;
    flex-shrink: 0;
    animation: dbpulse 1.8s ease-in-out infinite;
}
@keyframes dbpulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:0.4; transform:scale(0.65); }
}

/* ── Pending breakdown pills ─────────────────────────────────── */
.db-breakdown {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 5px;
}
.db-breakdown-pill {
    font-size: 0.65rem;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 20px;
    white-space: nowrap;
}
.db-bp-payroll { background:#E3F2FD; color:#0D47A1; }
.db-bp-tev     { background:#FFF9C4; color:#B45309; }
.db-bp-liq     { background:#FCE4EC; color:#880E4F; }

/* ── Layout ───────────────────────────────────────────────────── */
.db-main { display: flex; flex-direction: column; gap: 16px; margin-bottom: 16px; }

.db-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media (min-width: 768px) { .db-row { grid-template-columns: 1fr 1fr; } }

/* ── Cards ───────────────────────────────────────────────────── */
.db-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.db-card-head {
    padding: 11px 16px;
    border-bottom: 1px solid var(--border);
    background: #FAFBFF;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-shrink: 0;
}
.db-card-head h3 {
    font-size: 0.88rem;
    font-weight: 600;
    margin: 0;
    color: var(--navy);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.db-card-body { flex: 1; }

/* ── List rows ───────────────────────────────────────────────── */
.db-list { list-style: none; width: 100%; }
.db-list-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 16px;
    border-bottom: 1px solid var(--border);
    min-width: 0;
    transition: background 0.1s;
}
.db-list-item:last-child { border-bottom: none; }
.db-list-item:hover { background: var(--navy-light); }
.db-list-main { flex: 1; min-width: 0; }
.db-list-title {
    font-size: 0.83rem;
    font-weight: 600;
    color: var(--navy);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.db-list-sub {
    font-size: 0.72rem;
    color: var(--text-light);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 1px;
}
.db-list-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
}
.db-view-link {
    font-size: 0.7rem;
    color: var(--navy);
    opacity: 0.45;
    text-decoration: none;
    white-space: nowrap;
}
.db-view-link:hover { opacity: 1; text-decoration: underline; }

/* ── Badges ──────────────────────────────────────────────────── */
.db-badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 20px;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    white-space: nowrap;
}
.db-b-draft    { background: #ECEFF1; color: #607D8B; }
.db-b-computed { background: #E3F2FD; color: #0D47A1; }
.db-b-pending  { background: #FFF9C4; color: #B45309; }
.db-b-released { background: var(--success-bg); color: var(--success); }
.db-b-locked   { background: var(--navy-light); color: var(--navy); }
.db-b-navy     { background: var(--navy); color: #fff; }
.db-b-gold     { background: var(--gold-light); color: var(--gold-dark); }
.db-b-red      { background: var(--red-light); color: var(--red); }
.db-b-teal     { background: #E0F7FA; color: #00838F; }

/* ── Empty states ────────────────────────────────────────────── */
.db-empty {
    padding: 26px 16px;
    text-align: center;
    color: var(--text-light);
    font-size: 0.82rem;
}
.db-empty-icon { font-size: 1.5rem; margin-bottom: 6px; }

/* ── Quick Actions ───────────────────────────────────────────── */
.db-actions { padding: 12px 14px 14px; display: flex; flex-direction: column; gap: 7px; }
.db-action-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 13px;
    border-radius: 6px;
    border: 1.5px solid var(--border);
    background: white;
    color: var(--navy);
    font-size: 0.83rem;
    font-weight: 500;
    text-decoration: none;
    transition: background 0.12s, border-color 0.12s;
    gap: 8px;
    min-width: 0;
}
.db-action-btn:hover { background: var(--navy-light); border-color: var(--navy); color: var(--navy); text-decoration: none; }
.db-action-btn.primary { background: var(--navy); color: #fff; border-color: var(--navy); }
.db-action-btn.primary:hover { background: #1a2d6d; color: #fff; }
.db-action-btn.gold-btn { background: var(--gold); color: #fff; border-color: var(--gold); }
.db-action-btn.gold-btn:hover { background: #e5961f; color: #fff; }
.db-action-left {
    display: flex; align-items: center; gap: 7px;
    min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.db-action-count {
    background: var(--red);
    color: white;
    font-size: 0.62rem;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 10px;
    flex-shrink: 0;
    animation: dbpulse 1.8s ease-in-out infinite;
}
.db-action-sep {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--text-light);
    padding: 4px 2px 2px;
}

/* ── Quick Actions 2-column variant ─────────────────────────── */
.db-actions-2col {
    flex-direction: column;
    gap: 0;
    padding: 0;
}
.db-actions-2col .db-actions-col {
    width: 100%;
    padding: 12px 14px 14px;
    border-bottom: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.db-actions-2col .db-actions-col:last-child { border-bottom: none; }
@media (min-width: 480px) {
    .db-actions-2col {
        flex-direction: row;
        align-items: flex-start;
    }
    .db-actions-2col .db-actions-col {
        flex: 1;
        min-width: 0;
        border-bottom: none;
        border-right: 1px solid var(--border);
        padding: 12px 12px 14px;
    }
    .db-actions-2col .db-actions-col:last-child { border-right: none; }
}

/* ── Quick Actions 3-column variant ─────────────────────────── */
.db-actions-3col {
    flex-direction: column;
    gap: 0;
    padding: 0;
}
.db-actions-col {
    width: 100%;
    padding: 12px 14px 14px;
    border-bottom: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.db-actions-col:last-child { border-bottom: none; }
@media (min-width: 640px) {
    .db-actions-3col {
        flex-direction: row;
        align-items: flex-start;
    }
    .db-actions-col {
        flex: 1;
        min-width: 0;
        border-bottom: none;
        border-right: 1px solid var(--border);
        padding: 12px 10px 14px;
    }
    .db-actions-col:last-child { border-right: none; }
}

/* ── Chart ───────────────────────────────────────────────────── */
.db-chart-body {
    padding: 12px 16px 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.db-chart-wrap { position: relative; width: 100%; max-height: 190px; }

/* ── System info ─────────────────────────────────────────────── */
.db-sysinfo { padding: 14px 16px; }
.db-sysinfo-row {
    display: flex;
    align-items: baseline;
    gap: 10px;
    padding: 5px 0;
    border-bottom: 1px solid var(--border);
    font-size: 0.83rem;
    flex-wrap: wrap;
}
.db-sysinfo-row:last-child { border-bottom: none; }
.db-sysinfo-key {
    font-weight: 600;
    color: var(--text-light);
    flex-shrink: 0;
    width: 110px;
    font-size: 0.75rem;
}
.db-sysinfo-val { color: var(--text); min-width: 0; word-break: break-word; }

/* ── Queue Grid Layout ───────────────────────────────────────── */
.db-queue-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}

@media (max-width: 767px) {
    .db-queue-grid {
        grid-template-columns: 1fr;
    }
}

/* ── Queue List ───────────────────────────────────────────────── */
.db-queue-section {
    background: #fff;
    border: 0.5px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.db-queue-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 0.5px solid #e2e8f0;
}

.db-queue-label {
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--text);
}

.db-queue-viewall {
    font-size: 12px;
    color: #534AB7;
    text-decoration: none;
}

.db-queue-viewall:hover {
    text-decoration: underline;
}

.db-queue-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 0.5px solid #e2e8f0;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.12s;
}

.db-queue-row:hover,
.db-queue-row:hover .db-queue-label-text,
.db-queue-row:hover .db-queue-subtitle,
.db-queue-row:hover .db-queue-number,
.db-queue-row:hover .db-queue-chevron {
    text-decoration: none;
}

.db-queue-row:last-child {
    border-bottom: none;
}

.db-queue-row:hover {
    background: #f8fafc;
}

.db-queue-left {
    display: flex;
    flex-direction: column;
}

.db-queue-label-text {
    font-size: 13px;
    font-weight: 500;
    color: var(--text);
}

.db-queue-subtitle {
    font-size: 11px;
    color: #94a3b8;
}

.db-queue-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.db-queue-number {
    font-size: 20px;
    font-weight: 500;
    letter-spacing: -1px;
    color: var(--text);
}

.db-queue-chevron {
    font-size: 1.2rem;
    color: #cbd5e1;
}

.db-queue-badge {
    font-size: 10px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* ── Urgent row styling (Pending RD) ─────────────────────────── */
.db-queue-row.urgent {
    background: #FFFAF9;
}

.db-queue-row.urgent:hover {
    background: #FFF5F2;
}

.db-queue-row.urgent .db-queue-label-text {
    color: #D85A30;
}

.db-queue-row.urgent .db-queue-number {
    color: #D85A30;
}

.db-queue-row.urgent .db-queue-chevron {
    color: #D85A30;
}

.db-queue-row.urgent .db-queue-badge {
    background: #FAECE7;
    color: #993C1D;
}

/* ── TEV Summary mini-table ─────────────────────────────────── */
.db-mini-table { width: 100%; border-collapse: collapse; }
.db-mini-table td {
    padding: 7px 16px;
    font-size: 0.82rem;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}
.db-mini-table tr:last-child td { border-bottom: none; }
.db-mini-table .db-mt-label { color: var(--text-light); font-size: 0.75rem; }
.db-mini-table .db-mt-val { font-weight: 700; color: var(--navy); text-align: right; }
.db-mini-table tr:hover td { background: var(--navy-light); }
/* ── Super Admin Pipeline Strips ─────────────────────────── */
.sa-pipeline-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 11px 14px 13px;
    margin-bottom: 12px;
}
.sa-pipeline-label {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--text-light);
    margin-bottom: 10px;
}

/* Mobile: 2-column grid of tiles */
.sa-pipeline-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
/* Hide the arrow separators on mobile — they don't make sense in grid */
.sa-pipe-arrow { display: none; }

.sa-pitem {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 10px 8px;
    border-radius: 6px;
    border: 1.5px solid var(--border);
    background: #FAFBFF;
    text-decoration: none;
    transition: background 0.12s, border-color 0.12s;
    gap: 3px;
    min-width: 0;
}
.sa-pitem:hover {
    background: var(--navy-light);
    border-color: var(--navy);
    text-decoration: none;
}
.sa-pitem-val {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--navy);
    line-height: 1;
}
.sa-pitem-val.is-alert { color: var(--red); }
.sa-pitem-key {
    font-size: 0.62rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--text-light);
    line-height: 1.3;
}

/* TEV has 4 items — keep 2x2 on mobile too */
.sa-pipeline-grid--tev {
    grid-template-columns: 1fr 1fr;
}

/* Desktop (≥640px): switch to single inline row with arrow separators */
@media (min-width: 640px) {
    .sa-pipeline-grid {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 0;
    }
    .sa-pipe-arrow {
        display: block;
        font-size: 1.2rem;
        color: var(--border);
        flex-shrink: 0;
        padding: 0 4px;
        line-height: 1;
        user-select: none;
    }
    .sa-pitem {
        flex: 1;
        padding: 10px 10px;
    }
    .sa-pitem-val { font-size: 1.6rem; }
}
</style>
@endsection

@section('content')

{{-- ── Greeting ──────────────────────────────────────────────── --}}
<div class="db-greeting">
    <div class="db-greeting-header">
        <h1>Good {{ now()->format('H') < 12 ? 'morning' : (now()->format('H') < 17 ? 'afternoon' : 'evening') }},
            {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>
        <span class="db-role-pill">{{ str_replace('_', ' ', strtoupper(auth()->user()->getRoleNames()->first() ?? 'user')) }}</span>
    </div>
    <div class="db-greeting-body">
        <div class="db-greeting-date">{{ now()->format('l, F j, Y') }}</div>
        <div class="db-greeting-location">DOLE Regional Office IX, Zamboanga City</div>
    </div>
</div>

{{-- ── Stat Cards — role-aware ─────────────────────────────────── --}}
<div class="db-stat-grid">

    {{-- Card 1: Active Employees — all roles --}}
    <div class="db-stat purple">
        <div class="db-stat-left">
            <div>
                <div class="db-stat-title">Active Employees</div>
                <div class="db-stat-subtitle">Plantilla items</div>
            </div>
            <div class="db-stat-tag">HRIS</div>
        </div>
        <div class="db-stat-divider"></div>
        <div class="db-stat-right">
            <div class="db-stat-value">{{ number_format($totalEmployees) }}</div>
        </div>
    </div>

    {{-- Card 2: Current Cut-off — payroll roles only --}}
    @role('payroll_officer|hrmo|accountant|ard|chief_admin_officer')
    <div class="db-stat neutral">
        <div class="db-stat-left">
            <div>
                <div class="db-stat-title">Current Cut-off</div>
                <div class="db-stat-subtitle">{{ $currentMonth }} &mdash; {{ now()->day <= 15 ? '1–15' : '16–'.now()->daysInMonth }}</div>
            </div>
            <div class="db-stat-tag">Payroll</div>
        </div>
        <div class="db-stat-divider"></div>
        <div class="db-stat-right">
            <div class="db-stat-value">{{ $currentCutoff }}</div>
        </div>
    </div>
    @endrole
    @role('cashier|budget_officer|super_admin')
    <div class="db-stat neutral">
        <div class="db-stat-left">
            <div>
                <div class="db-stat-title">Payroll Batches</div>
                <div class="db-stat-subtitle">This month</div>
            </div>
            <div class="db-stat-tag">Payroll</div>
        </div>
        <div class="db-stat-divider"></div>
        <div class="db-stat-right">
            <div class="db-stat-value">{{ Modules\Payroll\Models\PayrollBatch::where('period_year', now()->year)->where('period_month', now()->month)->count() }}</div>
        </div>
    </div>
    @endrole

    {{-- Card 3: Pending Approvals — tailored sub-label per role --}}
    <div class="db-stat coral">
        <div class="db-stat-left">
            <div>
                <div class="db-stat-title">Pending Action</div>
                <div class="db-stat-subtitle">
                    @if($pendingApprovals > 0)
                        @if($pendingPayroll > 0){{ $pendingPayroll }} Payroll @endif
                    @else
                        All clear
                    @endif
                </div>
            </div>
            <div class="db-stat-tag">Payroll</div>
        </div>
        <div class="db-stat-divider"></div>
        <div class="db-stat-right">
            <div class="db-stat-value">{{ $pendingApprovals }}</div>
        </div>
    </div>

    {{-- Card 4: Role-specific 4th stat --}}
    @role('payroll_officer')
    <div class="db-stat neutral">
        <div class="db-stat-left">
            <div>
                @php $spPending = \Modules\Payroll\Models\SpecialPayrollBatch::whereIn('status',['draft','computed'])->count(); @endphp
                <div class="db-stat-title">Special Payroll</div>
                <div class="db-stat-subtitle">Draft / computed batches</div>
            </div>
            <div class="db-stat-tag">Payroll</div>
        </div>
        <div class="db-stat-divider"></div>
        <div class="db-stat-right">
            <div class="db-stat-value">{{ $spPending }}</div>
        </div>
    </div>
    @endrole
    @role('hrmo')
    <div class="db-stat neutral">
        <div class="db-stat-left">
            <div>
                <div class="db-stat-title">Payroll Batches</div>
                <div class="db-stat-subtitle">This month</div>
            </div>
            <div class="db-stat-tag">Payroll</div>
        </div>
        <div class="db-stat-divider"></div>
        <div class="db-stat-right">
            <div class="db-stat-value">{{ Modules\Payroll\Models\PayrollBatch::where('period_year', now()->year)->where('period_month', now()->month)->count() }}</div>
        </div>
    </div>
    @endrole
    @role('accountant')
    <div class="db-stat neutral">
        <div class="db-stat-left">
            <div>
                <div class="db-stat-title">Payroll for Review</div>
                <div class="db-stat-subtitle">Awaiting accountant cert.</div>
            </div>
            <div class="db-stat-tag">Payroll</div>
        </div>
        <div class="db-stat-divider"></div>
        <div class="db-stat-right">
            <div class="db-stat-value">{{ $pendingPayroll }}</div>
        </div>
    </div>
    @endrole
    @role('ard|chief_admin_officer')
    <div class="db-stat neutral">
        <div class="db-stat-left">
            <div>
                <div class="db-stat-title">Payroll for Approval</div>
                <div class="db-stat-subtitle">Acct. certified, needs RD</div>
            </div>
            <div class="db-stat-tag">Payroll</div>
        </div>
        <div class="db-stat-divider"></div>
        <div class="db-stat-right">
            <div class="db-stat-value">{{ $pendingPayroll }}</div>
        </div>
    </div>
    @endrole
    @role('cashier')
    <div class="db-stat neutral">
        <div class="db-stat-left">
            <div>
                <div class="db-stat-title">Payroll Released</div>
                <div class="db-stat-subtitle">This month</div>
            </div>
            <div class="db-stat-tag">Payroll</div>
        </div>
        <div class="db-stat-divider"></div>
        <div class="db-stat-right">
            <div class="db-stat-value">{{ Modules\Payroll\Models\PayrollBatch::where('status', 'released')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count() }}</div>
        </div>
    </div>
    @endrole
    @role('budget_officer')
    <div class="db-stat neutral">
        <div class="db-stat-left">
            <div>
                <div class="db-stat-title">Payroll Batches</div>
                <div class="db-stat-subtitle">This month</div>
            </div>
            <div class="db-stat-tag">Payroll</div>
        </div>
        <div class="db-stat-divider"></div>
        <div class="db-stat-right">
            <div class="db-stat-value">{{ Modules\Payroll\Models\PayrollBatch::where('period_year', now()->year)->where('period_month', now()->month)->count() }}</div>
        </div>
    </div>
    @endrole
    @role('super_admin')
    <div class="db-stat purple">
        <div class="db-stat-left">
            <div>
                @php 
                $officerRoles = ['super_admin', 'payroll_officer', 'hrmo', 'cashier', 'accountant', 'ard', 'budget_officer', 'chief_admin_officer'];
                $totalUsers = \App\Models\User::whereHas('roles', function($query) use ($officerRoles) {
                    $query->whereIn('name', $officerRoles);
                })->count();
                @endphp
                <div class="db-stat-title">System Users</div>
                <div class="db-stat-subtitle">Registered accounts</div>
            </div>
            <div class="db-stat-tag">Admin</div>
        </div>
        <div class="db-stat-divider"></div>
        <div class="db-stat-right">
            <div class="db-stat-value">{{ $totalUsers }}</div>
        </div>
    </div>
    @endrole

</div>{{-- /.db-stat-grid --}}

{{-- ══════════════════════════════════════════════════════════════
     MAIN CONTENT — role-driven layout
     ══════════════════════════════════════════════════════════════ --}}
<div class="db-main">

{{-- ─────────────────────────────────────────────────────────────
     SUPER ADMIN LAYOUT
     Full system view: all queues, pipeline chart, user mgmt
     Row 1: Recent Payroll (all statuses) | Recent TEV (all statuses)
     Row 2: Payroll Pipeline Chart | System-wide TEV Status + Quick Actions
     ───────────────────────────────────────────────────────────── --}}
@role('super_admin')

    {{-- Pipeline Overview: queue counters — 2-col on mobile, inline strips on desktop --}}
    @php
        $saPayrollDraft    = \Modules\Payroll\Models\PayrollBatch::whereIn('status',['draft','computed'])->count();
        $saPayrollAcct     = \Modules\Payroll\Models\PayrollBatch::where('status','pending_accountant')->count();
        $saPayrollRd       = \Modules\Payroll\Models\PayrollBatch::where('status','pending_rd')->count();
        $saTevSubmitted    = \Modules\Tev\Models\TevRequest::where('status','submitted')->count();
        $saTevCertified    = \Modules\Tev\Models\TevRequest::where('status','accountant_certified')->count();
        $saTevRdApproved   = \Modules\Tev\Models\TevRequest::where('status','rd_approved')->count();
        $saTevLiqFiled     = \Modules\Tev\Models\TevRequest::where('status','liquidation_filed')->count();
    @endphp

    {{-- ── Queue Grid: Payroll Queue, Recent Payroll Batches, Quick Access ─────────────────────────────── --}}
    <div class="db-queue-grid">

        {{-- Payroll Queue Section --}}
        @role('payroll_officer|hrmo|accountant|ard|cashier|chief_admin_officer|super_admin')
        <div class="db-queue-section">
            <div class="db-queue-header">
                <span class="db-queue-label">Payroll Queue</span>
                <a href="{{ route('payroll.index') }}" class="db-queue-viewall">View all →</a>
            </div>
            <a href="{{ route('payroll.index') }}?status=draft" class="db-queue-row">
                <div class="db-queue-left">
                    <div class="db-queue-label-text">Draft / Computed</div>
                    <div class="db-queue-subtitle">Awaiting review</div>
                </div>
                <div class="db-queue-right">
                    <div class="db-queue-number">{{ $saPayrollDraft }}</div>
                    <span class="db-queue-chevron">›</span>
                </div>
            </a>
            <a href="{{ route('payroll.index') }}?status=pending_accountant" class="db-queue-row">
                <div class="db-queue-left">
                    <div class="db-queue-label-text">Pending Acct.</div>
                    <div class="db-queue-subtitle">Accountant review</div>
                </div>
                <div class="db-queue-right">
                    <div class="db-queue-number">{{ $saPayrollAcct }}</div>
                    <span class="db-queue-chevron">›</span>
                </div>
            </a>
            <a href="{{ route('payroll.index') }}?status=pending_rd" class="db-queue-row {{ $saPayrollRd > 0 ? 'urgent' : '' }}">
                <div class="db-queue-left">
                    <div class="db-queue-label-text">Pending RD</div>
                    <div class="db-queue-subtitle">Regional Director approval</div>
                </div>
                <div class="db-queue-right">
                    @if($saPayrollRd > 0)
                    <span class="db-queue-badge">Action required</span>
                    @endif
                    <div class="db-queue-number">{{ $saPayrollRd }}</div>
                    <span class="db-queue-chevron">›</span>
                </div>
            </a>
        </div>
        @endrole
        @role('budget_officer')
        <div class="db-queue-section">
            <div class="db-queue-header">
                <span class="db-queue-label">Payroll Status</span>
            </div>
            <a href="{{ route('my-payslip') }}" class="db-queue-row">
                <div class="db-queue-left">
                    <div class="db-queue-label-text">View My Payslip</div>
                    <div class="db-queue-subtitle">Personal payroll records</div>
                </div>
                <div class="db-queue-right">
                    <span class="db-queue-chevron">›</span>
                </div>
            </a>
        </div>
        @endrole

        {{-- Recent Payroll Batches --}}
        <div class="db-queue-section">
            <div class="db-queue-header">
                <span class="db-queue-label">Recent Payroll Batches</span>
                <a href="{{ route('payroll.index') }}" class="db-queue-viewall">View all →</a>
            </div>
            @if($recentPayroll->isEmpty())
                <div class="db-empty"><div class="db-empty-icon">📭</div>No payroll batches yet.</div>
            @else
                @foreach($recentPayroll as $batch)
                @php
                    $mn = \Carbon\Carbon::create()->month($batch->period_month)->format('M');
                    $sm = ['draft'=>['Draft','db-b-draft'],'computed'=>['Computed','db-b-computed'],'pending_accountant'=>['Acct. Review','db-b-pending'],'pending_rd'=>['RD Review','db-b-pending'],'released'=>['Released','db-b-released'],'locked'=>['Locked','db-b-locked']];
                    $s  = $sm[$batch->status] ?? [$batch->status,'db-b-draft'];
                    $cutLabel = $batch->cutoff === '1st' ? '1st (1–15)' : '2nd (16–end)';
                @endphp
                <a href="{{ route('payroll.show', $batch->id) }}" class="db-queue-row">
                    <div class="db-queue-left">
                        <div class="db-queue-label-text">{{ $mn }} {{ $batch->period_year }}</div>
                        <div class="db-queue-subtitle">{{ $cutLabel }}@if($batch->creator) &mdash; {{ $batch->creator->name }}@endif</div>
                    </div>
                    <div class="db-queue-right">
                        <span class="db-queue-badge {{ $s[1] }}">{{ $s[0] }}</span>
                        <span class="db-queue-chevron">›</span>
                    </div>
                </a>
                @endforeach
            @endif
        </div>

        {{-- Quick Access --}}
        <div class="db-queue-section" style="grid-column: span 2;">
            <div class="db-queue-header">
                <span class="db-queue-label"> Quick Access</span>
            </div>
            <div class="db-actions db-actions-2col">
                <div class="db-actions-col">
                    <div class="db-action-sep">Payroll Operations</div>
                    <a href="{{ route('payroll.index') }}" class="db-action-btn">
                        <span class="db-action-left">Regular Payroll</span><span>→</span>
                    </a>
                    <a href="{{ route('special-payroll.newly-hired.index') }}" class="db-action-btn">
                        <span class="db-action-left">Newly Hired Payroll</span><span>→</span>
                    </a>
                    <a href="{{ route('special-payroll.differential.index') }}" class="db-action-btn">
                        <span class="db-action-left">Salary Differential</span><span>→</span>
                    </a>
                    <a href="{{ route('special-payroll.nosi-nosa.index') }}" class="db-action-btn">
                        <span class="db-action-left">NOSI/NOSA Payroll</span><span>→</span>
                    </a>
                </div>
                <div class="db-actions-col">
                    <div class="db-action-sep">Reports & Admin</div>
                    <a href="{{ route('reports.index') }}" class="db-action-btn">
                        <span class="db-action-left">Payroll Reports</span><span>→</span>
                    </a>
                    <a href="{{ route('employees.index') }}" class="db-action-btn">
                        <span class="db-action-left">Employees</span><span>→</span>
                    </a>
                    <a href="{{ route('users.index') }}" class="db-action-btn">
                        <span class="db-action-left">User Management</span><span>→</span>
                    </a>
                    <a href="{{ route('tev.dashboard') }}" class="db-action-btn">
                        <span class="db-action-left">TEV Dashboard</span><span>→</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

@endrole

{{-- ─────────────────────────────────────────────────────────────
     HRMO LAYOUT
     Row 1: Recent Payroll Batches (full width)
     Row 2: Chart + Quick Actions
     ───────────────────────────────────────────────────────────── --}}
@role('hrmo')

    <div class="db-row" style="grid-template-columns: 1fr;">

        {{-- Recent Payroll Batches --}}
        <div class="db-queue-section">
            <div class="db-queue-header">
                <span class="db-queue-label">💰 Recent Payroll Batches</span>
                <a href="{{ route('payroll.index') }}" class="db-queue-viewall">View all →</a>
            </div>
            @if($recentPayroll->isEmpty())
                <div class="db-empty"><div class="db-empty-icon">📭</div>No payroll batches yet.</div>
            @else
                @foreach($recentPayroll as $batch)
                @php
                    $mn = \Carbon\Carbon::create()->month($batch->period_month)->format('M');
                    $sm = ['draft'=>['Draft','db-b-draft'],'computed'=>['Computed','db-b-computed'],'pending_accountant'=>['Acct. Review','db-b-pending'],'pending_rd'=>['RD Review','db-b-pending'],'released'=>['Released','db-b-released'],'locked'=>['Locked','db-b-locked']];
                    $s  = $sm[$batch->status] ?? [$batch->status,'db-b-draft'];
                    $cutLabel = $batch->cutoff === '1st' ? '1st (1–15)' : '2nd (16–end)';
                @endphp
                <a href="{{ route('payroll.show', $batch->id) }}" class="db-queue-row">
                    <div class="db-queue-left">
                        <div class="db-queue-label-text">{{ $mn }} {{ $batch->period_year }}</div>
                        <div class="db-queue-subtitle">{{ $cutLabel }}@if($batch->creator) &mdash; {{ $batch->creator->name }}@endif</div>
                    </div>
                    <div class="db-queue-right">
                        <span class="db-queue-badge {{ $s[1] }}">{{ $s[0] }}</span>
                        <span class="db-queue-chevron">›</span>
                    </div>
                </a>
                @endforeach
            @endif
        </div>

    </div>{{-- /.db-row --}}

    {{-- Row 2: Chart + Quick Actions --}}
    <div class="db-row">

        @if($recentPayroll->isNotEmpty())
        <div class="db-card">
            <div class="db-card-head"><h3>📊 Payroll Status Overview</h3></div>
            <div class="db-chart-body">
                <div class="db-chart-wrap"><canvas id="payrollChart"></canvas></div>
            </div>
        </div>
        @endif

        <div class="db-card">
            <div class="db-card-head"><h3>⚡ Quick Actions</h3></div>
            <div class="db-actions db-actions-2col">
                <div class="db-actions-col">
                    <div class="db-action-sep">Payroll Operations</div>
                    <a href="{{ route('payroll.create') }}" class="db-action-btn primary">
                        <span class="db-action-left">💰 New Payroll Batch</span><span>→</span>
                    </a>
                    <a href="{{ route('payroll.index') }}?status=draft" class="db-action-btn">
                        <span class="db-action-left">📋 Payroll Batches
                            @if($pendingPayroll > 0)<span class="db-action-count">{{ $pendingPayroll }}</span>@endif
                        </span><span>→</span>
                    </a>
                    <a href="{{ route('special-payroll.newly-hired.index') }}" class="db-action-btn">
                        <span class="db-action-left">🆕 Newly Hired Payroll</span><span>→</span>
                    </a>
                </div>
                <div class="db-actions-col">
                    <div class="db-action-sep">Reports & Admin</div>
                    <a href="{{ route('reports.index') }}" class="db-action-btn">
                        <span class="db-action-left">📊 Payroll Reports</span><span>→</span>
                    </a>
                    <a href="{{ route('employees.index') }}" class="db-action-btn">
                        <span class="db-action-left">� Employees</span><span>→</span>
                    </a>
                    <a href="{{ route('tev.dashboard') }}" class="db-action-btn">
                        <span class="db-action-left">✈ TEV Dashboard</span><span>→</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

@endrole

{{-- ─────────────────────────────────────────────────────────────
     PAYROLL OFFICER LAYOUT
     Payroll-only role: no TEV access at all
     Row 1: Recent Payroll | Special Payroll summary
     Row 2: Chart (2fr) + Quick Actions 3-col (1fr)
     ───────────────────────────────────────────────────────────── --}}
@role('payroll_officer')

    <div class="db-row">

        {{-- Recent Regular Payroll Batches --}}
        <div class="db-queue-section">
            <div class="db-queue-header">
                <span class="db-queue-label">💰 Recent Payroll Batches</span>
                <a href="{{ route('payroll.index') }}" class="db-queue-viewall">View all →</a>
            </div>
            @if($recentPayroll->isEmpty())
                <div class="db-empty"><div class="db-empty-icon">📭</div>No payroll batches yet.</div>
            @else
                @foreach($recentPayroll as $batch)
                @php
                    $mn = \Carbon\Carbon::create()->month($batch->period_month)->format('M');
                    $sm = ['draft'=>['Draft','db-b-draft'],'computed'=>['Computed','db-b-computed'],'pending_accountant'=>['Acct. Review','db-b-pending'],'pending_rd'=>['RD Review','db-b-pending'],'released'=>['Released','db-b-released'],'locked'=>['Locked','db-b-locked']];
                    $s  = $sm[$batch->status] ?? [$batch->status,'db-b-draft'];
                    $cutLabel = $batch->cutoff === '1st' ? '1st (1–15)' : '2nd (16–end)';
                @endphp
                <a href="{{ route('payroll.show', $batch->id) }}" class="db-queue-row">
                    <div class="db-queue-left">
                        <div class="db-queue-label-text">{{ $mn }} {{ $batch->period_year }}</div>
                        <div class="db-queue-subtitle">{{ $cutLabel }}@if($batch->creator) &mdash; {{ $batch->creator->name }}@endif</div>
                    </div>
                    <div class="db-queue-right">
                        <span class="db-queue-badge {{ $s[1] }}">{{ $s[0] }}</span>
                        <span class="db-queue-chevron">›</span>
                    </div>
                </a>
                @endforeach
            @endif
        </div>

        {{-- Special Payroll Overview --}}
        <div class="db-card">
            <div class="db-card-head">
                <h3>📋 Special Payroll Overview</h3>
            </div>
            <div class="db-card-body">
                @php
                    $nhPending   = \Modules\Payroll\Models\SpecialPayrollBatch::where('type','newly_hired')->whereIn('status',['draft','computed'])->count();
                    $diffPending = \Modules\Payroll\Models\SpecialPayrollBatch::where('type','salary_differential')->whereIn('status',['draft','computed'])->count();
                    $nosiPending = \Modules\Payroll\Models\SpecialPayrollBatch::whereIn('type',['nosi','nosa'])->whereIn('status',['draft','computed'])->count();
                    $nhTotal     = \Modules\Payroll\Models\SpecialPayrollBatch::where('type','newly_hired')->count();
                    $diffTotal   = \Modules\Payroll\Models\SpecialPayrollBatch::where('type','salary_differential')->count();
                    $nosiTotal   = \Modules\Payroll\Models\SpecialPayrollBatch::whereIn('type',['nosi','nosa'])->count();
                @endphp
                <table class="db-mini-table">
                    <tr>
                        <td class="db-mt-label">🆕 Newly Hired</td>
                        <td class="db-mt-val">
                            {{ $nhTotal }} total
                            @if($nhPending > 0)<span class="db-breakdown-pill db-bp-payroll" style="margin-left:6px;">{{ $nhPending }} pending</span>@endif
                        </td>
                    </tr>
                    <tr>
                        <td class="db-mt-label">📈 Salary Differential</td>
                        <td class="db-mt-val">
                            {{ $diffTotal }} total
                            @if($diffPending > 0)<span class="db-breakdown-pill db-bp-payroll" style="margin-left:6px;">{{ $diffPending }} pending</span>@endif
                        </td>
                    </tr>
                    <tr>
                        <td class="db-mt-label">📑 NOSI / NOSA</td>
                        <td class="db-mt-val">
                            {{ $nosiTotal }} total
                            @if($nosiPending > 0)<span class="db-breakdown-pill db-bp-payroll" style="margin-left:6px;">{{ $nosiPending }} pending</span>@endif
                        </td>
                    </tr>
                    <tr>
                        <td class="db-mt-label">👤 Active Employees</td>
                        <td class="db-mt-val">{{ $totalEmployees }}</td>
                    </tr>
                    <tr>
                        <td class="db-mt-label">📅 Current Cut-off</td>
                        <td class="db-mt-val">{{ $currentCutoff }} — {{ $currentMonth }}</td>
                    </tr>
                </table>
            </div>
        </div>

    </div>

    {{-- Row 2: Chart + Quick Actions 3-col — equal width --}}
    <div class="db-row">

        @if($recentPayroll->isNotEmpty())
        <div class="db-card">
            <div class="db-card-head"><h3>📊 Payroll Status Overview</h3></div>
            <div class="db-chart-body">
                <div class="db-chart-wrap"><canvas id="payrollChart"></canvas></div>
            </div>
        </div>
        @endif

        <div class="db-card">
            <div class="db-card-head"><h3>⚡ Quick Actions</h3></div>
            <div class="db-actions db-actions-3col">

                <div class="db-actions-col">
                    <div class="db-action-sep">Regular Payroll</div>
                    <a href="{{ route('payroll.create') }}" class="db-action-btn primary">
                        <span class="db-action-left">💰 New Payroll Batch</span><span>→</span>
                    </a>
                    <a href="{{ route('payroll.index') }}?status=draft" class="db-action-btn">
                        <span class="db-action-left">📋 Draft Batches
                            @if($pendingPayroll > 0)<span class="db-action-count">{{ $pendingPayroll }}</span>@endif
                        </span><span>→</span>
                    </a>
                </div>

                <div class="db-actions-col">
                    <div class="db-action-sep">Special Payroll</div>
                    <a href="{{ route('special-payroll.newly-hired.create') }}" class="db-action-btn">
                        <span class="db-action-left">🆕 New Hire Payroll</span><span>→</span>
                    </a>
                    <a href="{{ route('special-payroll.differential.create') }}" class="db-action-btn">
                        <span class="db-action-left">📈 Salary Differential</span><span>→</span>
                    </a>
                    <a href="{{ route('special-payroll.nosi-nosa.create') }}" class="db-action-btn">
                        <span class="db-action-left">📑 NOSI / NOSA</span><span>→</span>
                    </a>
                </div>

                <div class="db-actions-col">
                    <div class="db-action-sep">Admin</div>
                    <a href="{{ route('employees.index') }}" class="db-action-btn">
                        <span class="db-action-left">👤 Employees</span><span>→</span>
                    </a>
                    <a href="{{ route('reports.index') }}" class="db-action-btn">
                        <span class="db-action-left">📊 Reports</span><span>→</span>
                    </a>
                    <a href="{{ route('users.index') }}" class="db-action-btn">
                        <span class="db-action-left">⚙ User Management</span><span>→</span>
                    </a>
                </div>

            </div>
        </div>

    </div>

@endrole

{{-- ─────────────────────────────────────────────────────────────
     ACCOUNTANT LAYOUT
     Row 1: Pending Payroll (focus) | TEV Certifications queue
     Row 2: Recent Payroll list | Chart
     ───────────────────────────────────────────────────────────── --}}
@role('accountant')

    <div class="db-row">

        {{-- Payroll Queue --}}
        <div class="db-card">
            <div class="db-card-head">
                <h3>💰 Payroll Awaiting Certification</h3>
                <a href="{{ route('payroll.index') }}?status=pending_accountant" class="btn btn-outline btn-sm" style="flex-shrink:0;">View All</a>
            </div>
            <div class="db-card-body">
                @php
                    $pendingBatches = \Modules\Payroll\Models\PayrollBatch::with('creator')->where('status','pending_accountant')->orderByDesc('id')->limit(5)->get();
                @endphp
                @if($pendingBatches->isEmpty())
                    <div class="db-empty"><div class="db-empty-icon">✅</div>No payroll batches pending certification.</div>
                @else
                    <ul class="db-list">
                        @foreach($pendingBatches as $batch)
                        @php $mn = \Carbon\Carbon::create()->month($batch->period_month)->format('M'); @endphp
                        <li class="db-list-item">
                            <div class="db-list-main">
                                <div class="db-list-title">{{ $mn }} {{ $batch->period_year }} — {{ $batch->cutoff === '1st' ? '1st Cut-off' : '2nd Cut-off' }}</div>
                                <div class="db-list-sub">Filed by: {{ $batch->creator->name ?? '—' }}</div>
                            </div>
                            <div class="db-list-right">
                                <span class="db-badge db-b-pending">Acct. Review</span>
                                <a href="{{ route('payroll.show', $batch->id) }}" class="db-view-link">Certify →</a>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- TEV Certification Queue --}}
        <div class="db-card">
            <div class="db-card-head">
                <h3>✈ TEV for Certification</h3>
                <a href="{{ route('tev.requests.index') }}?status=submitted" class="btn btn-outline btn-sm" style="flex-shrink:0;">View All</a>
            </div>
            <div class="db-card-body">
                @php
                    $pendingTevs = \Modules\Tev\Models\TevRequest::with('employee')->where('status','submitted')->orderByDesc('id')->limit(5)->get();
                @endphp
                @if($pendingTevs->isEmpty())
                    <div class="db-empty"><div class="db-empty-icon">✅</div>No TEVs awaiting certification.</div>
                @else
                    <ul class="db-list">
                        @foreach($pendingTevs as $tev)
                        @php
                            $tk = $tev->track === 'cash_advance' ? ['CA','db-b-navy'] : ['Reimb','db-b-gold'];
                            $en = $tev->employee ? $tev->employee->last_name.', '.substr($tev->employee->first_name,0,1).'.' : '—';
                        @endphp
                        <li class="db-list-item">
                            <div class="db-list-main">
                                <div class="db-list-title" style="font-family:monospace;font-size:0.79rem;">{{ $tev->tev_no }}</div>
                                <div class="db-list-sub">{{ $en }} &mdash; ₱{{ number_format($tev->grand_total,2) }}</div>
                            </div>
                            <div class="db-list-right">
                                <span class="db-badge {{ $tk[1] }}">{{ $tk[0] }}</span>
                                <a href="{{ route('tev.requests.show', $tev->id) }}" class="db-view-link">Certify →</a>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>

    <div class="db-row">
        @if($recentPayroll->isNotEmpty())
        <div class="db-card">
            <div class="db-card-head"><h3>📊 Payroll Status Overview</h3></div>
            <div class="db-chart-body">
                <div class="db-chart-wrap"><canvas id="payrollChart"></canvas></div>
            </div>
        </div>
        @endif
        <div class="db-card">
            <div class="db-card-head"><h3>⚡ Quick Actions</h3></div>
            <div class="db-actions db-actions-2col">
                <div class="db-actions-col">
                    <div class="db-action-sep">Payroll</div>
                    <a href="{{ route('payroll.index') }}?status=pending_accountant" class="db-action-btn primary">
                        <span class="db-action-left">💰 Certify Payroll
                            @if($pendingPayroll > 0)<span class="db-action-count">{{ $pendingPayroll }}</span>@endif
                        </span><span>→</span>
                    </a>
                </div>
                <div class="db-actions-col">
                    <div class="db-action-sep">TEV &amp; Reports</div>
                    <a href="{{ route('tev.requests.index') }}?status=submitted" class="db-action-btn{{ $pendingTev > 0 ? ' primary' : '' }}">
                        <span class="db-action-left">✈ Certify TEV
                            @if($pendingTev > 0)<span class="db-action-count">{{ $pendingTev }}</span>@endif
                        </span><span>→</span>
                    </a>
                    <a href="{{ route('reports.index') }}" class="db-action-btn">
                        <span class="db-action-left">📊 Reports</span><span>→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

@endrole

{{-- ─────────────────────────────────────────────────────────────
     ARD / CHIEF ADMIN OFFICER LAYOUT
     Row 1: Payroll awaiting RD | TEV awaiting RD approval
     Row 2: Chart + Quick Actions
     ───────────────────────────────────────────────────────────── --}}
@role('ard|chief_admin_officer')

    <div class="db-row">

        <div class="db-card">
            <div class="db-card-head">
                <h3>💰 Payroll Awaiting Approval</h3>
                <a href="{{ route('payroll.index') }}?status=pending_rd" class="btn btn-outline btn-sm" style="flex-shrink:0;">View All</a>
            </div>
            <div class="db-card-body">
                @php $rdBatches = \Modules\Payroll\Models\PayrollBatch::with('creator')->where('status','pending_rd')->orderByDesc('id')->limit(5)->get(); @endphp
                @if($rdBatches->isEmpty())
                    <div class="db-empty"><div class="db-empty-icon">✅</div>No payroll pending your approval.</div>
                @else
                    <ul class="db-list">
                        @foreach($rdBatches as $batch)
                        @php $mn = \Carbon\Carbon::create()->month($batch->period_month)->format('M'); @endphp
                        <li class="db-list-item">
                            <div class="db-list-main">
                                <div class="db-list-title">{{ $mn }} {{ $batch->period_year }} — {{ $batch->cutoff === '1st' ? '1st' : '2nd' }} Cut-off</div>
                                <div class="db-list-sub">Filed by: {{ $batch->creator->name ?? '—' }}</div>
                            </div>
                            <div class="db-list-right">
                                <span class="db-badge db-b-pending">RD Review</span>
                                <a href="{{ route('payroll.show', $batch->id) }}" class="db-view-link">Approve →</a>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="db-card">
            <div class="db-card-head">
                <h3>✈ TEV Awaiting Approval</h3>
                <a href="{{ route('tev.requests.index') }}?status=accountant_certified" class="btn btn-outline btn-sm" style="flex-shrink:0;">View All</a>
            </div>
            <div class="db-card-body">
                @php $rdTevs = \Modules\Tev\Models\TevRequest::with('employee')->where('status','accountant_certified')->orderByDesc('id')->limit(5)->get(); @endphp
                @if($rdTevs->isEmpty())
                    <div class="db-empty"><div class="db-empty-icon">✅</div>No TEVs pending your approval.</div>
                @else
                    <ul class="db-list">
                        @foreach($rdTevs as $tev)
                        @php
                            $tk = $tev->track === 'cash_advance' ? ['CA','db-b-navy'] : ['Reimb','db-b-gold'];
                            $en = $tev->employee ? $tev->employee->last_name.', '.substr($tev->employee->first_name,0,1).'.' : '—';
                        @endphp
                        <li class="db-list-item">
                            <div class="db-list-main">
                                <div class="db-list-title" style="font-family:monospace;font-size:0.79rem;">{{ $tev->tev_no }}</div>
                                <div class="db-list-sub">{{ $en }} &mdash; ₱{{ number_format($tev->grand_total,2) }}</div>
                            </div>
                            <div class="db-list-right">
                                <span class="db-badge {{ $tk[1] }}">{{ $tk[0] }}</span>
                                <a href="{{ route('tev.requests.show', $tev->id) }}" class="db-view-link">Approve →</a>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>

    <div class="db-row">
        @if($recentPayroll->isNotEmpty())
        <div class="db-card">
            <div class="db-card-head"><h3>📊 Payroll Status Overview</h3></div>
            <div class="db-chart-body">
                <div class="db-chart-wrap"><canvas id="payrollChart"></canvas></div>
            </div>
        </div>
        @endif
        <div class="db-card">
            <div class="db-card-head"><h3>⚡ Quick Actions</h3></div>
            <div class="db-actions db-actions-2col">
                <div class="db-actions-col">
                    <div class="db-action-sep">Payroll</div>
                    <a href="{{ route('payroll.index') }}?status=pending_rd" class="db-action-btn primary">
                        <span class="db-action-left">💰 Approve Payroll
                            @if($pendingPayroll > 0)<span class="db-action-count">{{ $pendingPayroll }}</span>@endif
                        </span><span>→</span>
                    </a>
                </div>
                <div class="db-actions-col">
                    <div class="db-action-sep">TEV &amp; Reports</div>
                    <a href="{{ route('tev.requests.index') }}?status=accountant_certified" class="db-action-btn{{ $pendingTev > 0 ? ' primary' : '' }}">
                        <span class="db-action-left">✈ Approve TEV
                            @if($pendingTev > 0)<span class="db-action-count">{{ $pendingTev }}</span>@endif
                        </span><span>→</span>
                    </a>
                    <a href="{{ route('reports.index') }}" class="db-action-btn">
                        <span class="db-action-left">📊 Reports</span><span>→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

@endrole

{{-- ─────────────────────────────────────────────────────────────
     CASHIER LAYOUT
     No payroll batches visible — only TEV release + liquidations
     Row 1: TEV for Release | Liquidations Pending
     Row 2: Quick Actions (full width or narrow)
     ───────────────────────────────────────────────────────────── --}}
@role('cashier')

    <div class="db-row">

        {{-- TEV for Release (rd_approved) --}}
        <div class="db-card">
            <div class="db-card-head">
                <h3>💵 TEV for Release</h3>
                <a href="{{ route('tev.requests.index') }}?status=rd_approved" class="btn btn-outline btn-sm" style="flex-shrink:0;">View All</a>
            </div>
            <div class="db-card-body">
                @php $forRelease = \Modules\Tev\Models\TevRequest::with('employee')->where('status','rd_approved')->orderByDesc('id')->limit(6)->get(); @endphp
                @if($forRelease->isEmpty())
                    <div class="db-empty"><div class="db-empty-icon">✅</div>No TEVs awaiting release.</div>
                @else
                    <ul class="db-list">
                        @foreach($forRelease as $tev)
                        @php
                            $tk = $tev->track === 'cash_advance' ? ['CA','db-b-navy'] : ['Reimb','db-b-gold'];
                            $en = $tev->employee ? $tev->employee->last_name.', '.substr($tev->employee->first_name,0,1).'.' : '—';
                        @endphp
                        <li class="db-list-item">
                            <div class="db-list-main">
                                <div class="db-list-title" style="font-family:monospace;font-size:0.79rem;">{{ $tev->tev_no }}</div>
                                <div class="db-list-sub">{{ $en }} &mdash; ₱{{ number_format($tev->grand_total,2) }}</div>
                            </div>
                            <div class="db-list-right">
                                <span class="db-badge {{ $tk[1] }}">{{ $tk[0] }}</span>
                                <a href="{{ route('tev.requests.show', $tev->id) }}" class="db-view-link">Release →</a>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Liquidations Pending (liquidation_filed) --}}
        <div class="db-card">
            <div class="db-card-head">
                <h3>🗂 Liquidations to Approve</h3>
                <a href="{{ route('tev.requests.index') }}?status=liquidation_filed" class="btn btn-outline btn-sm" style="flex-shrink:0;">View All</a>
            </div>
            <div class="db-card-body">
                @php $liquids = \Modules\Tev\Models\TevRequest::with('employee')->where('status','liquidation_filed')->orderByDesc('id')->limit(6)->get(); @endphp
                @if($liquids->isEmpty())
                    <div class="db-empty"><div class="db-empty-icon">✅</div>No pending liquidations.</div>
                @else
                    <ul class="db-list">
                        @foreach($liquids as $tev)
                        @php $en = $tev->employee ? $tev->employee->last_name.', '.substr($tev->employee->first_name,0,1).'.' : '—'; @endphp
                        <li class="db-list-item">
                            <div class="db-list-main">
                                <div class="db-list-title" style="font-family:monospace;font-size:0.79rem;">{{ $tev->tev_no }}</div>
                                <div class="db-list-sub">{{ $en }} &mdash; Bal: ₱{{ number_format(abs($tev->balance_due),2) }}{{ $tev->balance_due > 0 ? ' (refund)' : ($tev->balance_due < 0 ? ' (claim)' : ' (settled)') }}</div>
                            </div>
                            <div class="db-list-right">
                                <span class="db-badge db-b-pending">Liq. Filed</span>
                                <a href="{{ route('tev.requests.show', $tev->id) }}" class="db-view-link">Approve →</a>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>

    <div class="db-row">
        <div class="db-card">
            <div class="db-card-head"><h3>⚡ Quick Actions</h3></div>
            <div class="db-actions">
                <a href="{{ route('tev.requests.index') }}?status=rd_approved" class="db-action-btn primary">
                    <span class="db-action-left">💵 TEV for Release
                        @if($pendingTev > 0)<span class="db-action-count">{{ $pendingTev }}</span>@endif
                    </span><span>→</span>
                </a>
                <a href="{{ route('tev.requests.index') }}?status=liquidation_filed" class="db-action-btn{{ $pendingLiquidation > 0 ? ' primary' : '' }}">
                    <span class="db-action-left">🗂 Approve Liquidations
                        @if($pendingLiquidation > 0)<span class="db-action-count">{{ $pendingLiquidation }}</span>@endif
                    </span><span>→</span>
                </a>
                <a href="{{ route('tev.requests.index') }}" class="db-action-btn">
                    <span class="db-action-left">✈ All TEV Requests</span><span>→</span>
                </a>
            </div>
        </div>
        {{-- TEV summary mini-table for cashier --}}
        <div class="db-card">
            <div class="db-card-head"><h3>📊 TEV Status Summary</h3></div>
            <div class="db-card-body">
                <table class="db-mini-table">
                    @foreach(['submitted'=>'Submitted','accountant_certified'=>'Acct. Certified','rd_approved'=>'RD Approved','cashier_released'=>'CA Released','liquidation_filed'=>'Liq. Filed','liquidated'=>'Liquidated','reimbursed'=>'Reimbursed'] as $key=>$label)
                    <tr>
                        <td class="db-mt-label">{{ $label }}</td>
                        <td class="db-mt-val">{{ $tevByStatus[$key] ?? 0 }}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>

@endrole

{{-- ─────────────────────────────────────────────────────────────
     BUDGET OFFICER LAYOUT
     Monitors TEV submissions, no payroll access
     ───────────────────────────────────────────────────────────── --}}
@role('budget_officer')

    <div class="db-row">

        <div class="db-card">
            <div class="db-card-head">
                <h3>✈ Recent TEV Requests</h3>
                <a href="{{ route('tev.requests.index') }}" class="btn btn-outline btn-sm" style="flex-shrink:0;">View All</a>
            </div>
            <div class="db-card-body">
                @if($recentTev->isEmpty())
                    <div class="db-empty"><div class="db-empty-icon">✈</div>No TEV requests yet.</div>
                @else
                    <ul class="db-list">
                        @foreach($recentTev as $tev)
                        @php
                            $tevSt = ['draft'=>['Draft','db-b-draft'],'submitted'=>['Submitted','db-b-pending'],'accountant_certified'=>['Acct. Cert.','db-b-computed'],'rd_approved'=>['RD Approved','db-b-released'],'cashier_released'=>['CA Released','db-b-gold'],'reimbursed'=>['Reimbursed','db-b-released'],'liquidation_filed'=>['Liq. Filed','db-b-pending'],'liquidated'=>['Liquidated','db-b-locked'],'rejected'=>['Rejected','db-b-red']];
                            $t  = $tevSt[$tev->status] ?? [ucwords(str_replace('_',' ',$tev->status)),'db-b-draft'];
                            $tk = $tev->track === 'cash_advance' ? ['CA','db-b-navy'] : ['Reimb','db-b-gold'];
                            $en = $tev->employee ? $tev->employee->last_name.', '.substr($tev->employee->first_name,0,1).'.' : '—';
                        @endphp
                        <li class="db-list-item">
                            <div class="db-list-main">
                                <div class="db-list-title" style="font-family:monospace;font-size:0.79rem;">{{ $tev->tev_no }}</div>
                                <div class="db-list-sub">{{ $en }} &mdash; ₱{{ number_format($tev->grand_total,2) }}</div>
                            </div>
                            <div class="db-list-right">
                                <div style="display:flex;gap:3px;flex-wrap:wrap;justify-content:flex-end;">
                                    <span class="db-badge {{ $tk[1] }}">{{ $tk[0] }}</span>
                                    <span class="db-badge {{ $t[1] }}">{{ $t[0] }}</span>
                                </div>
                                <a href="{{ route('tev.requests.show', $tev->id) }}" class="db-view-link">View →</a>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="db-card">
            <div class="db-card-head"><h3>📊 TEV Status Summary</h3></div>
            <div class="db-card-body">
                <table class="db-mini-table">
                    @foreach(['submitted'=>'Submitted','accountant_certified'=>'Acct. Certified','rd_approved'=>'RD Approved','cashier_released'=>'CA Released','liquidation_filed'=>'Liq. Filed','liquidated'=>'Liquidated','reimbursed'=>'Reimbursed'] as $key=>$label)
                    <tr>
                        <td class="db-mt-label">{{ $label }}</td>
                        <td class="db-mt-val">{{ $tevByStatus[$key] ?? 0 }}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>

    </div>

    <div class="db-row">
        <div class="db-card">
            <div class="db-card-head"><h3>⚡ Quick Actions</h3></div>
            <div class="db-actions">
                <a href="{{ route('tev.requests.index') }}?status=submitted" class="db-action-btn primary">
                    <span class="db-action-left">📥 TEV Submissions
                        @if($pendingTev > 0)<span class="db-action-count">{{ $pendingTev }}</span>@endif
                    </span><span>→</span>
                </a>
                <a href="{{ route('tev.requests.index') }}" class="db-action-btn">
                    <span class="db-action-left">✈ All TEV Requests</span><span>→</span>
                </a>
                <a href="{{ route('reports.index') }}" class="db-action-btn">
                    <span class="db-action-left">📊 Reports</span><span>→</span>
                </a>
            </div>
        </div>
    </div>

@endrole

</div>{{-- /.db-main --}}


@endsection

@section('scripts')
{{-- Chart only for roles with payroll visibility --}}
@role('payroll_officer|hrmo|accountant|ard|chief_admin_officer|super_admin')
@if($recentPayroll->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    var cs     = getComputedStyle(document.documentElement);
    var navy   = cs.getPropertyValue('--navy').trim()   || '#0F1B4C';
    var gold   = cs.getPropertyValue('--gold').trim()   || '#F9A825';
    var red    = cs.getPropertyValue('--red').trim()    || '#B71C1C';

    var allLabels = ['Draft','Computed','Pending Accountant','Pending RD','Released','Locked'];
    var allColors = ['#9090AA', navy, gold, red, '#1B5E20', '#4A148C'];
    var rawData   = @json(array_values($payrollStatusData));

    var labels = [], data = [], colors = [];
    for (var i = 0; i < rawData.length; i++) {
        if (rawData[i] > 0) {
            labels.push(allLabels[i]);
            data.push(rawData[i]);
            colors.push(allColors[i]);
        }
    }
    if (!data.length) { labels = ['No Batches']; data = [1]; colors = ['#9090AA']; }

    var ctx = document.getElementById('payrollChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 3,
                borderColor: '#ffffff',
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: { padding: 12, font: { size: 11 }, boxWidth: 11, boxHeight: 11 }
                },
                tooltip: {
                    callbacks: {
                        label: function(c) { return '  ' + c.label + ': ' + c.parsed + ' batch(es)'; }
                    }
                }
            }
        }
    });
})();
</script>
@endif
@endrole
@endsection
