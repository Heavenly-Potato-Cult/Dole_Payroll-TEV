@extends('layouts.app')

@section('title', 'Allowance Assignment')
@section('page-title', 'Allowances')

@section('content')
@php
    $months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
    $periodLabel = ($months[$assignment->period_month] ?? '') . ' ' . $assignment->period_year;
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Allowance Assignment — {{ $periodLabel }}</h1>
        <p>{{ ucfirst($assignment->cutoff) }} cutoff · {{ $assignment->period_start->format('M d') }}{{ $assignment->period_end ? ' – ' . $assignment->period_end->format('M d, Y') : '' }}</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('payroll.allowances.index') }}" class="btn btn-outline">← Back</a>
        @if ($assignment->status === 'draft')
            <a href="{{ route('payroll.allowances.assignments.edit', $assignment) }}" class="btn btn-outline">Edit</a>
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
