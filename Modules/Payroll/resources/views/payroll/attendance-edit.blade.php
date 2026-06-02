{{-- resources/views/payroll/attendance-edit.blade.php --}}
{{--
    Expects from PayrollController@editAttendance:
      $payroll  — PayrollBatch
      $snapshot — AttendanceSnapshot (with employee loaded)
--}}

@extends('layouts.app')

@section('title', 'Edit Attendance Record')
@section('page-title', 'Edit Attendance')

@section('content')

@php
    $months = ['','January','February','March','April','May','June',
               'July','August','September','October','November','December'];
    $periodLabel = ($months[$payroll->period_month] ?? '?')
        . ' ' . \Carbon\Carbon::parse($payroll->period_start)->format('j')
        . '–' . \Carbon\Carbon::parse($payroll->period_end)->format('j')
        . ', ' . $payroll->period_year;

    $employee = $snapshot->employee;
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Attendance — {{ $periodLabel }}</h1>
        <p>
            {{ optional($employee)->last_name }}, {{ optional($employee)->first_name }}
            <span class="text-muted" style="font-size:0.85rem;">· {{ optional($employee)->employee_no }}</span>
        </p>
    </div>
    <a href="{{ route('payroll.show', $payroll) }}" class="btn btn-outline btn-sm">← Back to Batch</a>
</div>

@if (session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

<div style="display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:start;">

    {{-- ── Edit Form ── --}}
    <div class="card">
        <div class="card-header">
            <h3>✏ Correct Attendance Record</h3>
        </div>
        <div class="card-body">

            @if ($snapshot->is_corrected)
                <div class="alert" style="background:#FFF8E1; border-color:#FFD54F; margin-bottom:16px;">
                    <strong>⚠ Previously corrected</strong> by {{ $snapshot->correctedBy->name ?? '—' }}
                    on {{ optional($snapshot->corrected_at)->format('M d, Y') }}<br>
                    <span style="font-size:0.82rem; color:var(--text-mid);">
                        Note: {{ $snapshot->correction_note ?? '—' }}
                    </span>
                </div>
            @endif

            <form method="POST" action="{{ route('payroll.attendance.update', [$payroll, $snapshot]) }}">
                @csrf
                @method('PATCH')

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;">

                    <div class="form-group">
                        <label for="days_present">Days Present</label>
                        <input type="number" name="days_present" id="days_present"
                               step="0.5" min="0" max="31"
                               value="{{ old('days_present', number_format($snapshot->days_present, 1)) }}"
                               class="{{ $errors->has('days_present') ? 'is-invalid' : '' }}">
                        @error('days_present')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="lwop_days">LWOP Days <span class="text-muted">(Leave Without Pay)</span></label>
                        <input type="number" name="lwop_days" id="lwop_days"
                               step="0.5" min="0" max="31"
                               value="{{ old('lwop_days', number_format($snapshot->lwop_days, 3)) }}"
                               class="{{ $errors->has('lwop_days') ? 'is-invalid' : '' }}">
                        @error('lwop_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="late_minutes">Late (minutes)</label>
                        <input type="number" name="late_minutes" id="late_minutes"
                               step="1" min="0"
                               value="{{ old('late_minutes', $snapshot->late_minutes) }}"
                               class="{{ $errors->has('late_minutes') ? 'is-invalid' : '' }}">
                        @error('late_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="undertime_minutes">Undertime (minutes)</label>
                        <input type="number" name="undertime_minutes" id="undertime_minutes"
                               step="1" min="0"
                               value="{{ old('undertime_minutes', $snapshot->undertime_minutes) }}"
                               class="{{ $errors->has('undertime_minutes') ? 'is-invalid' : '' }}">
                        @error('undertime_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="leave_credits">Leave Credits</label>
                        <input type="number" name="leave_credits" id="leave_credits"
                               step="0.001" min="0"
                               value="{{ old('leave_credits', number_format($snapshot->leave_credits, 3)) }}"
                               class="{{ $errors->has('leave_credits') ? 'is-invalid' : '' }}">
                        @error('leave_credits')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="form-group" style="margin-top:8px;">
                    <label for="correction_note">
                        Reason for Correction <span style="color:var(--red);">*</span>
                    </label>
                    <textarea name="correction_note" id="correction_note" rows="3"
                              placeholder="Describe why this record is being corrected (min. 5 characters)…"
                              class="{{ $errors->has('correction_note') ? 'is-invalid' : '' }}"
                              style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:6px; font-size:0.88rem; resize:vertical;">{{ old('correction_note') }}</textarea>
                    @error('correction_note')
                        <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="alert alert-warning" style="font-size:0.83rem; margin-top:12px;">
                    <strong>⚠ Remember:</strong> After saving, you must
                    <strong>re-run Compute Payroll</strong> on the batch for these changes
                    to be reflected in payroll entries.
                </div>

                <div class="d-flex gap-2" style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary">
                        ✔ Save Correction
                    </button>
                    <a href="{{ route('payroll.show', $payroll) }}" class="btn btn-outline">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

    {{-- ── Current values reference ── --}}
    <div class="card">
        <div class="card-header">
            <h3>📋 Current HRIS Values</h3>
        </div>
        <div class="card-body" style="font-size:0.86rem;">
            <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span class="text-muted">Days Present</span>
                    <strong>{{ number_format($snapshot->days_present, 1) }}</strong>
                </div>
                <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span class="text-muted">LWOP Days</span>
                    <strong class="{{ $snapshot->lwop_days > 0 ? 'text-red' : '' }}">
                        {{ number_format($snapshot->lwop_days, 3) }}
                    </strong>
                </div>
                <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span class="text-muted">Late (min)</span>
                    <strong class="{{ $snapshot->late_minutes > 0 ? 'text-red' : '' }}">
                        {{ $snapshot->late_minutes }}
                    </strong>
                </div>
                <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border);">
                    <span class="text-muted">Undertime (min)</span>
                    <strong class="{{ $snapshot->undertime_minutes > 0 ? 'text-red' : '' }}">
                        {{ $snapshot->undertime_minutes }}
                    </strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span class="text-muted">Leave Credits</span>
                    <strong>{{ number_format($snapshot->leave_credits, 3) }}</strong>
                </div>
            </div>

            @if ($snapshot->daily_logs && count($snapshot->daily_logs) > 0)
                <div style="margin-top:16px;">
                    <div style="font-weight:700; color:var(--navy); font-size:0.80rem; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.04em;">
                        Daily Log Breakdown
                    </div>
                    <div style="max-height:260px; overflow-y:auto; font-size:0.78rem;">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="background:var(--navy); color:white;">
                                    <th style="padding:4px 8px; text-align:left;">Date</th>
                                    <th style="padding:4px 8px; text-align:center;">Present</th>
                                    <th style="padding:4px 8px; text-align:center;">Late</th>
                                    <th style="padding:4px 8px; text-align:center;">UT</th>
                                    <th style="padding:4px 6px; text-align:center;">CO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($snapshot->daily_logs as $date => $log)
                                    <tr style="{{ !($log['present'] ?? true) ? 'background:#FFF3F3;' : '' }}; border-bottom:1px solid var(--border);">
                                        <td style="padding:3px 8px;">
                                            {{ \Carbon\Carbon::parse($date)->format('M j') }}
                                        </td>
                                        <td style="padding:3px 8px; text-align:center;">
                                            {{ ($log['present'] ?? false) ? '✅' : '❌' }}
                                        </td>
                                        <td style="padding:3px 8px; text-align:center; {{ ($log['late_minutes'] ?? 0) > 0 ? 'color:var(--red); font-weight:600;' : 'color:var(--text-light);' }}">
                                            {{ ($log['late_minutes'] ?? 0) > 0 ? $log['late_minutes'] . 'm' : '—' }}
                                        </td>
                                        <td style="padding:3px 8px; text-align:center; {{ ($log['undertime_minutes'] ?? 0) > 0 ? 'color:var(--red); font-weight:600;' : 'color:var(--text-light);' }}">
                                            {{ ($log['undertime_minutes'] ?? 0) > 0 ? $log['undertime_minutes'] . 'm' : '—' }}
                                        </td>
                                        <td style="padding:3px 6px; text-align:center;">
                                            <span style="font-size:0.68rem; color:var(--text-light);">
                                                {{ ($log['is_first_cutoff'] ?? false) ? '1st' : '2nd' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-muted" style="font-size:0.78rem; margin-top:12px;">
                    No daily log detail available for this record.
                </div>
            @endif
        </div>
    </div>

</div>

@endsection