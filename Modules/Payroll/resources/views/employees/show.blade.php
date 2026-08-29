@extends('layouts.app')

@section('title', $employee->full_name)
@section('page-title', 'Employee Profile')

@section('styles')
<style>
/* ── Header Section ──────────────────────────────────────── */
.employee-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
}

.employee-header-right {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.employee-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.employee-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 600;
    color: #6c757d;
    flex-shrink: 0;
}

.employee-info h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: var(--text);
}

.employee-info p {
    margin: 4px 0 0 0;
    color: var(--text-mid);
    font-size: 0.9rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.active {
    background: #212529;
    color: #fff;
}

.status-badge.inactive {
    background: #dc3545;
    color: #fff;
}

.status-badge.vacant {
    background: #6c757d;
    color: #fff;
}

/* ── Tabs Navigation ──────────────────────────────────────── */
.tabs-nav {
    display: flex;
    gap: 0;
    border-bottom: 1px solid var(--border);
    margin: 0 auto 24px auto;
    max-width: 800px;
    justify-content: center;
}

.tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--text-mid);
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.tab-btn:hover {
    color: var(--text);
}

.tab-btn.active {
    color: var(--text);
    border-bottom-color: #212529;
}

/* ── Tab Panels ───────────────────────────────────────────── */
.tab-panel {
    display: none;
}

.tab-panel.active {
    display: block;
}

/* ── Info Card ────────────────────────────────────────────── */
.info-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 24px 32px;
    max-width: 800px;
    margin: 0 auto;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding: 14px 0;
    border-bottom: 1px solid var(--border);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: var(--text-light);
    font-size: 0.875rem;
    font-weight: 500;
    min-width: 160px;
}

.info-value {
    color: var(--text);
    font-size: 0.875rem;
    font-weight: 500;
    text-align: right;
}

.info-value.mono {
    font-family: monospace;
}

.info-value.bold {
    font-weight: 700;
}

/* ── History Table ───────────────────────────────────────── */
.history-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
    max-width: 800px;
    margin: 0 auto;
}

.history-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid var(--border);
}

.history-card .card-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.record-count {
    font-size: 0.8rem;
    color: var(--text-mid);
}

/* ── Footer Meta ───────────────────────────────────────────── */
.footer-meta {
    margin: 24px auto 0 auto;
    padding-top: 16px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    font-size: 0.78rem;
    color: var(--text-light);
    max-width: 800px;
}

@media (max-width: 600px) {
    .tabs-nav {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .info-card {
        padding: 16px 20px;
    }

    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }

    .info-value {
        text-align: left;
    }
}
</style>
@endsection

@section('content')

<div class="employee-header">
    <div class="employee-header-left">
        <div class="employee-avatar">
            {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
        </div>
        <div class="employee-info">
            <h1>{{ $employee->full_name }}</h1>
            <p>{{ $employee->position_title }}</p>
        </div>
    </div>
    <div class="employee-header-right">
        @role('payroll_officer|hrmo')
        <a href="{{ route('employees.deductions', $employee) }}" class="btn btn-outline">💳 Deductions</a>
        <a href="{{ route('payroll.employees.allowances', $employee) }}" class="btn btn-outline">🎫 Allowances</a>
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary">✎ Edit</a>
        @endrole
        <a href="{{ route('employees.index') }}" class="btn btn-outline">← Back</a>
    </div>
</div>

{{-- ── Tab Navigation ─────────────────────────────────────── --}}
<div class="tabs-nav">
    <button class="tab-btn active" data-tab="personal">Personal Information</button>
    <button class="tab-btn" data-tab="position">Position & Assignment</button>
    <button class="tab-btn" data-tab="salary">Salary Information</button>
    <button class="tab-btn" data-tab="government">Government IDs</button>
</div>

{{-- ── Tab: Personal Information ──────────────────────────── --}}
<div class="tab-panel active" id="tab-personal">
    <div class="info-card">
        <div class="info-row">
            <span class="info-label">Full Name</span>
            <span class="info-value bold">{{ $employee->full_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Last Name</span>
            <span class="info-value">{{ $employee->last_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">First Name</span>
            <span class="info-value">{{ $employee->first_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Middle Name</span>
            <span class="info-value">{{ $employee->middle_name ?: '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Suffix</span>
            <span class="info-value">{{ $employee->suffix ?: '—' }}</span>
        </div>
    </div>
</div>

{{-- ── Tab: Position & Assignment ─────────────────────────── --}}
<div class="tab-panel" id="tab-position">
    <div class="info-card">
        <div class="info-row">
            <span class="info-label">Plantilla Item No.</span>
            <span class="info-value mono">{{ $employee->plantilla_item_no ?: '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">PSIPOP Office</span>
            <span class="info-value">{{ $employee->psipopOffice->name ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Position Title</span>
            <span class="info-value">{{ $employee->position_title }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Division</span>
            <span class="info-value">{{ $employee->division ? $employee->division->code . ' — ' . $employee->division->name : '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Hire Date</span>
            <span class="info-value">{{ $employee->hire_date ? $employee->hire_date->format('F d, Y') : '—' }}</span>
        </div>
    </div>

    @if ($employee->promotionHistory->count())
    <div class="history-card" style="margin-top: 20px; margin-left: auto; margin-right: auto;">
        <div class="card-header">
            <h3>Promotion / Step History</h3>
            <span class="record-count">
                {{ $employee->promotionHistory->count() }} {{ Str::plural('record', $employee->promotionHistory->count()) }}
            </span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Effective Date</th>
                        <th>SG</th>
                        <th>Step</th>
                        <th style="text-align:right;">Amount</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employee->promotionHistory as $hist)
                    <tr>
                        <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($hist->effectivity_date)->format('M d, Y') }}</td>
                        <td>{{ $hist->new_salary_grade }}</td>
                        <td>{{ $hist->new_step }}</td>
                        <td style="text-align:right;font-family:monospace;">₱{{ number_format($hist->new_basic_salary, 2) }}</td>
                        <td style="font-size:0.82rem;color:var(--text-mid);">{{ $hist->remarks ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

{{-- ── Tab: Salary Information ────────────────────────────── --}}
<div class="tab-panel" id="tab-salary">
    <div class="info-card">
        <div class="info-row">
            <span class="info-label">Salary Grade</span>
            <span class="info-value">SG {{ $employee->salary_grade }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Step</span>
            <span class="info-value">Step {{ $employee->step }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">SIT Year</span>
            <span class="info-value">CY {{ $employee->sit_year }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Basic Salary</span>
            <span class="info-value bold">₱{{ number_format($employee->basic_salary, 2) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">PERA</span>
            <span class="info-value">
                ₱{{ number_format($peraInfo['amount'], 2) }}
                @unless ($peraInfo['from_standing_enrollment'])
                    <span style="color:var(--text-light);font-weight:400;font-size:0.78rem;">(legacy default)</span>
                @endunless
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">1st Cutoff Split</span>
            <span class="info-value">
                @if ($employee->salary_split_override_pct !== null)
                    {{ number_format($employee->salary_split_override_pct, 0) }}% / {{ number_format(100 - $employee->salary_split_override_pct, 0) }}%
                    <span style="color:var(--text-light);font-weight:400;font-size:0.78rem;">(custom)</span>
                @else
                    <span style="color:var(--text-light);font-weight:400;">Actual attendance days (default)</span>
                @endif
            </span>
        </div>
        <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
        <div class="info-row">
            <span class="info-label">Daily Rate (÷22)</span>
            <span class="info-value mono">₱{{ number_format($employee->daily_rate, 4) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Hourly Rate (÷22÷8)</span>
            <span class="info-value mono">₱{{ number_format($employee->hourly_rate, 4) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Minute Rate</span>
            <span class="info-value mono">₱{{ number_format($employee->minute_rate, 6) }}</span>
        </div>
        {{-- Semi-monthly gross hidden per requirement --}}
        {{-- <div class="info-row">
            <span class="info-label">Semi-monthly Gross</span>
            <span class="info-value bold">₱{{ number_format($employee->semi_monthly_gross, 2) }}</span>
        </div> --}}
    </div>
</div>

{{-- ── Tab: Government IDs ────────────────────────────────── --}}
<div class="tab-panel" id="tab-government">
    <div class="info-card">
        <div class="info-row">
            <span class="info-label">TIN</span>
            <span class="info-value mono">{{ $employee->tin ?: '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">GSIS No.</span>
            <span class="info-value mono">{{ $employee->gsis_bp_no ?: '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Pag-IBIG</span>
            <span class="info-value mono">{{ $employee->pagibig_no ?: '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">PhilHealth</span>
            <span class="info-value mono">{{ $employee->philhealth_no ?: '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">SSS No.</span>
            <span class="info-value mono">{{ $employee->sss_no ?: '—' }}</span>
        </div>
    </div>
</div>

<div class="footer-meta">
    <span>Record created: {{ $employee->created_at->format('M d, Y g:i A') }}</span>
    <span>Last updated: {{ $employee->updated_at->format('M d, Y g:i A') }}</span>
</div>

@endsection

@section('scripts')
<script>
// Tab switching functionality
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        // Remove active class from all tabs and panels
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

        // Add active class to clicked tab
        btn.classList.add('active');

        // Show corresponding panel
        const tabId = 'tab-' + btn.dataset.tab;
        document.getElementById(tabId).classList.add('active');
    });
});
</script>
@endsection

