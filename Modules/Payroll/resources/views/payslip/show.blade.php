<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Payslip — {{ $employee->full_name }} — {{ $periodLabel }}</title>
<style>
/* ================================================================
   DOLE RO9 Payslip — DomPDF Stylesheet (v5)
   A4 Portrait · Two payslip copies side-by-side
   Layout: 46% slip | 8% divider | 46% slip = 100%
   Fixes: logo centered above text, symmetric divider strip,
          equal left/right slip spacing.
================================================================ */

@page {
    margin: 8mm 6mm 6mm 6mm;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 7pt;
    color: #1a1a1a;
    background: #fff;
    line-height: 1.35;
}

/* ─────────────────────────────────────────────
   OUTER LAYOUT: 46% | 8% | 46% = 100%
───────────────────────────────────────────── */
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

/* ── Center divider strip ──
   Single cell — dashed left+right borders create a symmetric
   "cut here" lane. Scissors sit centered at the top.
   Left slip ends at left border; right slip starts at right border.
   Both slips therefore have identical gap to the cut line. */
.col-divider {
    width: 8%;
    vertical-align: top;
    padding: 0 0 0 0;
    text-align: center;
    border-left: 1.2px dashed #9BADD0;
    border-right: 1.2px dashed #9BADD0;
}

.divider-scissors {
    display: block;
    text-align: center;
    font-size: 9pt;
    color: #9BADD0;
    padding-top: 3px;
    line-height: 1;
}

/* ── Copy badge ── */
.copy-badge {
    text-align: center;
    font-size: 5.5pt;
    font-weight: bold;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #4A5E99;
    padding: 0 0 2px 0;
}

/* ─────────────────────────────────────────────
   PAYSLIP CARD
───────────────────────────────────────────── */
.slip {
    width: 100%;
    border: 1px solid #1A2B6B;
    font-size: 6.8pt;
    table-layout: fixed;
    border-collapse: collapse;
}

.slip > tbody > tr > td { padding: 0; }

/* ── Header ──
   Logo centered above text; entire header text-align:center.
   No inner table — avoids off-center push. */
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

.slip-header .republic {
    font-size: 5.5pt;
    font-style: italic;
    color: #666;
}

.slip-header .agency {
    font-size: 7.5pt;
    font-weight: bold;
    color: #1A2B6B;
    line-height: 1.25;
}

.slip-header .ro {
    font-size: 6.2pt;
    color: #444;
}

.slip-header .payslip-for {
    margin-top: 2px;
    font-size: 7pt;
    font-weight: bold;
    letter-spacing: 0.1em;
    color: #1A2B6B;
}

.slip-header .period-label {
    font-size: 6.2pt;
    color: #555;
}

/* ── Employee info strip ── */
.slip-employee {
    border-bottom: 1px solid #C8D2EE;
    padding: 2px 4px;
    background: #F3F5FC;
}

.slip-employee table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.slip-employee td {
    font-size: 6.2pt;
    padding: 0.8px 2px;
    vertical-align: top;
    overflow: hidden;
}

.slip-employee .lbl {
    width: 44px;
    color: #777;
    font-size: 5.5pt;
    white-space: nowrap;
}

.slip-employee .val {
    font-weight: bold;
    color: #0D1C55;
    word-wrap: break-word;
}

/* ── Cut-off column sub-headers ── */
.col-headers {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    border-bottom: 1px solid #1A2B6B;
    background: #E6EAF7;
}

.col-headers td {
    padding: 1.8px 3px;
    font-size: 6pt;
    font-weight: bold;
    color: #1A2B6B;
    text-align: right;
}

.col-headers .c-label { text-align: left; width: 54%; }
.col-headers .c-1st,
.col-headers .c-2nd   { width: 23%; }

/* ── Rows table ── */
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

.slip-rows .c-label { width: 54%; color: #222; }
.slip-rows .c-1st,
.slip-rows .c-2nd {
    width: 23%;
    text-align: right;
    font-size: 6.3pt;
    word-wrap: break-word;
    overflow: hidden;
}

/* Income rows */
.row-income .c-label { font-weight: bold; font-size: 6.8pt; color: #0D1C55; }
.row-income .c-1st,
.row-income .c-2nd   { font-weight: bold; color: #0D1C55; }

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
.row-sub .c-label {
    padding-left: 8px;
    color: #555;
    font-style: italic;
    font-size: 6.2pt;
}

/* TOTAL row */
.row-divider td {
    background: #E2E7F5;
    font-weight: bold;
    font-size: 6.5pt;
    border-top: 1px solid #7A90CC;
    border-bottom: 1px solid #7A90CC;
}

/* NET PAY rows */
.row-net td { border-bottom: 1px solid #1A2B6B; }

.row-net .c-label {
    background: #1A2B6B;
    color: #F9A825;
    font-weight: bold;
    font-size: 6.8pt;
    letter-spacing: 0.04em;
    padding: 2.5px 3px;
}

.row-net .c-1st,
.row-net .c-2nd {
    background: #FFFBEA;
    color: #7A5900;
    font-weight: bold;
    font-size: 7.2pt;
}

/* Active cut-off highlight */
.col-active {
    background: #E5F3E8 !important;
    color: #1B5E20 !important;
}

.amount-zero { color: #ccc; }

/* Tardiness/LWOP */
.row-attendance td {
    background: #FFF8F0;
    font-size: 6pt;
    color: #6D4C41;
    padding: 1.2px 3px;
    border-bottom: 1px solid #FFE0B2;
}

/* ── Footer ── */
.slip-footer {
    border-top: 1px solid #1A2B6B;
    padding: 3px 4px 3px;
    background: #F3F5FC;
}

.slip-footer .signatory {
    font-weight: bold;
    font-size: 6.8pt;
    color: #0D1C55;
}

.slip-footer .sig-title {
    font-size: 5.8pt;
    color: #555;
}

.slip-footer .doc-ref {
    margin-top: 2px;
    font-size: 5.3pt;
    color: #777;
    line-height: 1.6;
}

</style>
</head>
<body>

@php
    $months = ['','January','February','March','April','May','June',
               'July','August','September','October','November','December'];
    $monthName = $months[$batch->period_month] ?? '';

    $amt = function($dedMap, $code) {
        if (!$dedMap || !$code) return null;
        $d = $dedMap->get($code);
        return $d ? (float) $d->amount : null;
    };

    $fmt = function($val, $forceShow = false) {
        if ($val === null || (!$forceShow && $val == 0)) return null;
        return number_format($val, 2);
    };

    $cutoffIs1st = $batch->cutoff === '1st';
    $copyLabels  = ['EMPLOYEE COPY', 'OFFICE COPY'];

    // Base64-encode logo for DomPDF (requires GD extension)
    $logoPath = public_path('assets/img/dole_logo.png');
    $logoSrc  = (file_exists($logoPath) && extension_loaded('gd'))
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : '';
@endphp

<table class="two-col">
<tbody>
<tr>

@for ($col = 1; $col <= 2; $col++)

{{-- ════════════════ SLIP CELL ════════════════ --}}
<td class="slip-cell">

    <div class="copy-badge">{{ $copyLabels[$col - 1] }}</div>

    <table class="slip">

        {{-- ── Header: logo centered above text ── --}}
        <tr><td>
        <div class="slip-header">
            @if ($logoSrc)
            <img class="header-logo" src="{{ $logoSrc }}" alt="DOLE Logo"/>
            @endif
            <div class="republic">Republic of the Philippines</div>
            <div class="agency">DEPARTMENT OF LABOR AND EMPLOYMENT</div>
            <div class="ro">Regional Office No. 9</div>
            <div class="payslip-for">PAYSLIP FOR</div>
            <div class="period-label">{{ $monthName }} 1–31, {{ $batch->period_year }}</div>
        </div>
        </td></tr>

        {{-- ── Employee info ── --}}
        <tr><td>
        <div class="slip-employee">
            <table>
                <tr>
                    <td class="lbl">Name:</td>
                    <td class="val">{{ $employee->full_name }}</td>
                </tr>
                <tr>
                    <td class="lbl">Position:</td>
                    <td class="val">{{ $employee->position_title }}</td>
                </tr>
                <tr>
                    <td class="lbl">Plantilla:</td>
                    <td>{{ $employee->plantilla_item_no ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Division:</td>
                    <td>{{ $employee->division->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">SG – Step:</td>
                    <td>SG {{ $employee->salary_grade }} – Step {{ $employee->step }}</td>
                </tr>
            </table>
        </div>
        </td></tr>

        {{-- ── Cut-off column headers ── --}}
        <tr><td>
        <table class="col-headers">
            <tr>
                <td class="c-label"></td>
                <td class="c-1st">1–15</td>
                <td class="c-2nd">16–30/31</td>
            </tr>
        </table>
        </td></tr>

        {{-- ── Payslip data rows ── --}}
        <tr><td>
        <table class="slip-rows">

        @foreach ($rows as $row)
        @php
            $type  = $row['type'];
            $label = $row['label'];
            $code  = $row['code'];
            $a1    = null;
            $a2    = null;

            switch ($type) {
                case 'income':
                    if ($label === 'BASIC') {
                        $a1 = $entry1st ? (float) $entry1st->basic_salary : null;
                        $a2 = $entry2nd ? (float) $entry2nd->basic_salary : null;
                    } elseif ($label === 'ALLOWANCE') {
                        $a1 = $entry1st ? (float) ($entry1st->allowances?->sum('amount') ?? $entry1st->pera) : null;
                        $a2 = $entry2nd ? (float) ($entry2nd->allowances?->sum('amount') ?? $entry2nd->pera) : null;
                    }
                    break;
                case 'allowance':
                    $a1 = $entry1st
                        ? (float) ($entry1st->allowances?->firstWhere('code', $code)?->amount ?? 0)
                        : null;
                    $a2 = $entry2nd
                        ? (float) ($entry2nd->allowances?->firstWhere('code', $code)?->amount ?? 0)
                        : null;
                    break;
                case 'deduction':
                case 'sub':
                    $a1 = $amt($ded1st, $code);
                    $a2 = $amt($ded2nd, $code);
                    break;
                case 'divider':
                    $a1 = $entry1st ? (float) $entry1st->total_deductions : null;
                    $a2 = $entry2nd ? (float) $entry2nd->total_deductions : null;
                    break;
                case 'net':
                    if ($label === 'NET PAY 1-15') {
                        $a1 = $entry1st ? (float) $entry1st->net_amount : null;
                        $a2 = null;
                    } else {
                        $a1 = null;
                        $a2 = $entry2nd ? (float) $entry2nd->net_amount : null;
                    }
                    break;
            }

            $rowClass = match ($type) {
                'income'    => 'row-income',
                'allowance' => 'row-income',
                'spacer'    => 'row-spacer',
                'sub'     => 'row-sub',
                'divider' => 'row-divider',
                'net'     => 'row-net',
                default   => 'row-deduction',
            };

            $active1 = $cutoffIs1st  ? 'col-active' : '';
            $active2 = !$cutoffIs1st ? 'col-active' : '';
        @endphp

        @if ($type === 'spacer')
            <tr class="{{ $rowClass }}">
                <td colspan="3">{{ $label }}</td>
            </tr>

        @elseif ($type === 'net')
            <tr class="{{ $rowClass }}">
                <td class="c-label">{{ $label }}</td>
                <td class="c-1st {{ $active1 }}">
                    @if ($a1 !== null){{ $fmt($a1, true) }}@endif
                </td>
                <td class="c-2nd {{ $active2 }}">
                    @if ($a2 !== null){{ $fmt($a2, true) }}@endif
                </td>
            </tr>

        @else
            <tr class="{{ $rowClass }}">
                <td class="c-label">{{ $label }}</td>
                <td class="c-1st {{ $type === 'divider' ? $active1 : '' }}">
                    @if ($a1 !== null && $a1 != 0)
                        {{ $fmt($a1) }}
                    @elseif ($type === 'income')
                        <span class="amount-zero">—</span>
                    @endif
                </td>
                <td class="c-2nd {{ $type === 'divider' ? $active2 : '' }}">
                    @if ($a2 !== null && $a2 != 0)
                        {{ $fmt($a2) }}
                    @elseif ($type === 'income')
                        <span class="amount-zero">—</span>
                    @endif
                </td>
            </tr>
        @endif

        @endforeach

        {{-- ── Tardiness / LWOP ── --}}
        @php
            $showAttendance = false;
        @endphp

        @if ($showAttendance)
        <tr class="row-attendance">
            <td class="c-label">Tardiness / LWOP</td>
            <td class="c-1st">
                @if (($tardy1 + $lwop1) > 0){{ number_format($tardy1 + $lwop1, 2) }}@endif
            </td>
            <td class="c-2nd">
                @if (($tardy2 + $lwop2) > 0){{ number_format($tardy2 + $lwop2, 2) }}@endif
            </td>
        </tr>
        @endif

        </table>
        </td></tr>

        {{-- ── Footer ── --}}
        <tr><td>
        <div class="slip-footer">
<div class="signatory">{{ strtoupper($signatory?->full_name ?? 'HRMO DESIGNATE') }}</div>
<div class="sig-title">{{ $signatory?->position_title ?? '' }}{{ $signatory?->position_title ? ', ' : '' }}HRMO Designate</div>
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

{{-- ════════════════ CENTER DIVIDER ════════════════ --}}
@if ($col === 1)
<td class="col-divider">
    <span class="divider-scissors">✂</span>
</td>
@endif

@endfor

</tr>
</tbody>
</table>

</body>
</html>
