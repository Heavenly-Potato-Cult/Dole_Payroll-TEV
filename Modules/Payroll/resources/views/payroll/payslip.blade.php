<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Payslip — {{ $periodLabel }}</title>
<style>
/* ================================================================
   DOLE RO9 Payslip — DomPDF Stylesheet (v7 · Monthly)
   A4 Portrait · Two payslip copies side-by-side
   Layout: 46% slip | 8% divider | 46% slip = 100%
   Earnings/Deductions: single amount column (monthly total)
   Net Pay footer: three columns — 1–15 | 16–end | TOTAL
================================================================ */

@page { margin: 8mm 6mm 6mm 6mm; }

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 7pt;
    color: #1a1a1a;
    background: #fff;
    line-height: 1.35;
}

/* ─── OUTER LAYOUT ─── */
.two-col {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.slip-cell {
    width: 46%;
    vertical-align: top;
    padding: 0;
}
.col-divider {
    width: 8%;
    vertical-align: top;
    padding: 0;
    text-align: center;
    border-left: 1.2px dashed #9BADD0;
    border-right: 1.2px dashed #9BADD0;
}
.divider-scissors {
    display: block;
    font-size: 9pt;
    color: #9BADD0;
    padding-top: 3px;
    line-height: 1;
}

/* ─── Copy badge ─── */
.copy-badge {
    text-align: center;
    font-size: 5.5pt;
    font-weight: bold;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #4A5E99;
    padding: 0 0 2px 0;
}

/* ─── PAYSLIP CARD ─── */
.slip {
    width: 100%;
    border: 1px solid #1A2B6B;
    font-size: 6.8pt;
    table-layout: fixed;
    border-collapse: collapse;
}
.slip > tbody > tr > td { padding: 0; }

/* ── Header ── */
.slip-header {
    border-bottom: 1px solid #1A2B6B;
    padding: 4px 4px 3px;
    text-align: center;
    background: #fff;
}
.header-logo {
    display: block;
    margin: 0 auto 3px auto;
    width: 34px;
    height: 34px;
}
.slip-header .republic    { font-size: 5.5pt; font-style: italic; color: #666; }
.slip-header .agency      { font-size: 7.5pt; font-weight: bold; color: #1A2B6B; line-height: 1.25; }
.slip-header .ro          { font-size: 6.2pt; color: #444; }
.slip-header .payslip-for { margin-top: 2px; font-size: 7pt; font-weight: bold; letter-spacing: 0.1em; color: #1A2B6B; }
.slip-header .period-label { font-size: 6.2pt; color: #555; }

/* ── Employee info strip ── */
.slip-employee {
    border-bottom: 1px solid #C8D2EE;
    padding: 2px 4px;
    background: #F3F5FC;
}
.slip-employee table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.slip-employee td { font-size: 6.2pt; padding: 0.8px 2px; vertical-align: top; overflow: hidden; }
.slip-employee .lbl { width: 44px; color: #777; font-size: 5.5pt; white-space: nowrap; }
.slip-employee .val { font-weight: bold; color: #0D1C55; word-wrap: break-word; }

/* ── Rows table (single-amount: earnings + deductions) ── */
.slip-rows {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.slip-rows td {
    padding: 1.2px 3px;
    font-size: 6.5pt;
    vertical-align: middle;
    border-bottom: 1px solid #ECECEC;
    overflow: hidden;
}
.slip-rows .c-label { width: 70%; color: #222; }
.slip-rows .c-amt   { width: 30%; text-align: right; font-size: 6.3pt; word-wrap: break-word; }

/* Income rows */
.row-income .c-label { font-weight: bold; font-size: 6.8pt; color: #0D1C55; }
.row-income .c-amt   { font-weight: bold; color: #0D1C55; }

/* Section banner */
.row-spacer td {
    background: #1A2B6B;
    color: #fff;
    font-weight: bold;
    font-size: 6.2pt;
    letter-spacing: 0.12em;
    padding: 2px 3px;
    border-bottom: none;
}

/* Sub-rows */
.row-sub .c-label { padding-left: 8px; color: #555; font-style: italic; font-size: 6.2pt; }

/* TOTAL DEDUCTIONS row */
.row-divider td {
    background: #E2E7F5;
    font-weight: bold;
    font-size: 6.5pt;
    border-top: 1px solid #7A90CC;
    border-bottom: 1px solid #7A90CC;
}

.amount-zero { color: #ccc; }

/* ── NET PAY footer table (three columns: 1-15 | 16-end | TOTAL) ── */
.net-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.net-table td {
    border-bottom: 1px solid #1A2B6B;
    padding: 0;
}

/* Header sub-row inside net table */
.net-col-header td {
    background: #E6EAF7;
    font-size: 5.8pt;
    font-weight: bold;
    color: #1A2B6B;
    text-align: right;
    padding: 1.5px 3px;
    border-bottom: 1px solid #1A2B6B;
}
.net-col-header .nh-label { text-align: left; width: 40%; }
.net-col-header .nh-1st   { width: 20%; }
.net-col-header .nh-2nd   { width: 20%; }
.net-col-header .nh-tot   { width: 20%; }

/* Net pay value row */
.net-row .nl-label {
    width: 40%;
    background: #1A2B6B;
    color: #F9A825;
    font-weight: bold;
    font-size: 6.5pt;
    letter-spacing: 0.04em;
    padding: 2.5px 3px;
}
.net-row .nl-1st,
.net-row .nl-2nd,
.net-row .nl-tot {
    width: 20%;
    background: #FFFBEA;
    color: #7A5900;
    font-weight: bold;
    font-size: 6.8pt;
    text-align: right;
    padding: 2.5px 3px;
}

/* ── Footer ── */
.slip-footer {
    border-top: 1px solid #1A2B6B;
    padding: 3px 4px 3px;
    background: #F3F5FC;
}
.slip-footer .signatory  { font-weight: bold; font-size: 6.8pt; color: #0D1C55; }
.slip-footer .sig-title  { font-size: 5.8pt; color: #555; }
.slip-footer .doc-ref    { margin-top: 2px; font-size: 5.3pt; color: #777; line-height: 1.6; }

/* ─── Page break between employees ─── */
.page-break { page-break-after: always; }

</style>
</head>
<body>

@php
    $months = ['','January','February','March','April','May','June',
               'July','August','September','October','November','December'];
    $monthName   = $months[$batch->period_month] ?? '';
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $batch->period_month, $batch->period_year);

    // Base64-encode logo for DomPDF
    $logoPath = public_path('assets/img/dole_logo.png');
    $logoSrc  = (file_exists($logoPath) && extension_loaded('gd'))
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : '';

    $copyLabels = ['EMPLOYEE COPY', 'OFFICE COPY'];
@endphp

@foreach ($payslips as $slip)
@php
    $employee    = $slip['employee'];
    $entry       = $slip['entry'];
    $cutoffSplit = $slip['cutoffSplit'] ?? null;
    $dedMap      = $slip['dedMap'];

    $ded = function($code) use ($dedMap) {
        $d = $dedMap->get($code);
        return $d ? (float) $d->amount : null;
    };

    // ── Net pay per cutoff (for the bottom section) ───────────────────
    $net1st = isset($cutoffSplit['first_cutoff']['net_amount'])
        ? (float) $cutoffSplit['first_cutoff']['net_amount']
        : (isset($cutoffSplit['first_cutoff']['gross_income'])
            ? (float) $cutoffSplit['first_cutoff']['gross_income']
            : (float) $entry->net_amount / 2);

    $net2nd = isset($cutoffSplit['second_cutoff']['net_amount'])
        ? (float) $cutoffSplit['second_cutoff']['net_amount']
        : (isset($cutoffSplit['second_cutoff']['gross_income'])
            ? (float) $cutoffSplit['second_cutoff']['gross_income']
            : (float) $entry->net_amount / 2);

    $netTot = (float) $entry->net_amount;
@endphp

<table class="two-col">
<tbody>
<tr>

@for ($col = 1; $col <= 2; $col++)

<td class="slip-cell">

    <div class="copy-badge">{{ $copyLabels[$col - 1] }}</div>

    <table class="slip">

        {{-- ── HEADER ── --}}
        <tr><td>
        <div class="slip-header">
            @if ($logoSrc)
            <img class="header-logo" src="{{ $logoSrc }}" alt="DOLE"/>
            @endif
            <div class="republic">Republic of the Philippines</div>
            <div class="agency">DEPARTMENT OF LABOR AND EMPLOYMENT</div>
            <div class="ro">Regional Office No. 9</div>
            <div class="payslip-for">PAYSLIP FOR</div>
            <div class="period-label">{{ $monthName }} 1–{{ $daysInMonth }}, {{ $batch->period_year }}</div>
        </div>
        </td></tr>

        {{-- ── EMPLOYEE INFO ── --}}
        <tr><td>
        <div class="slip-employee">
            <table>
                <tr><td class="lbl">Name:</td><td class="val">{{ $employee->full_name }}</td></tr>
                <tr><td class="lbl">Position:</td><td class="val">{{ $employee->position_title }}</td></tr>
                <tr><td class="lbl">Plantilla:</td><td>{{ $employee->plantilla_item_no ?? '—' }}</td></tr>
                <tr><td class="lbl">Division:</td><td>{{ $employee->division->name ?? '—' }}</td></tr>
                <tr><td class="lbl">SG – Step:</td><td>SG {{ $employee->salary_grade }} – Step {{ $employee->step }}</td></tr>
            </table>
        </div>
        </td></tr>

        {{-- ── EARNINGS & DEDUCTIONS (single amount column) ── --}}
        <tr><td>
        <table class="slip-rows">

        @foreach ($rows as $row)
        @php
            $type  = $row['type'];
            $label = $row['label'];
            $code  = $row['code'];

            // Skip net rows — handled separately in the net-table below
            if ($type === 'net') continue;

            $amt = null;
            switch ($type) {
                case 'income':
                    $amt = $label === 'BASIC'
                        ? (float) $entry->basic_salary
                        : (float) $entry->pera;
                    break;
                case 'deduction':
                case 'sub':
                    $amt = $ded($code);
                    break;
                case 'divider':
                    $amt = (float) $entry->total_deductions;
                    break;
            }

            $rowClass = match ($type) {
                'income'  => 'row-income',
                'spacer'  => 'row-spacer',
                'sub'     => 'row-sub',
                'divider' => 'row-divider',
                default   => 'row-deduction',
            };
        @endphp

        @if ($type === 'spacer')
            <tr class="{{ $rowClass }}">
                <td colspan="2">{{ $label }}</td>
            </tr>

        @elseif ($type === 'divider')
            <tr class="{{ $rowClass }}">
                <td class="c-label">{{ $label }}</td>
                <td class="c-amt">
                    @if ($amt != 0){{ number_format($amt, 2) }}@else<span class="amount-zero">—</span>@endif
                </td>
            </tr>

        @else
            <tr class="{{ $rowClass }}">
                <td class="c-label">{{ $label }}</td>
                <td class="c-amt">
                    @if ($amt !== null && $amt != 0)
                        {{ number_format($amt, 2) }}
                    @elseif ($type === 'income')
                        <span class="amount-zero">—</span>
                    @endif
                </td>
            </tr>
        @endif

        @endforeach

        </table>
        </td></tr>

        {{-- ── NET PAY SECTION — three columns ── --}}
        <tr><td>
        <table class="net-table">
            {{-- Column sub-headers --}}
            <tr class="net-col-header">
                <td class="nh-label">NET PAY</td>
                <td class="nh-1st">1–15</td>
                <td class="nh-2nd">16–{{ $daysInMonth }}</td>
                <td class="nh-tot">TOTAL</td>
            </tr>
            {{-- Values --}}
            <tr class="net-row">
                <td class="nl-label">{{ $monthName }} {{ $batch->period_year }}</td>
                <td class="nl-1st">{{ number_format($net1st, 2) }}</td>
                <td class="nl-2nd">{{ number_format($net2nd, 2) }}</td>
                <td class="nl-tot">{{ number_format($netTot, 2) }}</td>
            </tr>
        </table>
        </td></tr>

        {{-- ── FOOTER ── --}}
        <tr><td>
        <div class="slip-footer">
            <div class="signatory">{{ strtoupper($signatory?->full_name ?? 'HRMO DESIGNATE') }}</div>
            <div class="sig-title">
                {{ $signatory?->position_title ?? '' }}{{ $signatory?->position_title ? ', ' : '' }}HRMO Designate
            </div>
            <div class="doc-ref">
                D9FI-550308 Rev. 01 &nbsp;·&nbsp;
                Email: ro9@dole.gov.ph &nbsp;·&nbsp;
                Tel: (062) 991-2673 · (062) 991-3376 &nbsp;·&nbsp;
                Website: ro9.dole.gov.ph
            </div>
        </div>
        </td></tr>

    </table>

</td>

@if ($col === 1)
<td class="col-divider">
    <span class="divider-scissors">✂</span>
</td>
@endif

@endfor

</tr>
</tbody>
</table>

@if (!$loop->last)
<div class="page-break"></div>
@endif

@endforeach

</body>
</html>