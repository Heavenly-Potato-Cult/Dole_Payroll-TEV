@extends('layouts.app')

@section('title', 'Allowance Assignment')
@section('page-title', 'Allowances')

@section('content')
@php
    $months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
    $periodLabel = ($months[$assignment->period_month] ?? '') . ' ' . $assignment->period_year;

    $statusColors = [
        'draft'    => ['bg' => '#f1f5f9', 'color' => '#475569'],
        'released' => ['bg' => '#dcfce7', 'color' => '#16a34a'],
    ];
    $sc = $statusColors[$assignment->status] ?? ['bg' => '#f1f5f9', 'color' => '#64748b'];
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>
            Allowance Assignment — {{ $periodLabel }}
            <span style="display:inline-block;margin-left:10px;padding:2px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;background:{{ $sc['bg'] }};color:{{ $sc['color'] }};vertical-align:middle;">
                {{ ucfirst($assignment->status) }}
            </span>
        </h1>
        <p>{{ ucfirst($assignment->cutoff) }} cutoff · {{ $assignment->period_start->format('M d') }}{{ $assignment->period_end ? ' – ' . $assignment->period_end->format('M d, Y') : '' }}</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('payroll.allowances.index') }}" class="btn btn-outline">← Back</a>
        @if ($assignment->status === 'draft')
            <a href="{{ route('payroll.allowances.assignments.edit', $assignment) }}" class="btn btn-outline">Edit</a>
            <form method="POST"
                  action="{{ route('payroll.allowances.assignments.advance', $assignment) }}"
                  onsubmit="return confirm('Once released, this assignment cannot be edited. Proceed?');"
                  style="display:inline;">
                @csrf
                <input type="hidden" name="action" value="release">
                <button type="submit" class="btn btn-primary">Release</button>
            </form>
        @endif
    </div>
</div>


<div class="card">
    <div class="card-header"><h3>Entries ({{ $assignment->entries->count() }})</h3></div>
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Allowance</th>
                    <th class="text-right">Amount</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assignment->entries as $entry)
                <tr>
                    <td>{{ $entry->employee->last_name ?? '' }}, {{ $entry->employee->first_name ?? '' }}</td>
                    <td>{{ $entry->allowanceType->name ?? '—' }}</td>
                    <td class="text-right">₱{{ number_format($entry->amount, 2) }}</td>
                    <td>{{ $entry->remarks ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">Total</th>
                    <th class="text-right">₱{{ number_format($assignment->entries->sum('amount'), 2) }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
