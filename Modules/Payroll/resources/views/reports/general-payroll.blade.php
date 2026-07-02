@extends('layouts.app')

@section('styles')
<style>
.gp-banner {
    background: var(--navy);
    border-radius: 12px;
    padding: 1.5rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.gp-banner-left h1 { color: #fff; font-size: 1.5rem; font-weight: 700; margin: 0 0 0.2rem; }
.gp-banner-left p { color: rgba(255,255,255,0.55); margin: 0; font-size: 0.875rem; }
.gp-banner-form { display: flex; align-items: flex-end; gap: 0.75rem; flex-wrap: wrap; }
.gp-banner-form .form-group { display: flex; flex-direction: column; gap: 4px; }
.gp-banner-form label {
    color: rgba(255,255,255,0.65); font-size: 0.72rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.05em;
}
.gp-banner-form select {
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
    color: #fff; border-radius: 6px; padding: 0.45rem 0.75rem; font-size: 0.875rem;
    min-width: 130px; cursor: pointer;
}
.gp-banner-form select option { color: #111; background: #fff; }
.gp-banner-form select:focus { outline: none; border-color: var(--gold); }
.btn-apply {
    background: var(--gold); color: #1a1a2e; border: none; border-radius: 6px;
    padding: 0.5rem 1.25rem; font-size: 0.875rem; font-weight: 700; cursor: pointer;
    white-space: nowrap; transition: opacity 0.15s;
}
.btn-apply:hover { opacity: 0.85; }

.period-label {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
    border-radius: 20px; padding: 0.3rem 0.9rem; color: rgba(255,255,255,0.8);
    font-size: 0.8rem; font-weight: 600; margin-top: 0.5rem;
}
.period-label .cutoff-tag { background: var(--gold); color: #1a1a2e; border-radius: 10px; padding: 1px 8px; font-size: 0.7rem; }

.preview-stats-row { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.preview-stat-card {
    flex: 1; min-width: 200px; background: #fff; border: 1px solid var(--border, #e5e7eb);
    border-radius: 10px; padding: 1.1rem 1.4rem;
}
.preview-stat-card .ps-label { font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
.preview-stat-card .ps-value { font-size: 1.5rem; font-weight: 700; color: #1a1a2e; margin-top: 4px; }
.preview-stat-card .ps-value.gold { color: #b8860b; }

.gp-columns-card {
    background: #fff; border: 1px solid var(--border, #e5e7eb); border-radius: 10px;
    padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;
}
.gp-columns-card h4 { margin: 0 0 0.75rem; font-size: 0.95rem; }
.gp-chip-list { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.gp-chip {
    background: #f1f5f9; border-radius: 6px; padding: 0.3rem 0.7rem;
    font-size: 0.78rem; color: #334155;
}

.gp-download-card {
    background: linear-gradient(135deg, #1e3a5f 0%, #0f2040 100%);
    border-radius: 10px; padding: 1.25rem 1.5rem;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;
}
.gp-download-card p { color: rgba(255,255,255,0.7); margin: 0; font-size: 0.85rem; }
.btn-dl-gold {
    background: var(--gold); color: #1a1a2e; border-radius: 6px; padding: 0.6rem 1.4rem;
    font-weight: 700; font-size: 0.9rem; text-decoration: none; white-space: nowrap;
}
.alert-warning-gp {
    background: #fff7ed; border: 1px solid #fdba74; border-left: 4px solid #f97316;
    border-radius: 8px; padding: 1rem 1.25rem; color: #7c2d12; margin-bottom: 1.5rem;
}
</style>
@endsection

@section('content')

<div class="gp-banner">
    <div class="gp-banner-left">
        <h1>📊 General Payroll Register</h1>
        <p>DOLE RO9 official payroll register — one row per employee</p>
        <div class="period-label">
            <span>{{ $months[$month] }} {{ $year }}</span>
            @if ($cutoff !== 'both')
                <span class="cutoff-tag">{{ $cutoff === '1st' ? '1st Cut-off' : '2nd Cut-off' }}</span>
            @else
                <span class="cutoff-tag">Full Month</span>
            @endif
        </div>
    </div>

    <form method="GET" action="{{ route('reports.general-payroll') }}" class="gp-banner-form">
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
                <option value="1st"  {{ $cutoff === '1st'  ? 'selected' : '' }}>1st Cut-off (1–15)</option>
                <option value="2nd"  {{ $cutoff === '2nd'  ? 'selected' : '' }}>2nd Cut-off (16–31)</option>
            </select>
        </div>
        <button type="submit" class="btn-apply">Apply Filter</button>
    </form>
</div>

@if ($employeeCount === 0)
    <div class="alert-warning-gp">
        No payroll data found for <strong>{{ $months[$month] }} {{ $year }}</strong>
        @if ($cutoff !== 'both')
            ({{ $cutoff === '1st' ? '1st' : '2nd' }} cut-off)
        @endif.
        Generate and compute payroll batches for this period first.
    </div>
@else
    <div class="preview-stats-row">
        <div class="preview-stat-card">
            <div class="ps-label">Employees</div>
            <div class="ps-value">{{ number_format($employeeCount) }}</div>
        </div>
        <div class="preview-stat-card">
            <div class="ps-label">Gross Income</div>
            <div class="ps-value">₱{{ number_format($grandTotalGross, 2) }}</div>
        </div>
        <div class="preview-stat-card">
            <div class="ps-label">Net Amount</div>
            <div class="ps-value gold">₱{{ number_format($grandTotalNet, 2) }}</div>
        </div>
    </div>

    <div class="gp-columns-card">
        <h4>📋 Deduction columns included ({{ count($deductionColumns) }})</h4>
        <div class="gp-chip-list">
            @forelse ($deductionColumns as $col)
                <span class="gp-chip">{{ $col['name'] }}</span>
            @empty
                <span class="gp-chip">No deductions posted this period</span>
            @endforelse
        </div>
    </div>
@endif

<div class="gp-download-card">
    <div>
        <h4 style="color:#fff; margin:0 0 4px; font-size:0.95rem;">⬇ Download Excel Register</h4>
        <p>No., Name, Position, SG-Step, Basic Salary, PERA, RATA, Gross Income, all active deductions, Total Deductions, Net Amount — sorted by last name, with DOLE letterhead and signature block.</p>
    </div>
    <a href="{{ route('reports.general-payroll-download', ['year' => $year, 'month' => $month, 'cutoff' => $cutoff]) }}"
       class="btn-dl-gold">⬇ Download XLSX</a>
</div>

@endsection
