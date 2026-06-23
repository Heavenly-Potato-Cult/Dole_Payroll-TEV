@extends('layouts.app')

@section('title', 'Allowances')
@section('page-title', 'Allowances')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Allowance Batches</h1>
        <p>Manage period-based allowance runs for individual or bulk employee assignments.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('payroll.allowances.types.index') }}" class="btn btn-outline">Allowance Types</a>
        <a href="{{ route('payroll.allowances.batches.create') }}" class="btn btn-primary">+ New Batch</a>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-body">
        <form method="GET" class="filter-form" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div>
                <label style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--text-mid);">Year</label>
                <select name="year" style="height:38px;">
                    <option value="">All</option>
                    @for ($y = $currentYear + 1; $y >= $currentYear - 3; $y--)
                        <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--text-mid);">Status</label>
                <select name="status" style="height:38px;">
                    <option value="">All</option>
                    @foreach (['draft','pending_review','approved','released'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Cutoff</th>
                    <th>Entries</th>
                    <th>Status</th>
                    <th>Prepared</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($batches as $batch)
                <tr>
                    <td>{{ \Carbon\Carbon::create($batch->period_year, $batch->period_month)->format('F Y') }}</td>
                    <td>{{ ucfirst($batch->cutoff) }}</td>
                    <td>{{ $batch->entries_count ?? $batch->entries->count() }}</td>
                    <td><span class="badge">{{ str_replace('_', ' ', ucfirst($batch->status)) }}</span></td>
                    <td>{{ $batch->prepared_at?->format('M d, Y') ?? '—' }}</td>
                    <td><a href="{{ route('payroll.allowances.batches.show', $batch) }}" class="btn btn-sm btn-outline">View</a></td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-light);">No allowance batches yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $batches->links() }}
@endsection
