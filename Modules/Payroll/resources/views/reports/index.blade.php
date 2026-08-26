@extends('layouts.app')

@section('page-title', 'Reports')

@section('styles')
<style>
/* ── Tab Navigation ───────────────────────────────────────────── */
.reports-tabs {
    display: flex;
    gap: 4px;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 1.5rem;
    overflow-x: auto;
    padding-bottom: 0;
}

.tab-btn {
    padding: 0.75rem 1.25rem;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s;
    border-radius: 6px 6px 0 0;
}

.tab-btn:hover {
    background: #f8fafc;
    color: var(--navy);
}

.tab-btn.active {
    background: #f1f5f9;
    color: var(--navy);
    border-bottom-color: var(--navy);
}

/* ── Tab Content ─────────────────────────────────────────────── */
.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* ── Filter Form ─────────────────────────────────────────────── */
.filter-form {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.filter-form .form-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.filter-form label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.filter-form select {
    padding: 0.5rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.875rem;
    min-width: 130px;
    cursor: pointer;
    height: 38px;
    box-sizing: border-box;
}

.filter-form select:focus {
    outline: none;
    border-color: var(--navy);
}

.btn-filter {
    background: var(--navy);
    color: #fff;
    border: none;
    padding: 0.5rem 1.25rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s;
    height: 38px;
    box-sizing: border-box;
}

.btn-filter:hover {
    opacity: 0.85;
}

/* ── Stats Grid ─────────────────────────────────────────────── */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-left: 4px solid var(--navy);
    border-radius: 8px;
    padding: 1rem 1.25rem;
}

.stat-card.gold {
    border-left-color: var(--gold);
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
}

.stat-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--navy);
}

.stat-card.gold .stat-value {
    color: #92400e;
}

/* ── Report Cards ───────────────────────────────────────────── */
.report-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

.report-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    transition: box-shadow 0.15s, transform 0.15s;
}

.report-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.report-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    gap: 0.75rem;
}

.report-card-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--navy);
    margin: 0;
    line-height: 1.4;
}

.report-card-desc {
    font-size: 0.8rem;
    color: #64748b;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.report-card-desc a {
    color: var(--navy);
    text-decoration: underline;
}

.format-badge {
    display: inline-block;
    background: #dcfce7;
    color: #166534;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 4px;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    white-space: nowrap;
}

.format-badge.csv {
    background: #e0f2fe;
    color: #075985;
}

.report-card-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: auto;
}

.report-card-actions .btn-dl {
    font-size: 0.8rem;
    padding: 0.4rem 0.85rem;
}

/* ── Category Header ─────────────────────────────────────────── */
.category-header {
    font-size: 0.85rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 1.5rem 0 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e2e8f0;
}

.category-header:first-child {
    margin-top: 0;
}

/* ── Action Buttons ─────────────────────────────────────────── */
.btn-dl {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--navy);
    color: #fff;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.15s;
    white-space: nowrap;
}

.btn-dl:hover {
    opacity: 0.85;
    color: #fff;
}

.btn-dl-gold {
    background: var(--gold);
    color: #1a1a2e;
}

.btn-dl-gold:hover {
    color: #1a1a2e;
}

.btn-dl-csv {
    background: #0369a1;
}

.d-flex {
    display: flex;
}

.gap-2 {
    gap: 0.5rem;
}

.flex-wrap {
    flex-wrap: wrap;
}

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 768px) {
    .reports-tabs {
        flex-wrap: nowrap;
        -webkit-overflow-scrolling: touch;
    }

    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-form select,
    .btn-filter {
        width: 100%;
    }
}
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>📊 Reports</h1>
        <p class="text-muted">Generate and download payroll remittance reports</p>
    </div>
</div>

{{-- ── Filter Form (Shared) ─────────────────────────────────────── --}}
<form method="GET" action="{{ route('reports.index') }}" class="filter-form">
    <input type="hidden" name="tab" id="tab-input" value="{{ $activeTab }}">

    <div class="form-group">
        <label for="year">Year</label>
        <select name="year" id="year">
            @for ($y = $currentYear; $y >= 2020; $y--)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </div>

    <div class="form-group">
        <label for="month">Month</label>
        <select name="month" id="month">
            @foreach ($months as $num => $name)
                <option value="{{ $num }}" {{ $num == $month ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="cutoff">Cut-off</label>
        <select name="cutoff" id="cutoff">
            <option value="both" {{ $cutoff === 'both' ? 'selected' : '' }}>Both (Full Month)</option>
            <option value="1st" {{ $cutoff === '1st' ? 'selected' : '' }}>1st Cut-off (1–15)</option>
            <option value="2nd" {{ $cutoff === '2nd' ? 'selected' : '' }}>2nd Cut-off (16–31)</option>
        </select>
    </div>

    <div class="form-group">
        <label>&nbsp;</label>
        <button type="submit" class="btn-filter">Apply Filter</button>
    </div>
</form>

{{-- ── Tab Navigation ───────────────────────────────────────────── --}}
<div class="reports-tabs">
    <button class="tab-btn {{ $activeTab === 'gsis' ? 'active' : '' }}" onclick="switchTab('gsis')">GSIS</button>
    <button class="tab-btn {{ $activeTab === 'hdmf' ? 'active' : '' }}" onclick="switchTab('hdmf')">HDMF / Pag-IBIG</button>
    <button class="tab-btn {{ $activeTab === 'general_payroll' ? 'active' : '' }}" onclick="switchTab('general_payroll')">General Payroll</button>
    <button class="tab-btn {{ $activeTab === 'phic' ? 'active' : '' }}" onclick="switchTab('phic')">PhilHealth</button>
    <button class="tab-btn {{ $activeTab === 'caress_union' ? 'active' : '' }}" onclick="switchTab('caress_union')">CARESS IX (Union)</button>
    <button class="tab-btn {{ $activeTab === 'caress_mortuary' ? 'active' : '' }}" onclick="switchTab('caress_mortuary')">CARESS IX (Mortuary)</button>
    <button class="tab-btn {{ $activeTab === 'mass' ? 'active' : '' }}" onclick="switchTab('mass')">MASS</button>
    <button class="tab-btn {{ $activeTab === 'provident_fund' ? 'active' : '' }}" onclick="switchTab('provident_fund')">Provident Fund</button>
    <button class="tab-btn {{ $activeTab === 'lbp' ? 'active' : '' }}" onclick="switchTab('lbp')">LBP Loan</button>
    <button class="tab-btn {{ $activeTab === 'btr' ? 'active' : '' }}" onclick="switchTab('btr')">BTR</button>
    <button class="tab-btn {{ $activeTab === 'sss' ? 'active' : '' }}" onclick="switchTab('sss')">SSS Voluntary</button>
</div>

{{-- ── GSIS Tab Content ─────────────────────────────────────────── --}}
<div class="tab-content {{ $activeTab === 'gsis' ? 'active' : '' }}" id="tab-gsis">
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Employees Included</div>
            <div class="stat-value">{{ number_format($employeeCount ?? 0) }}</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-label">Grand Total (GSIS)</div>
            <div class="stat-value">₱{{ number_format($grandTotal ?? 0, 2) }}</div>
        </div>
    </div>

    @if (isset($totals) && $employeeCount > 0)
    <div class="category-header">Life / Retirement Premium</div>
    <div class="report-cards-grid">
        <div class="report-card">
            <div class="report-card-header">
                <h4 class="report-card-title">GSIS Summary Report</h4>
                <span class="format-badge">XLSX</span>
            </div>
            <p class="report-card-desc">
                Summary of GSIS deductions by division subtotals. Includes Life/Retirement Premium Personal Share.
            </p>
            <div class="report-card-actions">
                <a href="{{ route('reports.gsis-summary', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff]) }}"
                   class="btn-dl">
                    ⬇ Download Summary
                </a>
            </div>
        </div>
    </div>

    <div class="category-header">Loans</div>
    <div class="report-cards-grid">
        <div class="report-card">
            <div class="report-card-header">
                <h4 class="report-card-title">GSIS Detailed Report</h4>
                <span class="format-badge">XLSX</span>
            </div>
            <p class="report-card-desc">
                Detailed breakdown of all GSIS deductions including Emergency Loan, Educational Assistance, MPL, Consolidated Loan, HELP, GFAL, CPL, Policy Loan, and Real Estate Loan.
            </p>
            <div class="report-card-actions">
                <a href="{{ route('reports.gsis-detailed', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff]) }}"
                   class="btn-dl btn-dl-gold">
                    ⬇ Download Detailed
                </a>
            </div>
        </div>
    </div>

    {{-- Deduction Breakdown Table --}}
    <div class="report-card">
        <div class="report-card-header">
            <h4 class="report-card-title">Deduction Breakdown — {{ $months[$month] }} {{ $year }}
                @if ($cutoff !== 'both')
                    <span style="font-size:0.7rem; background:var(--gold); color:#1a1a2e; padding:2px 8px; border-radius:10px; margin-left:8px;">
                        {{ $cutoff === '1st' ? '1st Cut-off' : '2nd Cut-off' }}
                    </span>
                @endif
            </h4>
        </div>
        <div style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">Code</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">GSIS Account</th>
                        <th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; color: var(--navy);">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($labelMap as $code => $label)
                        @if (isset($totals[$code]) && $totals[$code] > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.75rem 1rem;"><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;">{{ $code }}</code></td>
                            <td style="padding: 0.75rem 1rem;">{{ $label }}</td>
                            <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">
                                ₱{{ number_format($totals[$code], 2) }}
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                        <td colspan="2" style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">GRAND TOTAL</td>
                        <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: #92400e;">₱{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @else
    <div class="alert alert-warning">
        No payroll data found for <strong>{{ $months[$month] }} {{ $year }}</strong>
        @if ($cutoff !== 'both')
            ({{ $cutoff === '1st' ? '1st' : '2nd' }} cut-off)
        @endif.
        Generate and compute payroll batches for this period first.
    </div>
    @endif
</div>

{{-- ── HDMF Tab Content ─────────────────────────────────────────── --}}
<div class="tab-content {{ $activeTab === 'hdmf' ? 'active' : '' }}" id="tab-hdmf">
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">P1 Contributors</div>
            <div class="stat-value">{{ number_format($employeeCount ?? 0) }}</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-label">Grand Total (All Sheets)</div>
            <div class="stat-value">₱{{ number_format($grandTotal ?? 0, 2) }}</div>
        </div>
    </div>

    @if (isset($sheets) && $grandTotal > 0)
    <div class="category-header">All HDMF Reports (5 Sheets)</div>
    <div class="report-cards-grid">
        <div class="report-card">
            <div class="report-card-header">
                <h4 class="report-card-title">HDMF Combined Remittance</h4>
                <span class="format-badge">XLSX</span>
            </div>
            <p class="report-card-desc">
                Downloads one Excel file containing all 5 sheets (P1, P2, MPL, CAL, Housing) formatted for direct HDMF portal upload.
            </p>
            <div class="report-card-actions">
                <a href="{{ route('reports.hdmf-download', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff]) }}"
                   class="btn-dl">
                    ⬇ Download All (5 sheets)
                </a>
            </div>
        </div>
    </div>

    {{-- Sheet Breakdown Table --}}
    <div class="report-card">
        <div class="report-card-header">
            <h4 class="report-card-title">Sheet Breakdown — {{ $months[$month] }} {{ $year }}
                @if ($cutoff !== 'both')
                    <span style="font-size:0.7rem; background:var(--gold); color:#1a1a2e; padding:2px 8px; border-radius:10px; margin-left:8px;">
                        {{ $cutoff === '1st' ? '1st Cut-off' : '2nd Cut-off' }}
                    </span>
                @endif
            </h4>
        </div>
        <div style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">Sheet</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">Program</th>
                        <th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; color: var(--navy);">Employee Count</th>
                        <th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; color: var(--navy);">EE Share Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sheets as $sheet)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 0.75rem 1rem;">{{ $sheet['label'] }}</td>
                        <td style="padding: 0.75rem 1rem;"><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;">{{ $sheet['program'] }}</code></td>
                        <td style="padding: 0.75rem 1rem; text-align: right;">{{ number_format($sheet['count']) }}</td>
                        <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">
                            @if ($sheet['total'] > 0)
                                ₱{{ number_format($sheet['total'], 2) }}
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                        <td colspan="3" style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">GRAND TOTAL</td>
                        <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: #92400e;">₱{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @else
    <div class="alert alert-warning">
        No HDMF payroll data found for <strong>{{ $months[$month] }} {{ $year }}</strong>
        @if ($cutoff !== 'both')
            ({{ $cutoff === '1st' ? '1st' : '2nd' }} cut-off)
        @endif.
        Generate and compute payroll batches for this period first.
    </div>
    @endif
</div>

{{-- ── General Payroll Tab Content ──────────────────────────────── --}}
<div class="tab-content {{ $activeTab === 'general_payroll' ? 'active' : '' }}" id="tab-general_payroll">
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Employees</div>
            <div class="stat-value">{{ number_format($employeeCount ?? 0) }}</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-label">Grand Total (Net Amount)</div>
            <div class="stat-value">₱{{ number_format($grandTotal ?? 0, 2) }}</div>
        </div>
    </div>

    <div class="report-cards-grid">
        <div class="report-card">
            <div class="report-card-header">
                <h4 class="report-card-title">General Payroll Register</h4>
                <span class="format-badge">XLSX</span>
            </div>
            <p class="report-card-desc">
                Official DOLE RO9 payroll register — one row per employee with Basic Salary, PERA, RATA,
                Gross Income, every active deduction, Total Deductions, and Net Amount. Includes letterhead
                and signature block.
            </p>
            <div class="report-card-actions">
                <a href="{{ route('reports.general-payroll', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff]) }}"
                   class="btn-dl">
                    👁 Open Full Report
                </a>
                <a href="{{ route('reports.general-payroll-download', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff]) }}"
                   class="btn-dl btn-dl-gold">
                    ⬇ Download XLSX
                </a>
            </div>
        </div>
    </div>

    @if (($employeeCount ?? 0) === 0)
    <div class="alert alert-warning">
        No payroll data found for <strong>{{ $months[$month] }} {{ $year }}</strong>
        @if ($cutoff !== 'both')
            ({{ $cutoff === '1st' ? '1st' : '2nd' }} cut-off)
        @endif.
        Generate and compute payroll batches for this period first.
    </div>
    @endif
</div>

{{-- ── PhilHealth Tab Content ───────────────────────────────────── --}}
<div class="tab-content {{ $activeTab === 'phic' ? 'active' : '' }}" id="tab-phic">
    <div class="report-cards-grid">
        <div class="report-card">
            <div class="report-card-header">
                <h4 class="report-card-title">PhilHealth (PHIC) Contributions</h4>
                <span class="format-badge csv">CSV</span>
            </div>
            <p class="report-card-desc">
                Extracted from the system. Generate PDF Billing and PHIC Remittance from the
                <a href="https://www.philhealth.gov.ph" target="_blank" rel="noopener">PHIC Employer Portal</a>.
            </p>
            <div class="report-card-actions">
                <a href="{{ route('reports.phic-csv', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff]) }}"
                   class="btn-dl btn-dl-csv">
                    ⬇ Download CSV
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ── CARESS Union Tab Content ─────────────────────────────────── --}}
<div class="tab-content {{ $activeTab === 'caress_union' ? 'active' : '' }}" id="tab-caress_union">
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Employees</div>
            <div class="stat-value">{{ number_format($employeeCount ?? 0) }}</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-label">Total Amount</div>
            <div class="stat-value">₱{{ number_format($grandTotal ?? 0, 2) }}</div>
        </div>
    </div>

    <div class="report-cards-grid">
        <div class="report-card">
            <div class="report-card-header">
                <h4 class="report-card-title">CARESS IX Union Dues</h4>
                <span class="format-badge">XLSX</span>
            </div>
            <p class="report-card-desc">
                CARESS 9 monthly union dues — Payee: DOLE-CARESS9
            </p>
            <div class="report-card-actions">
                <a href="{{ route('reports.caress-union', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff, 'download' => 1]) }}"
                   class="btn-dl">
                    ⬇ Download
                </a>
            </div>
        </div>
    </div>

    @if (isset($reportRows) && $reportRows->count() > 0)
    <div class="report-card">
        <div class="report-card-header">
            <h4 class="report-card-title">Deduction Rows</h4>
            <span style="font-size:0.78rem; color:#64748b;">{{ $reportRows->count() }} record(s)</span>
        </div>
        <div style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">#</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">Employee</th>
                        <th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; color: var(--navy);">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportRows as $i => $ded)
                        @php $emp = $ded->entry->employee; @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.75rem 1rem;">{{ $i + 1 }}</td>
                            <td style="padding: 0.75rem 1rem;">{{ strtoupper($emp->last_name . ', ' . $emp->first_name) }}</td>
                            <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">₱{{ number_format($ded->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                        <td colspan="2" style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">GRAND TOTAL</td>
                        <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: #92400e;">₱{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @elseif (isset($reportRows))
    <div class="alert alert-warning">
        No payroll data found for <strong>{{ $months[$month] }} {{ $year }}</strong>
        @if ($cutoff !== 'both')
            ({{ $cutoff === '1st' ? '1st' : '2nd' }} cut-off)
        @endif.
        Generate and compute payroll batches for this period first.
    </div>
    @endif
</div>

{{-- ── CARESS Mortuary Tab Content ──────────────────────────────── --}}
<div class="tab-content {{ $activeTab === 'caress_mortuary' ? 'active' : '' }}" id="tab-caress_mortuary">
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Employees</div>
            <div class="stat-value">{{ number_format($employeeCount ?? 0) }}</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-label">Total Amount</div>
            <div class="stat-value">₱{{ number_format($grandTotal ?? 0, 2) }}</div>
        </div>
    </div>

    <div class="report-cards-grid">
        <div class="report-card">
            <div class="report-card-header">
                <h4 class="report-card-title">CARESS IX Mortuary Benefit</h4>
                <span class="format-badge">XLSX</span>
            </div>
            <p class="report-card-desc">
                Death benefit schedule — Daily Rate × (0.25 + 0.25 + 0.50)
            </p>
            <div class="report-card-actions">
                <a href="{{ route('reports.caress-mortuary', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff, 'download' => 1]) }}"
                   class="btn-dl">
                    ⬇ Download
                </a>
            </div>
        </div>
    </div>

    @if (isset($reportRows) && $reportRows->count() > 0)
    <div class="report-card">
        <div class="report-card-header">
            <h4 class="report-card-title">Deduction Rows</h4>
            <span style="font-size:0.78rem; color:#64748b;">{{ $reportRows->count() }} record(s)</span>
        </div>
        <div style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">#</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">Employee</th>
                        <th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; color: var(--navy);">Daily Rate</th>
                        <th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; color: var(--navy);">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportRows as $i => $ded)
                        @php $emp = $ded->entry->employee; $dr = round(($emp->semi_monthly_gross * 2) / 22, 2); @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.75rem 1rem;">{{ $i + 1 }}</td>
                            <td style="padding: 0.75rem 1rem;">{{ strtoupper($emp->last_name . ', ' . $emp->first_name) }}</td>
                            <td style="padding: 0.75rem 1rem; text-align: right;">₱{{ number_format($dr, 2) }}</td>
                            <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">₱{{ number_format($ded->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                        <td colspan="3" style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">GRAND TOTAL</td>
                        <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: #92400e;">₱{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @elseif (isset($reportRows))
    <div class="alert alert-warning">
        No payroll data found for <strong>{{ $months[$month] }} {{ $year }}</strong>
        @if ($cutoff !== 'both')
            ({{ $cutoff === '1st' ? '1st' : '2nd' }} cut-off)
        @endif.
        Generate and compute payroll batches for this period first.
    </div>
    @endif
</div>

{{-- ── MASS Tab Content ─────────────────────────────────────────── --}}
<div class="tab-content {{ $activeTab === 'mass' ? 'active' : '' }}" id="tab-mass">
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Employees</div>
            <div class="stat-value">{{ number_format($employeeCount ?? 0) }}</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-label">Total Amount</div>
            <div class="stat-value">₱{{ number_format($grandTotal ?? 0, 2) }}</div>
        </div>
    </div>

    <div class="report-cards-grid">
        <div class="report-card">
            <div class="report-card-header">
                <h4 class="report-card-title">MASS Contribution</h4>
                <span class="format-badge">XLSX</span>
            </div>
            <p class="report-card-desc">
                Payee: Warren M. Miclat and Maria Teresa M. Cabance<br>
                c/o Bureau of Labor Relations, Intramuros, Manila
            </p>
            <div class="report-card-actions">
                <a href="{{ route('reports.mass', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff, 'download' => 1]) }}"
                   class="btn-dl">
                    ⬇ Download
                </a>
            </div>
        </div>
    </div>

    @if (isset($reportRows) && $reportRows->count() > 0)
    <div class="report-card">
        <div class="report-card-header">
            <h4 class="report-card-title">Deduction Rows</h4>
            <span style="font-size:0.78rem; color:#64748b;">{{ $reportRows->count() }} record(s)</span>
        </div>
        <div style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">#</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">Employee</th>
                        <th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; color: var(--navy);">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportRows as $i => $ded)
                        @php $emp = $ded->entry->employee; @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.75rem 1rem;">{{ $i + 1 }}</td>
                            <td style="padding: 0.75rem 1rem;">{{ strtoupper($emp->last_name . ', ' . $emp->first_name) }}</td>
                            <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">₱{{ number_format($ded->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                        <td colspan="2" style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">GRAND TOTAL</td>
                        <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: #92400e;">₱{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @elseif (isset($reportRows))
    <div class="alert alert-warning">
        No payroll data found for <strong>{{ $months[$month] }} {{ $year }}</strong>
        @if ($cutoff !== 'both')
            ({{ $cutoff === '1st' ? '1st' : '2nd' }} cut-off)
        @endif.
        Generate and compute payroll batches for this period first.
    </div>
    @endif
</div>

{{-- ── Provident Fund Tab Content ──────────────────────────────── --}}
<div class="tab-content {{ $activeTab === 'provident_fund' ? 'active' : '' }}" id="tab-provident_fund">
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Employees</div>
            <div class="stat-value">{{ number_format($employeeCount ?? 0) }}</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-label">Total Amount</div>
            <div class="stat-value">₱{{ number_format($grandTotal ?? 0, 2) }}</div>
        </div>
    </div>

    <div class="report-cards-grid">
        <div class="report-card">
            <div class="report-card-header">
                <h4 class="report-card-title">DOLE Provident Fund</h4>
                <span class="format-badge">XLSX</span>
            </div>
            <p class="report-card-desc">
                Payee: DOLEPFI Inc. — Account No. 2471-0431-01 · Land Bank of the Philippines
            </p>
            <div class="report-card-actions">
                <a href="{{ route('reports.provident-fund', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff, 'download' => 1]) }}"
                   class="btn-dl">
                    ⬇ Download
                </a>
            </div>
        </div>
    </div>

    @if (isset($reportRows) && $reportRows->count() > 0)
    <div class="report-card">
        <div class="report-card-header">
            <h4 class="report-card-title">Deduction Rows</h4>
            <span style="font-size:0.78rem; color:#64748b;">{{ $reportRows->count() }} record(s)</span>
        </div>
        <div style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">#</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">Employee</th>
                        <th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; color: var(--navy);">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportRows as $i => $ded)
                        @php $emp = $ded->entry->employee; @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.75rem 1rem;">{{ $i + 1 }}</td>
                            <td style="padding: 0.75rem 1rem;">{{ strtoupper($emp->last_name . ', ' . $emp->first_name) }}</td>
                            <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">₱{{ number_format($ded->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                        <td colspan="2" style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">GRAND TOTAL</td>
                        <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: #92400e;">₱{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @elseif (isset($reportRows))
    <div class="alert alert-warning">
        No payroll data found for <strong>{{ $months[$month] }} {{ $year }}</strong>
        @if ($cutoff !== 'both')
            ({{ $cutoff === '1st' ? '1st' : '2nd' }} cut-off)
        @endif.
        Generate and compute payroll batches for this period first.
    </div>
    @endif
</div>

{{-- ── LBP Loan Tab Content ─────────────────────────────────────── --}}
<div class="tab-content {{ $activeTab === 'lbp' ? 'active' : '' }}" id="tab-lbp">
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Employees</div>
            <div class="stat-value">{{ number_format($employeeCount ?? 0) }}</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-label">Total Amount</div>
            <div class="stat-value">₱{{ number_format($grandTotal ?? 0, 2) }}</div>
        </div>
    </div>

    <div class="report-cards-grid">
        <div class="report-card">
            <div class="report-card-header">
                <h4 class="report-card-title">LBP Loan Remittance</h4>
                <span class="format-badge">XLSX</span>
            </div>
            <p class="report-card-desc">
                Landbank of the Philippines loan deductions
            </p>
            <div class="report-card-actions">
                <a href="{{ route('reports.lbp-loan', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff, 'download' => 1]) }}"
                   class="btn-dl">
                    ⬇ Download
                </a>
            </div>
        </div>
    </div>

    @if (isset($reportRows) && $reportRows->count() > 0)
    <div class="report-card">
        <div class="report-card-header">
            <h4 class="report-card-title">Deduction Rows</h4>
            <span style="font-size:0.78rem; color:#64748b;">{{ $reportRows->count() }} record(s)</span>
        </div>
        <div style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">#</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">Employee</th>
                        <th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; color: var(--navy);">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportRows as $i => $ded)
                        @php $emp = $ded->entry->employee; @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.75rem 1rem;">{{ $i + 1 }}</td>
                            <td style="padding: 0.75rem 1rem;">{{ strtoupper($emp->last_name . ', ' . $emp->first_name) }}</td>
                            <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">₱{{ number_format($ded->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                        <td colspan="2" style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">GRAND TOTAL</td>
                        <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: #92400e;">₱{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @elseif (isset($reportRows))
    <div class="alert alert-warning">
        No payroll data found for <strong>{{ $months[$month] }} {{ $year }}</strong>
        @if ($cutoff !== 'both')
            ({{ $cutoff === '1st' ? '1st' : '2nd' }} cut-off)
        @endif.
        Generate and compute payroll batches for this period first.
    </div>
    @endif
</div>

{{-- ── BTR Tab Content ──────────────────────────────────────────── --}}
<div class="tab-content {{ $activeTab === 'btr' ? 'active' : '' }}" id="tab-btr">
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Employees</div>
            <div class="stat-value">{{ number_format($employeeCount ?? 0) }}</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-label">Total Amount</div>
            <div class="stat-value">₱{{ number_format($grandTotal ?? 0, 2) }}</div>
        </div>
    </div>

    <div class="report-cards-grid">
        <div class="report-card">
            <div class="report-card-header">
                <h4 class="report-card-title">Bureau of Treasury (BTR) Refund</h4>
                <span class="format-badge">XLSX</span>
            </div>
            <p class="report-card-desc">
                Withholding Tax and other refunds payable to Bureau of Treasury
            </p>
            <div class="report-card-actions">
                <a href="{{ route('reports.btr-refund', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff, 'download' => 1]) }}"
                   class="btn-dl">
                    ⬇ Download
                </a>
            </div>
        </div>
    </div>

    @if (isset($reportRows) && $reportRows->count() > 0)
    <div class="report-card">
        <div class="report-card-header">
            <h4 class="report-card-title">Deduction Rows</h4>
            <span style="font-size:0.78rem; color:#64748b;">{{ $reportRows->count() }} record(s)</span>
        </div>
        <div style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">#</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">Employee</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: var(--navy);">Reason of Refund</th>
                        <th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; color: var(--navy);">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportRows as $i => $ded)
                        @php $emp = $ded->entry->employee; @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.75rem 1rem;">{{ $i + 1 }}</td>
                            <td style="padding: 0.75rem 1rem;">{{ strtoupper($emp->last_name . ', ' . $emp->first_name) }}</td>
                            <td style="padding: 0.75rem 1rem;">{{ $ded->deductionType->name ?? '—' }}</td>
                            <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">₱{{ number_format($ded->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                        <td colspan="3" style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: var(--navy);">GRAND TOTAL</td>
                        <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: #92400e;">₱{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @elseif (isset($reportRows))
    <div class="alert alert-warning">
        No payroll data found for <strong>{{ $months[$month] }} {{ $year }}</strong>
        @if ($cutoff !== 'both')
            ({{ $cutoff === '1st' ? '1st' : '2nd' }} cut-off)
        @endif.
        Generate and compute payroll batches for this period first.
    </div>
    @endif
</div>

{{-- ── SSS Voluntary Tab Content ───────────────────────────────── --}}
<div class="tab-content {{ $activeTab === 'sss' ? 'active' : '' }}" id="tab-sss">
    <div class="report-cards-grid">
        <div class="report-card">
            <div class="report-card-header">
                <h4 class="report-card-title">SSS Voluntary Contributions</h4>
                <span class="format-badge csv">CSV</span>
            </div>
            <p class="report-card-desc">
                Extracted from the system. Generate PDF Billing and SSS Remittance from the
                <a href="https://www.sss.gov.ph" target="_blank" rel="noopener">SSS Employer Portal</a>.
            </p>
            <div class="report-card-actions">
                <a href="{{ route('reports.sss', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff]) }}"
                   class="btn-dl btn-dl-csv">
                    ⬇ Download CSV
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function switchTab(tabName) {
    // Update hidden input
    document.getElementById('tab-input').value = tabName;

    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');

    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById('tab-' + tabName).classList.add('active');

    // Submit form to load data for the tab
    document.querySelector('.filter-form').submit();
}
</script>
@endsection
