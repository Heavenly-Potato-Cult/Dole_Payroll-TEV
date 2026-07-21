{{-- resources/views/special-payroll/newly-hired-payslip.blade.php --}}
{{--
    Expects from SpecialPayrollController::newHirePayslip():
      $batch     — SpecialPayrollBatch (type in newly_hired/transferee/others, status = released)
      $employee  — Employee model
      $result    — array from NewlyHiredPayrollService::compute()
      $typeLabel — 'Newly Hired' | 'Transferee' | 'Others'
      $signatory — Signatory model (hrmo_designate) or null

    Goal 2(A) — deliberately NOT a wrap of newly-hired-show.blade.php: that
    view's stepper/SweetAlert/app-layout chrome isn't DomPDF-renderable.
    This is a dedicated, single-slip, single-copy DomPDF template — same
    visual language (navy #1A2B6B, DejaVu Sans, table layout) as the regular
    payroll payslip_blade.php, but with one net-pay column instead of three
    since there's no 1st/2nd cutoff split for special payroll.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Payslip — {{ $typeLabel }} — {{ $employee->full_name }}</title>
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
    // payslip_blade.php, so it renders identically here.
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
        <div class="type-label">{{ $typeLabel }} — Pro-Rated Payroll</div>
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
                <td>{{ $employee->position_title ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Effectivity Date</td>
                <td>{{ optional($batch->effectivity_date)->format('F j, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Cut-off Covered</td>
                <td>
                    {{ optional($batch->period_start)->format('M j') }}
                    –
                    {{ optional($batch->period_end)->format('j, Y') }}
                </td>
            </tr>
            <tr>
                <td class="label">Working Days</td>
                <td>{{ $result['working_days'] }} day(s) <span style="font-size:6pt; color:#888;">(÷22-day rate)</span></td>
            </tr>
        </table>
    </div>

    {{-- ── Earnings ── --}}
    <div class="section-banner">EARNINGS</div>
    <table class="rows-table">
        <tr>
            <td>Basic Salary (Pro-Rated)</td>
            <td class="amt">₱{{ number_format($result['salary_earned'], 2) }}</td>
        </tr>
        <tr>
            <td>PERA (Pro-Rated)</td>
            <td class="amt">₱{{ number_format($result['pera_earned'], 2) }}</td>
        </tr>
        @foreach ($result['allowance_lines'] ?? [] as $line)
        <tr>
            <td>
                {{ $line['name'] }} (Pro-Rated)
                @if (! empty($line['is_overridden']))
                    <span style="font-size:6.5pt; color:#B7791F;"> · manually adjusted</span>
                @endif
            </td>
            <td class="amt">₱{{ number_format($line['amount'], 2) }}</td>
        </tr>
        @endforeach
        @if (($result['lwop_deduction'] ?? 0) > 0)
        <tr>
            <td class="neg">LWOP Deduction ({{ $result['lwop_days'] }} day/s)</td>
            <td class="amt neg">−₱{{ number_format($result['lwop_deduction'], 2) }}</td>
        </tr>
        @endif
        <tr class="divider-row">
            <td>Gross Earned</td>
            <td class="amt">₱{{ number_format($result['net_earned'], 2) }}</td>
        </tr>
    </table>

    {{-- ── Deductions ── --}}
    <div class="section-banner">DEDUCTIONS</div>
    <table class="rows-table">
        <tr>
            <td>GSIS Personal Share</td>
            <td class="amt">₱{{ number_format($result['gsis_ps'], 2) }}</td>
        </tr>
        <tr>
            <td class="sub-label">PhilHealth (Government share only — not deducted)</td>
            <td class="amt">₱0.00</td>
        </tr>
        <tr>
            <td class="sub-label">Pag-IBIG (Government share only — not deducted)</td>
            <td class="amt">₱0.00</td>
        </tr>
        <tr>
            <td class="sub-label">Withholding Tax (annualized — no history yet)</td>
            <td class="amt">₱0.00</td>
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
