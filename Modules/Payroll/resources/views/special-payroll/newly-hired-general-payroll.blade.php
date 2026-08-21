{{-- resources/views/special-payroll/newly-hired-general-payroll.blade.php --}}
{{--
    Dedicated DomPDF template for the "GENERAL PAYROLL" register/certification
    document for a Newly Hired / Transferee / Others pro-rated payroll batch.

    This is NOT the per-employee payslip (newly-hired-payslip.blade.php) — it
    is the printable register page that used to be produced by hitting
    window.print() on newly-hired-show.blade.php, which broke because that
    page is an interactive, app-layout screen (scrollbars, nav chrome,
    CSS grid) that DomPDF cannot render. This template is a standalone
    HTML document with no app layout, built for DomPDF specifically.

    Expects from SpecialPayrollController@newHireGeneralPayroll:
      $batch               — SpecialPayrollBatch (type=newly_hired/transferee/others, with employee, approver)
      $employee            — Employee model
      $result              — array from NewlyHiredPayrollService::compute()
      $typeLabel            — string ('Newly Hired' | 'Transferee' | 'Others')
      $periodLabel          — string, formatted period covered
      $effectivityFmt       — string, formatted assumption-to-duty date
      $allowanceBreakdown   — Collection of ['name','code','amount']
      $allowancesTotalForDisplay — float
      $signatory            — Signatory|null (hrmo_designate)
      $statusLabel          — string
--}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>General Payroll — {{ $employee->last_name }}, {{ $employee->first_name }}</title>
<style>
    @page { margin: 14mm 10mm; }

    * { box-sizing: border-box; }

    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 8.5pt;
        color: #1F2937;
        margin: 0;
        padding: 0;
    }

    .doc-header { text-align: center; padding-bottom: 10px; border-bottom: 2px solid #1A2B6B; margin-bottom: 14px; }
    .doc-header .header-logo { display: block; margin: 0 auto 4px auto; width: 40px; height: 40px; }
    .doc-header .republic { font-size: 6.8pt; font-style: italic; color: #666; margin: 0 0 1px; }
    .doc-header .doc-agency { font-size: 8.5pt; color: #4B5563; margin: 0 0 2px; }
    .doc-header h1 { font-size: 12pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.04em; color: #1A2B6B; margin: 2px 0; }
    .doc-header h2 { font-size: 9.5pt; font-weight: bold; color: #1A2B6B; margin: 2px 0; }
    .doc-header .doc-period { font-size: 8.5pt; color: #4B5563; margin: 4px 0 0; }

    .ack {
        font-size: 7.8pt; font-style: italic; color: #4B5563;
        margin-bottom: 10px;
    }

    /* ── Meta table (label/value pairs, 3 columns) ── */
    table.doc-meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table.doc-meta td { padding: 3px 10px 3px 0; vertical-align: top; width: 33.33%; }
    table.doc-meta .label { display: block; font-size: 6.8pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: #9CA3AF; }
    table.doc-meta .value { display: block; font-size: 8.3pt; font-weight: bold; color: #1F2937; margin-top: 1px; }

    /* ── Register table ── */
    table.reg-table { width: 100%; border-collapse: collapse; font-size: 7.3pt; margin-bottom: 10px; }
    table.reg-table thead th {
        background: #1A2B6B; color: #ffffff;
        padding: 5px 4px; text-align: right; font-weight: bold;
        border: 1px solid #1A2B6B;
    }
    table.reg-table thead th.text-left { text-align: left; }
    table.reg-table thead th.text-center { text-align: center; }
    table.reg-table tbody td {
        padding: 5px 4px; border: 1px solid #D1D5DB; text-align: right;
    }
    table.reg-table tbody td.text-left { text-align: left; }
    table.reg-table tbody td.text-center { text-align: center; }
    table.reg-table tfoot td {
        padding: 5px 4px; border: 1px solid #D1D5DB;
        font-weight: bold; background: #F3F4F6; text-align: right;
    }
    table.reg-table tfoot td.text-left { text-align: left; }
    .red-text { color: #B71C1C; }
    .green-text { color: #1B5E20; }

    .govtshare-note {
        font-size: 7.6pt; color: #4B5563; margin-bottom: 10px;
        padding: 6px 8px; background: #F8F9FF; border: 1px solid #E5E7EB;
    }

    .amount-words {
        font-size: 7.8pt; color: #4B5563; text-align: right; margin-bottom: 14px;
    }
    .amount-words .peso { font-weight: bold; color: #1A2B6B; }

    .remarks-box {
        font-size: 7.8pt; margin-bottom: 14px; padding: 6px 8px;
        background: #FAFBFF; border: 1px solid #E5E7EB;
    }

    /* ── Certification blocks — 2x2 table so DomPDF keeps each cell together ── */
    table.cert-grid { width: 100%; border-collapse: separate; border-spacing: 8px; page-break-inside: avoid; }
    table.cert-grid td.cert-block {
        width: 50%; vertical-align: top; border: 1px solid #D1D5DB; padding: 10px 12px 16px;
    }
    .cert-block-tag { font-size: 7pt; font-weight: bold; color: #9CA3AF; margin-bottom: 4px; }
    .cert-block-title { font-size: 7.6pt; color: #4B5563; margin-bottom: 16px; line-height: 1.35; }
    .cert-meta-line { font-size: 7.6pt; color: #4B5563; margin-bottom: 12px; }
    .cert-sig-line { border-top: 1px solid #1F2937; margin-top: 26px; }
    .cert-sig-name { font-size: 8pt; font-weight: bold; margin-top: 4px; }
    .cert-sig-role { font-size: 7.3pt; color: #4B5563; }
    .cert-date { font-size: 7.3pt; color: #4B5563; margin-top: 8px; }
</style>
</head>
<body>

@php
    // Base64-encode logo for DomPDF — same approach as newly-hired-payslip.blade.php
    // and the regular payroll payslip_blade.php, so the register carries the
    // same visual identity as the payslip family.
    $logoPath = public_path('assets/img/dole_logo.png');
    $logoSrc  = (file_exists($logoPath) && extension_loaded('gd'))
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : '';
@endphp

    <div class="doc-header">
        @if ($logoSrc)
        <img class="header-logo" src="{{ $logoSrc }}" alt="DOLE"/>
        @endif
        <p class="republic">Republic of the Philippines</p>
        <p class="doc-agency">Department of Labor and Employment — Regional Office IX, Zamboanga City</p>
        <h1>General Payroll</h1>
        <h2>Pro-Rated Payroll for {{ strtoupper($typeLabel) }} Employee</h2>
        <p class="doc-period">For the Period of {{ $periodLabel }}</p>
    </div>

    <p class="ack">
        I acknowledge receipt of cash shown opposite my name as full compensation
        for services rendered for the period covered.
    </p>

    <table class="doc-meta">
        <tr>
            <td>
                <span class="label">Employee Name</span>
                <span class="value">
                    {{ $employee->last_name }}, {{ $employee->first_name }}
                    @if ($employee->middle_name) {{ substr($employee->middle_name, 0, 1) }}. @endif
                </span>
            </td>
            <td>
                <span class="label">Position</span>
                <span class="value">{{ $employee->position_title ?? '—' }}</span>
            </td>
            <td>
                <span class="label">Plantilla Item No.</span>
                <span class="value">{{ $employee->plantilla_item_no ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Assumption to Duty</span>
                <span class="value">{{ $effectivityFmt }}</span>
            </td>
            <td>
                <span class="label">Period Covered</span>
                <span class="value">{{ $periodLabel }}</span>
            </td>
            <td>
                <span class="label">Working Days</span>
                <span class="value">{{ $result['working_days'] }} day(s) &nbsp;<span style="font-weight:normal; color:#9CA3AF;">(22-day divisor)</span></span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Status</span>
                <span class="value">{{ $statusLabel }}</span>
            </td>
            @if ($batch->approver)
            <td>
                <span class="label">{{ $batch->status === 'released' ? 'Released by' : 'Approved by' }}</span>
                <span class="value">{{ $batch->approver->name ?? '—' }}</span>
            </td>
            @endif
        </tr>
    </table>

    <table class="reg-table">
        <thead>
            <tr>
                <th class="text-center" style="width:20px;">#</th>
                <th class="text-left">Name</th>
                <th class="text-left">Position</th>
                <th>Basic Salary</th>
                <th>Salary Earned</th>
                <th>Allowances</th>
                <th>Total Earned</th>
                <th>GSIS PS</th>
                <th>PHIC</th>
                <th>Pag-IBIG</th>
                <th>WHT</th>
                <th>Total Deductions</th>
                <th>Net Amount</th>
                <th class="text-center" style="width:70px;">Signature</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td class="text-left" style="font-weight:bold;">
                    {{ $employee->last_name }}, {{ $employee->first_name }}
                    @if ($employee->middle_name) {{ substr($employee->middle_name, 0, 1) }}. @endif
                </td>
                <td class="text-left">{{ $employee->position_title ?? '—' }}</td>
                <td>₱{{ number_format($result['basic_salary'], 2) }}</td>
                <td>₱{{ number_format($result['salary_earned'], 2) }}</td>
                <td>₱{{ number_format($allowancesTotalForDisplay, 2) }}</td>
                <td style="font-weight:bold;">₱{{ number_format($result['net_earned'], 2) }}</td>
                <td class="red-text">₱{{ number_format($result['gsis_ps'], 2) }}</td>
                <td>₱{{ number_format($result['phic'], 2) }}</td>
                <td>₱{{ number_format($result['pagibig'], 2) }}</td>
                <td>₱{{ number_format($result['wht'], 2) }}</td>
                <td class="red-text" style="font-weight:bold;">₱{{ number_format($result['total_deductions'], 2) }}</td>
                <td class="green-text" style="font-weight:bold;">₱{{ number_format($result['net_amount'], 2) }}</td>
                <td>&nbsp;</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-left">TOTALS — 1 employee</td>
                <td>₱{{ number_format($result['basic_salary'], 2) }}</td>
                <td>₱{{ number_format($result['salary_earned'], 2) }}</td>
                <td>₱{{ number_format($allowancesTotalForDisplay, 2) }}</td>
                <td>₱{{ number_format($result['net_earned'], 2) }}</td>
                <td class="red-text">₱{{ number_format($result['gsis_ps'], 2) }}</td>
                <td>₱{{ number_format($result['phic'], 2) }}</td>
                <td>₱{{ number_format($result['pagibig'], 2) }}</td>
                <td>₱{{ number_format($result['wht'], 2) }}</td>
                <td class="red-text">₱{{ number_format($result['total_deductions'], 2) }}</td>
                <td class="green-text">₱{{ number_format($result['net_amount'], 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    @if (isset($allowanceBreakdown) && $allowanceBreakdown->isNotEmpty())
    <div class="govtshare-note">
        <strong style="color:#1A2B6B;">Allowances Breakdown:</strong>
        @foreach ($allowanceBreakdown as $line)
            {{ $line['name'] }}: <strong>₱{{ number_format($line['amount'], 2) }}</strong>@if (! $loop->last) &emsp;|&emsp; @endif
        @endforeach
    </div>
    @endif

    <div class="govtshare-note">
        <strong style="color:#1A2B6B;">Government Shares</strong>
        (remitted separately — not deducted from employee's net pay):
        &emsp; GSIS GS: <strong>₱{{ number_format($result['gsis_gs'], 2) }}</strong>
        &emsp;|&emsp; PhilHealth GS: <strong>₱{{ number_format($result['phic_gs'], 2) }}</strong>
        &emsp;|&emsp; Pag-IBIG GS: <strong>₱{{ number_format($result['hdmf_gs'], 2) }}</strong>
    </div>

    <div class="amount-words">
        <span class="peso">=P=</span>
        &nbsp; ₱{{ number_format($result['net_amount'], 2) }}
        &emsp; ALOBS No.: ______________
        &emsp; Date: ______________
    </div>

    @if ($batch->remarks)
    <div class="remarks-box">
        <strong style="color:#1A2B6B;">Remarks:</strong> {{ $batch->remarks }}
    </div>
    @endif

    <table class="cert-grid">
        <tr>
            <td class="cert-block">
                <div class="cert-block-tag">[ A ]</div>
                <div class="cert-block-title">Certified: Services duly rendered as stated.</div>
                <div class="cert-sig-line"></div>
                <div class="cert-sig-name">{{ $signatory?->full_name ?? '________________________________' }}</div>
                <div class="cert-sig-role">Administrative Officer V / HRMO Designate</div>
                <div class="cert-sig-role">Authorized Official</div>
                <div class="cert-date">Date: ________________________</div>
            </td>
            <td class="cert-block">
                <div class="cert-block-tag">[ B ]</div>
                <div class="cert-block-title">
                    Certified: Funds available, cash available, supporting documents complete and proper.
                </div>
                <div class="cert-sig-line"></div>
                @if ($batch->approver && $batch->status !== 'draft')
                    <div class="cert-sig-name">{{ $batch->approver->name }}</div>
                    <div class="cert-sig-role">Accountant</div>
                    <div class="cert-date">
                        Date:
                        {{ $batch->approved_at ? \Carbon\Carbon::parse($batch->approved_at)->format('M d, Y') : '________________________' }}
                    </div>
                @else
                    <div class="cert-sig-name">________________________________</div>
                    <div class="cert-sig-role">Accountant</div>
                    <div class="cert-date">Date: ________________________</div>
                @endif
            </td>
        </tr>
        <tr>
            <td class="cert-block">
                <div class="cert-block-tag">[ C ]</div>
                <div class="cert-block-title">Approved for Payment:</div>
                <div class="cert-meta-line">
                    <strong>=P=</strong> ₱{{ number_format($result['net_amount'], 2) }}
                    &emsp; JEV No.: ______________
                </div>
                <div class="cert-sig-line"></div>
                <div class="cert-sig-name">________________________________</div>
                <div class="cert-sig-role">Regional Director / ARD</div>
                <div class="cert-sig-role">Head of Agency / Authorized Representative</div>
                <div class="cert-date">Date: ________________________</div>
            </td>
            <td class="cert-block">
                <div class="cert-block-tag">[ D ]</div>
                <div class="cert-block-title">
                    Certified: Each employee whose name appears above has been paid
                    the amount indicated opposite his/her name.
                </div>
                <div class="cert-sig-line"></div>
                <div class="cert-sig-name">________________________________</div>
                <div class="cert-sig-role">AO V / Cashier</div>
                <div class="cert-date">Date: ________________________</div>
            </td>
        </tr>
    </table>

</body>
</html>
