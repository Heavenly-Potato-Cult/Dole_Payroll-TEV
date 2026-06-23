@extends('layouts.app')

@section('title', 'Employee Allowances')
@section('page-title', 'Employees')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>{{ $employee->full_name }}</h1>
        <p>Standing allowance enrollments for this employee.</p>
    </div>
    <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline">← Back to Profile</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('employees.allowances.update', $employee) }}">
            @csrf
            <table class="table">
                <thead>
                    <tr>
                        <th>Enabled</th>
                        <th>Allowance</th>
                        <th>Amount (₱)</th>
                        <th>Effectivity</th>
                        <th>Expiry</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($types as $type)
                    @php $enrollment = $enrollments->get($type->id); @endphp
                    <tr>
                        <td>
                            <input type="checkbox" name="allowances[{{ $type->id }}][enabled]" value="1"
                                @checked(old("allowances.{$type->id}.enabled", (bool) $enrollment))>
                        </td>
                        <td>
                            <strong>{{ $type->name }}</strong><br>
                            <code style="font-size:0.75rem;">{{ $type->code }}</code>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="allowances[{{ $type->id }}][amount]"
                                   value="{{ old("allowances.{$type->id}.amount", $enrollment->amount ?? ($type->code === 'PERA' ? $employee->pera : '')) }}">
                        </td>
                        <td>
                            <input type="date" name="allowances[{{ $type->id }}][effectivity_date]"
                                   value="{{ old("allowances.{$type->id}.effectivity_date", optional($enrollment?->effectivity_date)->toDateString() ?? now()->toDateString()) }}">
                        </td>
                        <td>
                            <input type="date" name="allowances[{{ $type->id }}][expiry_date]"
                                   value="{{ old("allowances.{$type->id}.expiry_date", optional($enrollment?->expiry_date)->toDateString()) }}">
                        </td>
                        <td>
                            <input type="text" name="allowances[{{ $type->id }}][remarks]"
                                   value="{{ old("allowances.{$type->id}.remarks", $enrollment->remarks ?? '') }}">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary" style="margin-top:16px;">Save Allowances</button>
        </form>
    </div>
</div>
@endsection
