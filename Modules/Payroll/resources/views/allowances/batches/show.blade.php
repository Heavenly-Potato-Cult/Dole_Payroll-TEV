@extends('layouts.app')

@section('title', 'Allowance Batch')
@section('page-title', 'Allowances')

@section('content')
@php
    $months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
    $periodLabel = ($months[$batch->period_month] ?? '') . ' ' . $batch->period_year;
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Allowance Batch — {{ $periodLabel }}</h1>
        <p>{{ ucfirst($batch->cutoff) }} cutoff · {{ $batch->period_start->format('M d') }}{{ $batch->period_end ? ' – ' . $batch->period_end->format('M d, Y') : '' }}</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('payroll.allowances.index') }}" class="btn btn-outline">← Back</a>
        @if ($batch->status === 'draft')
            <a href="{{ route('payroll.allowances.batches.edit', $batch) }}" class="btn btn-outline">Edit</a>
        @endif
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
        <div>
            <strong>Status:</strong> {{ str_replace('_', ' ', ucfirst($batch->status)) }}<br>
            <span style="color:var(--text-light);font-size:0.9rem;">Prepared by {{ $batch->creator->name ?? '—' }} on {{ $batch->prepared_at?->format('M d, Y') ?? '—' }}</span>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            @if ($batch->status === 'draft')
                <form method="POST" action="{{ route('payroll.allowances.batches.advance', $batch) }}">@csrf<input type="hidden" name="action" value="submit"><button class="btn btn-primary btn-sm">Submit for Review</button></form>
            @elseif ($batch->status === 'pending_review')
                <form method="POST" action="{{ route('payroll.allowances.batches.advance', $batch) }}">@csrf<input type="hidden" name="action" value="approve"><button class="btn btn-primary btn-sm">Approve</button></form>
            @elseif ($batch->status === 'approved')
                <form method="POST" action="{{ route('payroll.allowances.batches.advance', $batch) }}">@csrf<input type="hidden" name="action" value="release"><button class="btn btn-primary btn-sm">Release</button></form>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Entries ({{ $batch->entries->count() }})</h3></div>
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
                @foreach ($batch->entries as $entry)
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
                    <th class="text-right">₱{{ number_format($batch->entries->sum('amount'), 2) }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
