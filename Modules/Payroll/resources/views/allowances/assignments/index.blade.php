@extends('layouts.app')

@section('title', 'Allowances')
@section('page-title', 'Allowances')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Allowance Assignments</h1>
        <p>Manage period-based allowance assignments for individual or bulk employee assignments.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('payroll.allowances.types.index') }}" class="btn btn-outline">Allowance Types</a>
        <a href="{{ route('payroll.allowances.assignments.create') }}" class="btn btn-primary">+ New Assignment</a>
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
                    <th>Status</th>
                    <th style="text-align:right;">Entries</th>
                    <th style="text-align:right;">Total Amount</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assignments as $assignment)
                @php
                    $entriesCount = $assignment->entries_count ?? $assignment->entries->count();
                    $totalAmount  = $assignment->entries_sum_amount ?? $assignment->entries->sum('amount');
                    $periodLabel  = \Carbon\Carbon::create($assignment->period_year, $assignment->period_month)->format('F Y');
                    $statusColors = [
                        'draft'    => ['bg' => '#f1f5f9', 'color' => '#475569'],
                        'released' => ['bg' => '#dcfce7', 'color' => '#16a34a'],
                    ];
                    $sc = $statusColors[$assignment->status] ?? ['bg' => '#f1f5f9', 'color' => '#64748b'];
                @endphp
                <tr>
                    <td style="font-weight:600;">{{ $periodLabel }}</td>
                    <td>{{ ucfirst($assignment->cutoff) }}</td>
                    <td>
                        <span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:{{ $sc['bg'] }};color:{{ $sc['color'] }};">
                            {{ ucfirst($assignment->status) }}
                        </span>
                    </td>
                    <td style="text-align:right;">{{ number_format($entriesCount) }}</td>
                    <td style="text-align:right;font-weight:600;">₱{{ number_format($totalAmount, 2) }}</td>
                    <td style="color:var(--text-light);font-size:0.85rem;">{{ $assignment->created_at?->format('M d, Y') ?? '—' }}</td>
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                            <a href="{{ route('payroll.allowances.assignments.show', $assignment) }}" class="btn btn-sm btn-outline">View</a>
                            @if ($assignment->status === 'draft')
                                <form method="POST"
                                      action="{{ route('payroll.allowances.assignments.destroy', $assignment) }}"
                                      onsubmit="return confirm('Delete this draft assignment ({{ $periodLabel }}, {{ ucfirst($assignment->cutoff) }}) and its {{ $entriesCount }} entries? This cannot be undone.');"
                                      style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline" style="color:#dc2626;border-color:#dc2626;">Delete</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--text-light);">No allowance assignments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $assignments->links() }}
@endsection
