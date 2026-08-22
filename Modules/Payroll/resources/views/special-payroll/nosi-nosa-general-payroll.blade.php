{{-- resources/views/special-payroll/nosi-nosa-general-payroll.blade.php --}}
{{--
    Dedicated DomPDF template for the "GENERAL PAYROLL" register/certification
    document for a NOSI or NOSA batch. NOT the per-employee payslip
    (nosi-nosa-payslip.blade.php) — this is the printable register page that
    used to be produced by hitting window.print() on nosi-nosa-show.blade.php,
    which broke because that page is an interactive, app-layout screen
    DomPDF cannot render. Standalone HTML, no app layout, built for DomPDF
    specifically.

    Expects from SpecialPayrollController@nosiNosaGeneralPayroll:
      $batch        — SpecialPayrollBatch (type=nosi|nosa, with employee, approver)
      $employee     — Employee model
      $result       — array from SalaryDifferentialService::compute() (includes 'per_month')
      $period       — string, formatted "from – to" period
      $typeUpper    — string ('NOSI' | 'NOSA')
      $typeTitle    — string ('NOTICE OF STEP INCREMENT' | 'NOTICE OF SALARY ADJUSTMENT')
      $statusLabel  — string
      $signatory    — Signatory|null (hrmo_designate)
--}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>General Payroll — {{ $typeUpper }} — {{ $employee->last_name }}, {{ $employee->first_name }}</title>
@php
/*
    Declared here (before any use below), not after </html>: see the
    identical note in differential-general-payroll.blade.php — PHP only
    hoists UNCONDITIONAL top-level function declarations; the
    function_exists() guard makes this conditional, so it must sit above
    every call site. Suffixed *GeneralPayroll* to avoid colliding with the
    identically-purposed helper in nosi-nosa-show.blade.php or
    differential-general-payroll.blade.php if more than one of these is
    ever rendered in the same request.
*/
if (! function_exists('amountToWordsGeneralPayroll')) {
function amountToWordsGeneralPayroll(float $amount): string
{
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
             'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
             'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    $int  = (int) floor($amount);
    $cent = (int) round(($amount - $int) * 100);

    $convert = function (int $n) use (&$convert, $ones, $tens): string {
        if ($n === 0)  return '';
        if ($n < 20)   return $ones[$n];
        if ($n < 100)  return $tens[(int)($n/10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
        if ($n < 1000) return $ones[(int)($n/100)] . ' Hundred' . ($n % 100 ? ' ' . $convert($n % 100) : '');
        if ($n < 1_000_000) {
            return $convert((int)($n/1000)) . ' Thousand' . ($n % 1000 ? ' ' . $convert($n % 1000) : '');
        }
        return $convert((int)($n/1_000_000)) . ' Million' . ($n % 1_000_000 ? ' ' . $convert($n % 1_000_000) : '');
    };

    $words = trim($convert($int)) ?: 'Zero';
    return $words . ' Pesos and ' . str_pad($cent, 2, '0', STR_PAD_LEFT) . '/100 Only';
}
}
@endphp
<style>
    @page { margin: 9mm 8mm; }

    * { box-sizing: border-box; }

    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 8.5pt;
        color: #1F2937;
        margin: 0;
        padding: 0;
    }

    .doc-header { text-align: center; padding-bottom: 6px; border-bottom: 2px solid #1A2B6B; margin-bottom: 8px; }
    .doc-header .header-logo { display: block; margin: 0 auto 4px auto; width: 40px; height: 40px; }
    .doc-header .republic { font-size: 6.8pt; font-style: italic; color: #666; margin: 0 0 1px; }
    .doc-header .doc-agency { font-size: 8.5pt; color: #4B5563; margin: 0 0 2px; }
    .doc-header .doc-type-badge {
        display: inline-block; font-size: 8pt; font-weight: bold; letter-spacing: 0.08em;
        color: #ffffff; background: #1A2B6B; padding: 2px 10px; margin: 4px 0;
    }
    .doc-header h1 { font-size: 12pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.04em; color: #1A2B6B; margin: 2px 0; }
    .doc-header h2 { font-size: 9.5pt; font-weight: bold; color: #1A2B6B; margin: 2px 0; }
    .doc-header .doc-period { font-size: 8.5pt; color: #4B5563; margin: 4px 0 0; }

    .ack { font-size: 7.8pt; font-style: italic; color: #4B5563; margin-bottom: 6px; }

    table.doc-meta { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.doc-meta td { padding: 2px 10px 2px 0; vertical-align: top; }
    table.doc-meta .label { display: block; font-size: 6.8pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: #9CA3AF; }
    table.doc-meta .value { display: block; font-size: 8.3pt; font-weight: bold; color: #1F2937; margin-top: 1px; }

    table.reg-table { width: 100%; border-collapse: collapse; font-size: 6.9pt; margin-bottom: 10px; }
    table.reg-table thead th {
        background: #1A2B6B; color: #ffffff;
        padding: 4px 3px; text-align: right; font-weight: bold;
        border: 1px solid #1A2B6B;
    }
    table.reg-table thead th.text-left { text-align: left; }
    table.reg-table thead th.text-center { text-align: center; }
    table.reg-table thead th.hdr-earned { background: #1e3a8a; text-align: center; }
    table.reg-table thead th.hdr-deduct { background: #7c1a1a; text-align: center; }
    table.reg-table tbody td {
        padding: 4px 3px; border: 1px solid #D1D5DB; text-align: right;
    }
    table.reg-table tbody td.text-left { text-align: left; }
    table.reg-table tbody td.text-center { text-align: center; }
    table.reg-table tfoot td {
        padding: 4px 3px; border: 1px solid #D1D5DB;
        font-weight: bold; background: #F3F4F6; text-align: right;
    }
    table.reg-table tfoot td.text-left { text-align: left; }
    .red-text { color: #B71C1C; }
    .green-text { color: #1B5E20; }

    .remarks-box {
        font-size: 7.8pt; margin-bottom: 14px; padding: 6px 8px;
        background: #FAFBFF; border: 1px solid #E5E7EB;
    }

    table.cert-grid { width: 100%; border-collapse: separate; border-spacing: 6px; page-break-inside: avoid; }
    table.cert-grid td.cert-block {
        width: 25%; vertical-align: top; border: 1px solid #D1D5DB; padding: 7px 8px 8px;
    }
    .cert-block-ref { font-size: 7pt; font-weight: bold; color: #9CA3AF; margin-bottom: 4px; }
    .cert-block-title { font-size: 7pt; color: #4B5563; margin-bottom: 12px; line-height: 1.3; }
    .cert-block-meta { font-size: 6.6pt; color: #4B5563; margin-top: 8px; }
    .cert-block-meta span { display: block; padding: 2px 0; border-bottom: 1px solid #D1D5DB; min-width: 90px; margin-bottom: 3px; }
    .cert-sig-line { border-top: 1px solid #1F2937; margin-top: 18px; }
    .cert-sig-name { font-size: 7.4pt; font-weight: bold; margin-top: 4px; }
    .cert-sig-role { font-size: 6.8pt; color: #4B5563; }
</style>
</head>
<body>

@php
    // Base64-encode logo for DomPDF — same approach as nosi-nosa-payslip.blade.php
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
        <p class="doc-agency">Department of Labor and Employment — RO9, Zamboanga City</p>
        <div class="doc-type-badge">{{ $typeUpper }}</div>
        <h1>General Payroll</h1>
        <h2>{{ $typeTitle }}</h2>
        <p class="doc-period">For the Period of {{ strtoupper($period) }}</p>
    </div>

    @php
        // Same dynamic-grid approach as differential-general-payroll.blade.php,
        // for layout consistency across the two templates and to trim a
        // little vertical space toward fitting one page.
        $metaItems = collect([
            [
                'label' => 'Employee',
                'value' => trim(
                    optional($employee)->last_name . ', ' . optional($employee)->first_name
                    . (optional($employee)->middle_name ? ' ' . substr($employee->middle_name, 0, 1) . '.' : '')
                ),
            ],
            ['label' => 'Position', 'value' => optional($employee)->position_title ?? '—'],
            ['label' => 'Old Rate', 'value' => '₱' . number_format($batch->old_basic_salary, 2)],
            ['label' => 'New Rate', 'value' => '₱' . number_format($batch->new_basic_salary, 2)],
            [
                'label'  => 'Differential / mo.',
                'value'  => '₱' . number_format($result['differential'], 2),
                'accent' => true,
            ],
            ['label' => 'Months Covered', 'value' => count($result['per_month']) . ' month(s)'],
            ['label' => 'WHT Rate', 'value' => number_format($result['wht_rate'] * 100, 0) . '%'],
            ['label' => 'Status', 'value' => $statusLabel],
        ]);

        if ($batch->approver) {
            $metaItems->push([
                'label' => $batch->status === 'released' ? 'Released by' : 'Approved by',
                'value' => $batch->approver->name ?? '—',
            ]);
        }

        $metaRows = $metaItems->chunk(4);
    @endphp
    <table class="doc-meta">
        @foreach ($metaRows as $row)
        <tr>
            @foreach ($row as $item)
            <td style="width:25%;">
                <span class="label">{{ $item['label'] }}</span>
                <span class="value" @if (! empty($item['accent'])) style="color:#1A2B6B;" @endif>{{ $item['value'] }}</span>
            </td>
            @endforeach
        </tr>
        @endforeach
    </table>

    <p class="ack">
        We acknowledge receipt of cash shown opposite our name as full compensation
        for services rendered for the period covered.
    </p>

    <table class="reg-table">
        <thead>
            <tr>
                <th rowspan="2" class="text-center" style="width:18px;">No.</th>
                <th rowspan="2" class="text-left">Name</th>
                <th rowspan="2" class="text-left">Position</th>
                <th colspan="{{ 3 + count($result['per_month']) }}" class="hdr-earned">EARNED FOR THE PERIOD</th>
                <th colspan="6" class="hdr-deduct">DEDUCTIONS</th>
                <th rowspan="2">Net Amount</th>
                <th rowspan="2" class="text-center" style="width:60px;">Signature</th>
            </tr>
            <tr>
                <th>New Rate</th>
                <th>Old Rate</th>
                <th>Differential</th>
                @foreach ($result['per_month'] as $mo)
                    <th>{{ $mo['month_label'] }}<br><span style="font-weight:normal;">({{ $mo['calendar_days'] }}d)</span></th>
                @endforeach
                <th class="hdr-deduct">Total</th>
                <th>PhilHealth</th>
                <th>GSIS Life/Ret</th>
                <th>Pag-IBIG</th>
                <th>Whld Tax</th>
                <th class="hdr-deduct">Total Deduct.</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td class="text-left" style="font-weight:bold;">
                    {{ optional($employee)->last_name }}, {{ optional($employee)->first_name }}
                    @if (optional($employee)->middle_name) {{ substr($employee->middle_name, 0, 1) }}. @endif
                </td>
                <td class="text-left">{{ optional($employee)->position_title ?? '—' }}</td>
                <td>{{ number_format($batch->new_basic_salary, 2) }}</td>
                <td>{{ number_format($batch->old_basic_salary, 2) }}</td>
                <td style="font-weight:bold;">{{ number_format($result['differential'], 2) }}</td>
                @foreach ($result['per_month'] as $mo)
                    <td>{{ number_format($mo['earned'], 2) }}</td>
                @endforeach
                <td style="font-weight:bold;">{{ number_format($result['total_earned'], 2) }}</td>
                <td class="red-text">{{ number_format($result['total_phic'], 2) }}</td>
                <td class="red-text">{{ number_format($result['total_gsis'], 2) }}</td>
                <td class="red-text">{{ number_format($result['total_pagibig'], 2) }}</td>
                <td class="red-text">{{ number_format($result['total_wht'], 2) }}</td>
                <td class="red-text" style="font-weight:bold;">{{ number_format($result['total_deductions'], 2) }}</td>
                <td class="green-text" style="font-weight:bold;">{{ number_format($result['net_amount'], 2) }}</td>
                <td>&nbsp;</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-left">TOTAL</td>
                <td>{{ number_format($batch->new_basic_salary, 2) }}</td>
                <td>{{ number_format($batch->old_basic_salary, 2) }}</td>
                <td>{{ number_format($result['differential'], 2) }}</td>
                @foreach ($result['per_month'] as $mo)
                    <td>{{ number_format($mo['earned'], 2) }}</td>
                @endforeach
                <td>{{ number_format($result['total_earned'], 2) }}</td>
                <td class="red-text">{{ number_format($result['total_phic'], 2) }}</td>
                <td class="red-text">{{ number_format($result['total_gsis'], 2) }}</td>
                <td class="red-text">{{ number_format($result['total_pagibig'], 2) }}</td>
                <td class="red-text">{{ number_format($result['total_wht'], 2) }}</td>
                <td class="red-text">{{ number_format($result['total_deductions'], 2) }}</td>
                <td class="green-text">{{ number_format($result['net_amount'], 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    @if ($batch->remarks)
    <div class="remarks-box">
        <strong style="color:#1A2B6B;">Remarks:</strong> {{ $batch->remarks }}
    </div>
    @endif

    <table class="cert-grid">
        <tr>
            <td class="cert-block">
                <div class="cert-block-ref">A</div>
                <div class="cert-block-title">CERTIFIED: Services duly rendered as stated.</div>
                <div class="cert-sig-line"></div>
                <div class="cert-sig-name">{{ $signatory?->full_name ?? 'NAME' }}</div>
                <div class="cert-sig-role">{{ $signatory?->position_title ?? 'Position' }}, HRMO / HRMO Designate</div>
                <div class="cert-sig-role">Authorized Official</div>
            </td>
            <td class="cert-block">
                <div class="cert-block-ref">B</div>
                <div class="cert-block-title">
                    CERTIFIED: Funds available, cash available, supporting documents complete and proper.
                </div>
                <div class="cert-sig-line"></div>
                <div class="cert-sig-name">{{ ($batch->approver && $batch->status !== 'draft') ? $batch->approver->name : 'NAME' }}</div>
                <div class="cert-sig-role">Accountant</div>
                <div class="cert-block-meta">
                    ALOBS NO.: <span></span>
                    Date: <span></span>
                    JEV No.: <span></span>
                    Date: <span></span>
                </div>
            </td>
            <td class="cert-block">
                <div class="cert-block-ref">C</div>
                <div class="cert-block-title">
                    APPROVED FOR PAYMENT:
                    <br>
                    <strong>{{ strtoupper(amountToWordsGeneralPayroll($result['net_amount'])) }}</strong>
                    <br>= ₱ {{ number_format($result['net_amount'], 2) }}
                </div>
                <div class="cert-sig-line"></div>
                <div class="cert-sig-name">NAME</div>
                <div class="cert-sig-role">Head of Agency / Authorized Representative</div>
            </td>
            <td class="cert-block">
                <div class="cert-block-ref">D</div>
                <div class="cert-block-title">
                    CERTIFIED: Each employee whose name appears above has been paid
                    the amount indicated opposite his/her name.
                </div>
                <div class="cert-sig-line"></div>
                <div class="cert-sig-name">NAME</div>
                <div class="cert-sig-role">Position, Cashier</div>
                <div class="cert-block-meta">
                    Date: <span></span>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
