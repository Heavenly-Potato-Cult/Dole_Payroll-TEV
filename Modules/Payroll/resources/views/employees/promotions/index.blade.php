@extends('layouts.app')

@section('title', 'Promotion History — ' . $employee->full_name)
@section('page-title', 'Promotion History')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Promotion History</h1>
        <p>{{ $employee->full_name }} &mdash; {{ $employee->position_title }}</p>
    </div>
    <div class="d-flex gap-2">
        @can(\App\SharedKernel\Enums\Permission::EMPLOYEES_MANAGE->value)
        <a href="{{ route('employees.promotions.create', $employee) }}" class="btn btn-primary">
            + Add Record
        </a>
        @endrole
        <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline">← Profile</a>
    </div>
</div>

{{-- Current salary snapshot --}}
<div class="stat-grid" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-label">Current SG / Step</div>
        <div class="stat-value">{{ $employee->salary_grade }}-{{ $employee->step }}</div>
        <div class="stat-sub">CY {{ $employee->sit_year }}</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-label">Basic Salary</div>
        <div class="stat-value" style="font-size:1.4rem;">₱{{ number_format($employee->basic_salary, 2) }}</div>
        <div class="stat-sub">Monthly</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Last Promotion</div>
        <div class="stat-value" style="font-size:1.1rem;">
            {{ $employee->last_promotion_date ? $employee->last_promotion_date->format('M d, Y') : '—' }}
        </div>
        <div class="stat-sub">{{ $history->count() }} {{ Str::plural('record', $history->count()) }}</div>
    </div>
</div>

{{-- Timeline --}}
<div class="card">
    <div class="card-header">
        <h3>History Timeline</h3>
        <span class="text-muted" style="font-size:0.82rem;">Most recent first</span>
    </div>

    @if ($history->isEmpty())
        <div class="card-body" style="text-align:center;padding:40px;color:var(--text-light);">
            No promotion records yet.
            @can(\App\SharedKernel\Enums\Permission::EMPLOYEES_MANAGE->value)
            <a href="{{ route('employees.promotions.create', $employee) }}">Add the first record →</a>
            @endrole
        </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Effective Date</th>
                    <th>Type</th>
                    <th style="text-align:center;">Old SG-Step</th>
                    <th style="text-align:right;">Old Salary</th>
                    <th style="text-align:center;">New SG-Step</th>
                    <th style="text-align:right;">New Salary</th>
                    <th style="text-align:right;">Differential</th>
                    <th>Remarks</th>
                    <th style="width:80px;text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($history as $i => $rec)
                <tr style="{{ $i === 0 ? 'background:var(--gold-light);' : '' }}">
                    <td style="font-weight:600;">
                        {{ $rec->effective_date->format('M d, Y') }}
                        @if ($i === 0)
                            <span class="badge badge-active" style="margin-left:6px;font-size:0.60rem;">Latest</span>
                        @endif
                    </td>
                    <td>
                        @php $typeColors = ['promotion'=>'badge-active','step_increment'=>'badge-computed','adjustment'=>'badge-pending']; @endphp
                        <span class="badge {{ $typeColors[$rec->type] ?? 'badge-draft' }}">
                            {{ $rec->getTypeLabel() }}
                        </span>
                    </td>
                    <td style="text-align:center;font-family:monospace;">
                        SG{{ $rec->old_sg }}-{{ $rec->old_step }}
                    </td>
                    <td style="text-align:right;font-family:monospace;">
                        ₱{{ number_format($rec->old_salary, 2) }}
                    </td>
                    <td style="text-align:center;font-family:monospace;font-weight:700;color:var(--navy);">
                        SG{{ $rec->new_sg }}-{{ $rec->new_step }}
                    </td>
                    <td style="text-align:right;font-family:monospace;font-weight:700;color:var(--navy);">
                        ₱{{ number_format($rec->new_salary, 2) }}
                    </td>
                    <td style="text-align:right;font-family:monospace;
                                color:{{ $rec->differential >= 0 ? 'var(--success)' : 'var(--red)' }};">
                        +₱{{ number_format($rec->differential, 2) }}
                    </td>
                    <td style="font-size:0.82rem;color:var(--text-mid);">
                        {{ $rec->remarks ?? '—' }}
                    </td>
                    <td style="text-align:center;">
                        @can(\App\SharedKernel\Enums\Permission::EMPLOYEES_MANAGE->value)
                        @if ($i === 0)
                        <form method="POST"
                              action="{{ route('employees.promotions.destroy', [$employee, $rec]) }}"
                              onsubmit="return confirm('Delete this promotion record?\nThe employee salary will be restored to the previous values.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">✕</button>
                        </form>
                        @else
                            <span class="text-muted" style="font-size:0.76rem;">—</span>
                        @endif
                        @endrole
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection