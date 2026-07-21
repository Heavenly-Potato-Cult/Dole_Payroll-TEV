{{-- resources/views/special-payroll/differential-payslip.blade.php --}}
{{--
    Expects from SpecialPayrollController::differentialPayslip():
      $batch     — SpecialPayrollBatch (type = salary_differential, status = released)
      $employee  — Employee model
      $result    — array from SalaryDifferentialService::compute()
      $signatory — Signatory model (hrmo_designate) or null

    Mirrors newly-hired-payslip.blade.php's visual language (navy #1A2B6B,
    DejaVu Sans, table layout, DOLE logo via public_path()) — same reason
    this is a dedicated DomPDF template rather than a wrap of
    differential-show.blade.php: that view's stepper/print-CSS chrome isn't
    DomPDF-renderable.

    KEY DIFFERENCE from newly-hired-payslip: there is no single cut-off
    earnings block here. SalaryDifferentialService can span multiple
    months, so the earnings section is a per-month breakdown table
    ($result['per_month']) instead of a flat list of earning lines, and
    deductions (GSIS/PHIC/Pag-IBIG/WHT) are computed on the differential
    only and are always mandatory — no LWOP line, no allowance lines, no
    GSIS opt-in toggle (see Goal 1/Goal 2 discussion — salary differential
    does not carry allowances or an optional-GSIS path the way newly-hired
    does).
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Payslip — Salary Differential — {{ $employee->full_name }}</title>
<style>
@page { margin: 14mm 12mm; }

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 8pt;
    color: #1a1a1a;
    background: #fff;
    line-height: 1.4;
}

.slip {
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    border: 1px solid #1A2B6B;
    border-collapse: collapse;
    table-layout: fixed;
}
.slip > tbody > tr > td { padding: 0; }

/* ── Header ── */
.slip-header {
    border-bottom: 1px solid #1A2B6B;
    padding: 8px 10px 6px;
    text-align: center;
}
.header-logo {
    display: block;
    margin: 0 auto 4px auto;
    width: 42px;
    height: 42px;
}
.slip-header .republic    { font-size: 6.5pt; font-style: italic; color: #666; }
.slip-header .agency      { font-size: 9pt; font-weight: bold; color: #1A2B6B; line-height: 1.3; }
.slip-header .ro          { font-size: 7.2pt; color: #444; }
.slip-header .payslip-for { margin-top: 4px; font-size: 8pt; font-weight: bold; letter-spacing: 0.08em; color: #1A2B6B; }
.slip-header .type-label  { font-size: 7pt; color: #555; margin-top: 2px; }

/* ── Employee info strip ── */
.slip-employee {
    border-bottom: 1px solid #C8D2EE;
    padding: 6px 10px;
}
.slip-employee table { width: 100%; border-collapse: collapse; }
.slip-employee td { padding: 1px 0; font-size: 7.5pt; }
.slip-employee .label { color: #666; width: 38%; }
.slip-employee .value { font-weight: bold; color: #1A2B6B; }

/* ── Section banner ── */
.section-banner {
    background: #1A2B6B;
    color: #fff;
    font-size: 7.5pt;
    font-weight: bold;
    letter-spacing: 0.05em;
    padding: 3px 10px;
}

/* ── Rows table ── */
.rows-table { width: 100%; border-collapse: collapse; }
.rows-table td {
    padding: 3px 10px;
    font-size: 7.8pt;
    border-bottom: 1px solid #EEF1FA;
}
.rows-table .amt { text-align: right; }
.rows-table .sub-label { padding-left: 18px; color: #555; }
.rows-table .neg { color: #B71C1C; }

.divider-row td {
    border-top: 1.2px solid #1A2B6B;
    border-bottom: none;
    font-weight: bold;
    padding-top: 5px;
}

/* ── Per-month breakdown table ── */
.month-table { width: 100%; border-collapse: collapse; }
.month-table th {
    background: #EEF1FA;
    color: #1A2B6B;
    font-size: 6.8pt;
    font-weight: bold;
    text-align: right;
    padding: 3px 10px;
    border-bottom: 1px solid #C8D2EE;
}
.month-table th:first-child { text-align: left; }
.month-table td {
    padding: 3px 10px;
    font-size: 7.5pt;
    text-align: right;
    border-bottom: 1px solid #EEF1FA;
}
.month-table td:first-child { text-align: left; }
.month-table .full-tag { font-size: 6.2pt; color: #888; }
.month-table tfoot td {
    border-top: 1.2px solid #1A2B6B;
    border-bottom: none;
    font-weight: bold;
    padding-top: 5px;
}

/* ── Net pay footer ── */
.net-footer {
    background: #F5E9C8;
    border-top: 1.5px solid #1A2B6B;
    padding: 8px 10px;
}
.net-footer table { width: 100%; border-collapse: collapse; }
.net-footer .label { font-size: 8pt; font-weight: bold; color: #1A2B6B; }
.net-footer .value { font-size: 11pt; font-weight: bold; color: #1A2B6B; text-align: right; }

/* ── Signatory ── */
.slip-footer {
    padding: 14px 10px 10px;
    text-align: center;
}
.slip-footer .line {
    border-top: 1px solid #333;
    width: 70%;
    margin: 24px auto 3px;
}
.slip-footer .signatory  { font-weight: bold; font-size: 7.8pt; color: #0D1C55; }
.slip-footer .signatory-title { font-size: 6.8pt; color: #555; }

.remarks-box {
    margin: 8px 10px 0;
    padding: 5px 8px;
    background: #FAFBFF;
    border: 1px solid #E5E9F5;
    font-size: 6.8pt;
    color: #555;
}
</style>
</head>
<body>

@php
    // Base64-encode logo for DomPDF — same approach as the regular payroll
    // payslip_blade.php and newly-hired-payslip.blade.php, so it renders
    // identically here.
    $logoPath = public_path('assets/img/dole_logo.png');
    $logoSrc  = (file_exists($logoPath) && extension_loaded('gd'))
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : '';
@endphp

<table class="slip">
<tbody>
<tr><td>

    {{-- ── Header ── --}}
    <div class="slip-header">
        @if ($logoSrc)
        <img class="header-logo" src="{{ $logoSrc }}" alt="DOLE"/>
        @endif
        <div class="republic">Republic of the Philippines</div>
        <div class="agency">DEPARTMENT OF LABOR AND EMPLOYMENT</div>
        <div class="ro">Regional Office No. IX — Zamboanga Peninsula</div>
        <div class="payslip-for">PAYSLIP</div>
        <div class="type-label">Salary Differential</div>
    </div>

    {{-- ── Employee info ── --}}
    <div class="slip-employee">
        <table>
            <tr>
                <td class="label">Employee</td>
                <td class="value">{{ strtoupper($employee->full_name) }}</td>
            </tr>
            <tr>
                <td class="label">Position</td>
                <td>
                    {{ $employee->position_title ?? '—' }}
                    @if ($batch->new_position && $batch->new_position !== $employee->position_title)
                        → {{ $batch->new_position }}
                    @endif
                </td>
            </tr>
            @if ($batch->old_salary_grade || $batch->new_salary_grade)
            <tr>
                <td class="label">Salary Grade</td>
                <td>
                    @if ($batch->old_salary_grade) SG {{ $batch->old_salary_grade }} @else — @endif
                    @if ($batch->new_salary_grade) → SG {{ $batch->new_salary_grade }} @endif
                </td>
            </tr>
            @endif
            @if ($batch->old_step || $batch->new_step)
            <tr>
                <td class="label">Step</td>
                <td>
                    @if ($batch->old_step) Step {{ $batch->old_step }} @else — @endif
                    @if ($batch->new_step) → Step {{ $batch->new_step }} @endif
                </td>
            </tr>
            @endif
            <tr>
                <td class="label">Effectivity Covered</td>
                <td>
                    {{ \Carbon\Carbon::parse($result['effectivity_from'])->format('M j, Y') }}
                    –
                    {{ \Carbon\Carbon::parse($result['effectivity_to'])->format('M j, Y') }}
                </td>
            </tr>
            <tr>
                <td class="label">Old Rate</td>
                <td>₱{{ number_format($result['old_salary'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">New Rate</td>
                <td>₱{{ number_format($result['new_salary'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Differential / mo.</td>
                <td class="value">₱{{ number_format($result['differential'], 2) }}</td>
            </tr>
        </table>
    </div>

    {{-- ── Per-month breakdown ── --}}
    <div class="section-banner">MONTHLY BREAKDOWN</div>
    <table class="month-table">
        <thead>
            <tr>
                <th>Month</th>
                <th>Earned</th>
                <th>GSIS</th>
                <th>PHIC</th>
                <th>Pag-IBIG</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($result['per_month'] as $mo)
            <tr>
                <td>
                    {{ $mo['month_label'] }}
                    @if (! $mo['is_full'])
                        <span class="full-tag">({{ $mo['days'] }}d)</span>
                    @endif
                </td>
                <td>₱{{ number_format($mo['earned'], 2) }}</td>
                <td>₱{{ number_format($mo['gsis'], 2) }}</td>
                <td>₱{{ number_format($mo['phic'], 2) }}</td>
                <td>₱{{ number_format($mo['pagibig'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td>₱{{ number_format($result['total_earned'], 2) }}</td>
                <td>₱{{ number_format($result['total_gsis'], 2) }}</td>
                <td>₱{{ number_format($result['total_phic'], 2) }}</td>
                <td>₱{{ number_format($result['total_pagibig'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ── Deductions summary ── --}}
    <div class="section-banner">DEDUCTIONS SUMMARY</div>
    <table class="rows-table">
        <tr>
            <td>GSIS Personal Share</td>
            <td class="amt">₱{{ number_format($result['total_gsis'], 2) }}</td>
        </tr>
        <tr>
            <td>PhilHealth</td>
            <td class="amt">₱{{ number_format($result['total_phic'], 2) }}</td>
        </tr>
        <tr>
            <td>Pag-IBIG</td>
            <td class="amt">₱{{ number_format($result['total_pagibig'], 2) }}</td>
        </tr>
        <tr>
            <td>Withholding Tax ({{ number_format($result['wht_rate'] * 100, 0) }}%)</td>
            <td class="amt">₱{{ number_format($result['total_wht'], 2) }}</td>
        </tr>
        <tr class="divider-row">
            <td>Total Deductions</td>
            <td class="amt">₱{{ number_format($result['total_deductions'], 2) }}</td>
        </tr>
    </table>

    {{-- ── Net Pay ── --}}
    <div class="net-footer">
        <table>
            <tr>
                <td class="label">NET PAY</td>
                <td class="value">₱{{ number_format($result['net_amount'], 2) }}</td>
            </tr>
        </table>
    </div>

    @if ($batch->remarks)
    <div class="remarks-box">
        <strong>Remarks:</strong> {{ $batch->remarks }}
    </div>
    @endif

    {{-- ── Signatory ── --}}
    <div class="slip-footer">
        <div class="line"></div>
        <div class="signatory">{{ strtoupper($signatory?->full_name ?? 'HRMO DESIGNATE') }}</div>
        <div class="signatory-title">
            {{ $signatory?->position_title ?? '' }}{{ $signatory?->position_title ? ', ' : '' }}HRMO Designate
        </div>
    </div>

</td></tr>
</tbody>
</table>

</body>
</html>
