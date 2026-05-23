<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Exports\GsisDetailedExport;
use Modules\Payroll\Exports\GsisSummaryExport;
use Modules\Payroll\Exports\HdmfRemittanceExport;
use Modules\Payroll\Exports\HdmfP1Export;
use Modules\Payroll\Exports\HdmfP2Export;
use Modules\Payroll\Exports\HdmfMplExport;
use Modules\Payroll\Exports\HdmfCalExport;
use Modules\Payroll\Exports\HdmfHousingExport;
use Modules\Payroll\Exports\LbpLoanExport;
use Modules\Payroll\Exports\CaressUnionDuesExport;
use Modules\Payroll\Exports\CaressMortuaryExport;
use Modules\Payroll\Exports\MassExport;
use Modules\Payroll\Exports\ProvidentFundExport;
use Modules\Payroll\Exports\BtrRefundExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class PayrollReportController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    //  Payroll Register - List all payroll batches
    //  GET /reports/payroll-register
    // ─────────────────────────────────────────────────────────────────────────
    public function payrollRegister(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $query = \Modules\Payroll\Models\PayrollBatch::with(['entries.employee'])
            ->orderByDesc('period_start')
            ->orderByDesc('cutoff');

        if ($request->filled('year')) {
            $query->whereYear('period_start', $request->year);
        }
        if ($request->filled('month')) {
            $query->whereMonth('period_start', $request->month);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $batches = $query->paginate(20)->withQueryString();
        $currentYear = now()->year;

        return view('payroll::reports.payroll-register', compact('batches', 'currentYear'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Reports Index - Unified Tabbed Interface
    //  GET /reports?tab=gsis|hdmf|phic|caress|mass|provident|lbp|btr|sss
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant', 'ard', 'cashier']);

        $tab = $request->get('tab', 'gsis');
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        $cutoff = $request->get('cutoff', 'both');

        $data = [
            'activeTab' => $tab,
            'year' => $year,
            'month' => $month,
            'cutoff' => $cutoff,
            'currentYear' => now()->year,
            'months' => $this->monthNames(),
        ];

        // Load data based on active tab
        switch ($tab) {
            case 'gsis':
                $summaryExport = new GsisSummaryExport($year, $month, $cutoff);
                $totals = $summaryExport->getTotals();
                $employeeCount = $summaryExport->getEmployeeCount();
                $grandTotal = array_sum($totals);
                $labelMap = [
                    'GSIS_LIFE_RETIREMENT' => 'Life/Retirement Premium Personal Share',
                    'GSIS_EMERGENCY' => 'Emergency Loan',
                    'GSIS_EDUC' => 'Educational Assistance Loan',
                    'GSIS_MPL_LITE' => 'Multi-Purpose Loan Lite (MPL Lite)',
                    'GSIS_CONSO' => 'Consolidated Loan',
                    'GSIS_HELP' => 'Home Emergency Loan',
                    'GSIS_GFAL' => 'GSIS Financial Assistance Program (GFAL)',
                    'GSIS_MPL' => 'Multi-Purpose Loan (MPL)',
                    'GSIS_CPL' => 'GSIS Computer Loan (CPL)',
                    'GSIS_POLICY' => 'Policy Loan - optional',
                    'GSIS_REAL_ESTATE' => 'Real Estate Loan',
                ];
                $data = array_merge($data, compact('totals', 'labelMap', 'employeeCount', 'grandTotal'));
                break;

            case 'hdmf':
                $p1 = new HdmfP1Export($year, $month, $cutoff);
                $p2 = new HdmfP2Export($year, $month, $cutoff);
                $mpl = new HdmfMplExport($year, $month, $cutoff);
                $cal = new HdmfCalExport($year, $month, $cutoff);
                $housing = new HdmfHousingExport($year, $month, $cutoff);

                $sheets = [
                    ['label' => 'Pag-IBIG I (P1)', 'program' => 'F1', 'count' => $p1->getCount(), 'total' => $p1->getTotal()],
                    ['label' => 'Modified Pag-IBIG II (P2)', 'program' => 'M2', 'count' => $p2->getCount(), 'total' => $p2->getTotal()],
                    ['label' => 'Multi-Purpose Loan (MPL)', 'program' => 'MPL', 'count' => $mpl->getCount(), 'total' => $mpl->getTotal()],
                    ['label' => 'Calamity Loan (CAL)', 'program' => 'CAL', 'count' => $cal->getCount(), 'total' => $cal->getTotal()],
                    ['label' => 'Housing Loan (HL)', 'program' => 'HL', 'count' => $housing->getCount(), 'total' => $housing->getTotal()],
                ];

                $grandTotal = array_sum(array_column($sheets, 'total'));
                $employeeCount = $p1->getCount();
                $data = array_merge($data, compact('sheets', 'grandTotal', 'employeeCount'));
                break;

            case 'phic':
            case 'sss':
                // These are CSV-only exports, no preview data needed
                $data['employeeCount'] = 0;
                $data['grandTotal'] = 0;
                break;

            case 'caress_union':
            case 'caress_mortuary':
            case 'mass':
            case 'provident_fund':
            case 'lbp':
            case 'btr':
                // Load preview data for these tabs
                $batches = \Modules\Payroll\Models\PayrollBatch::query()
                    ->whereYear('period_start', $year)
                    ->whereMonth('period_start', $month)
                    ->when($cutoff === '1st', fn($q) => $q->whereDay('period_start', '<=', 15))
                    ->when($cutoff === '2nd', fn($q) => $q->whereDay('period_start', '>', 15))
                    ->pluck('id');

                $codeMap = [
                    'caress_union' => 'CARESS_UNION',
                    'caress_mortuary' => 'CARESS_MORTUARY',
                    'mass' => 'MASS',
                    'provident_fund' => 'PROVIDENT_FUND',
                    'lbp' => 'LBP_LOAN',
                    'btr' => null, // Special case: multiple deduction types
                ];

                if ($tab === 'btr') {
                    $deductionTypes = \Modules\Payroll\Models\DeductionType::whereIn('code', ['WHT', 'REFUND_VARIOUS'])->pluck('id');

                    $rows = \Modules\Payroll\Models\PayrollDeduction::with(['entry.employee', 'deductionType'])
                        ->whereIn('payroll_entry_id', fn($q) => $q->select('id')->from('payroll_entries')->whereIn('payroll_batch_id', $batches))
                        ->whereIn('deduction_type_id', $deductionTypes)
                        ->where('amount', '>', 0)
                        ->get();
                } else {
                    $deductionTypeId = \Modules\Payroll\Models\DeductionType::where('code', $codeMap[$tab])->value('id');

                    if (!$deductionTypeId) {
                        // Deduction type not found, return empty results
                        $rows = collect();
                    } else {
                        $rows = \Modules\Payroll\Models\PayrollDeduction::with('entry.employee')
                            ->whereIn('payroll_entry_id', fn($q) => $q->select('id')->from('payroll_entries')->whereIn('payroll_batch_id', $batches))
                            ->where('deduction_type_id', $deductionTypeId)
                            ->where('amount', '>', 0)
                            ->get();
                    }
                }

                $data = array_merge($data, [
                    'reportRows' => $rows,
                    'grandTotal' => $rows->sum('amount'),
                    'employeeCount' => $rows->count(),
                ]);
                break;
        }

        return view('payroll::reports.index', $data);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GSIS — Filter / Preview page
    //  GET /reports/gsis
    // ─────────────────────────────────────────────────────────────────────────
    public function gsisIndex(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        $year   = (int) $request->get('year',   now()->year);
        $month  = (int) $request->get('month',  now()->month);
        $cutoff = $request->get('cutoff', 'both');

        $summaryExport = new GsisSummaryExport($year, $month, $cutoff);
        $totals        = $summaryExport->getTotals();
        $employeeCount = $summaryExport->getEmployeeCount();
        $grandTotal    = array_sum($totals);

        $labelMap = [
            'GSIS_LIFE_RETIREMENT' => 'Life/Retirement Premium Personal Share',
            'GSIS_EMERGENCY'       => 'Emergency Loan',
            'GSIS_EDUC'            => 'Educational Assistance Loan',
            'GSIS_MPL_LITE'        => 'Multi-Purpose Loan Lite (MPL Lite)',
            'GSIS_CONSO'           => 'Consolidated Loan',
            'GSIS_HELP'            => 'Home Emergency Loan',
            'GSIS_GFAL'            => 'GSIS Financial Assistance Program (GFAL)',
            'GSIS_MPL'             => 'Multi-Purpose Loan (MPL)',
            'GSIS_CPL'             => 'GSIS Computer Loan (CPL)',
            'GSIS_POLICY'          => 'Policy Loan - optional',
            'GSIS_REAL_ESTATE'     => 'Real Estate Loan',
        ];

        $currentYear = now()->year;
        $months = $this->monthNames();

        return view('payroll::reports.gsis', compact(
            'year', 'month', 'cutoff',
            'totals', 'labelMap', 'employeeCount', 'grandTotal',
            'currentYear', 'months'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GSIS — Summary Excel Download
    //  GET /reports/gsis-summary
    // ─────────────────────────────────────────────────────────────────────────
    public function gsisSummary(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        $request->validate([
            'year'   => ['required', 'integer', 'min:2020', 'max:2099'],
            'month'  => ['required', 'integer', 'min:1',    'max:12'],
            'cutoff' => ['nullable', 'in:1st,2nd,both'],
        ]);

        $year   = (int) $request->year;
        $month  = (int) $request->month;
        $cutoff = $request->get('cutoff', 'both');

        $filename = sprintf('GSIS-Summary-%04d-%02d-%s.xlsx', $year, $month, $cutoff);

        return Excel::download(new GsisSummaryExport($year, $month, $cutoff), $filename);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GSIS — Detailed Excel Download
    //  GET /reports/gsis-detailed
    // ─────────────────────────────────────────────────────────────────────────
    public function gsisDetailed(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        $request->validate([
            'year'   => ['required', 'integer', 'min:2020', 'max:2099'],
            'month'  => ['required', 'integer', 'min:1',    'max:12'],
            'cutoff' => ['nullable', 'in:1st,2nd,both'],
        ]);

        $year   = (int) $request->year;
        $month  = (int) $request->month;
        $cutoff = $request->get('cutoff', 'both');

        $filename = sprintf('GSIS-Detailed-%04d-%02d-%s.xlsx', $year, $month, $cutoff);

        return Excel::download(new GsisDetailedExport($year, $month, $cutoff), $filename);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  HDMF — Filter / Preview page
    //  GET /reports/hdmf
    // ─────────────────────────────────────────────────────────────────────────
    public function hdmfIndex(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        $year   = (int) $request->get('year',   now()->year);
        $month  = (int) $request->get('month',  now()->month);
        $cutoff = $request->get('cutoff', 'both');

        // Build per-sheet stats for the preview table
        $p1      = new HdmfP1Export($year, $month, $cutoff);
        $p2      = new HdmfP2Export($year, $month, $cutoff);
        $mpl     = new HdmfMplExport($year, $month, $cutoff);
        $cal     = new HdmfCalExport($year, $month, $cutoff);
        $housing = new HdmfHousingExport($year, $month, $cutoff);

        $sheets = [
            ['label' => 'Pag-IBIG I (P1)',        'program' => 'F1',  'count' => $p1->getCount(),      'total' => $p1->getTotal()],
            ['label' => 'Modified Pag-IBIG II (P2)', 'program' => 'M2', 'count' => $p2->getCount(),   'total' => $p2->getTotal()],
            ['label' => 'Multi-Purpose Loan (MPL)', 'program' => 'MPL', 'count' => $mpl->getCount(),  'total' => $mpl->getTotal()],
            ['label' => 'Calamity Loan (CAL)',      'program' => 'CAL', 'count' => $cal->getCount(),  'total' => $cal->getTotal()],
            ['label' => 'Housing Loan (HL)',         'program' => 'HL',  'count' => $housing->getCount(), 'total' => $housing->getTotal()],
        ];

        $grandTotal    = array_sum(array_column($sheets, 'total'));
        $employeeCount = $p1->getCount(); // P1 is the broadest — use as headline count

        $currentYear = now()->year;
        $months      = $this->monthNames();

        return view('payroll::reports.hdmf', compact(
            'year', 'month', 'cutoff',
            'sheets', 'grandTotal', 'employeeCount',
            'currentYear', 'months'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  HDMF — Combined 5-sheet Excel Download
    //  GET /reports/hdmf/download
    // ─────────────────────────────────────────────────────────────────────────
    public function hdmf(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        $request->validate([
            'year'   => ['required', 'integer', 'min:2020', 'max:2099'],
            'month'  => ['required', 'integer', 'min:1',    'max:12'],
            'cutoff' => ['nullable', 'in:1st,2nd,both'],
        ]);

        $year   = (int) $request->year;
        $month  = (int) $request->month;
        $cutoff = $request->get('cutoff', 'both');

        $filename = sprintf('HDMF-Remittance-%04d-%02d-%s.xlsx', $year, $month, $cutoff);

        return Excel::download(new HdmfRemittanceExport($year, $month, $cutoff), $filename);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PHASE 3A STEP 3 — NEW REMITTANCE METHODS
    // ─────────────────────────────────────────────────────────────────────────

    // ────────────────────────────────────────────────────────────────────────
    // Shared helper: resolve filter params from request
    // ────────────────────────────────────────────────────────────────────────
    private function remittanceFilters(): array
    {
        $year   = (int) request('year',   now()->year);
        $month  = (int) request('month',  now()->month);
        $cutoff = request('cutoff', 'both');

        return [$year, $month, $cutoff];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Master Remittances Hub
    // ────────────────────────────────────────────────────────────────────────
    public function remittancesHub()
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        [$year, $month, $cutoff] = $this->remittanceFilters();

        return view('payroll::reports.remittances', [
            'year'        => $year,
            'month'       => $month,
            'cutoff'      => $cutoff,
            'currentYear' => now()->year,
            'months'      => $this->monthNames(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PHIC — stub (system-generated via portal)
    // ────────────────────────────────────────────────────────────────────────
    public function phicCsv()
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        [$year, $month, $cutoff] = $this->remittanceFilters();
        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        // PhilHealth generates its own billing/remittance PDF from the employer
        // portal. This endpoint produces a plain-text contribution list
        // suitable for manual reconciliation or upload preparation.

        $batches = \Modules\Payroll\Models\PayrollBatch::query()
            ->whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->when($cutoff === '1st', fn($q) => $q->whereDay('period_start', '<=', 15))
            ->when($cutoff === '2nd', fn($q) => $q->whereDay('period_start', '>', 15))
            ->pluck('id');

        $deductionTypeId = \Modules\Payroll\Models\DeductionType::where('code', 'PHIC')->value('id');

        $rows = \Modules\Payroll\Models\PayrollDeduction::with('entry.employee')
            ->whereIn('payroll_entry_id', function ($q) use ($batches) {
                $q->select('id')->from('payroll_entries')->whereIn('payroll_batch_id', $batches);
            })
            ->where('deduction_type_id', $deductionTypeId)
            ->where('amount', '>', 0)
            ->get()
            ->map(function ($ded) {
                $emp = $ded->entry->employee;
                return [
                    strtoupper($emp->last_name . ', ' . $emp->first_name),
                    $emp->philhealth_no ?? '',
                    number_format($emp->semi_monthly_gross * 2, 2),
                    number_format($ded->amount, 2),
                ];
            })
            ->sortBy(fn($r) => $r[0])
            ->values();

        $filename = "PHIC_{$year}_{$month}_contributions.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows, $monthName, $year) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['PhilHealth Contributions — ' . $monthName . ' ' . $year]);
            fputcsv($out, ['Note: Extracted from the system. Generate PDF Billing and PHIC Remittance from the PHIC Employer Portal.']);
            fputcsv($out, []);
            fputcsv($out, ['NAME', 'PHILHEALTH NO.', 'BASIC MONTHLY SALARY', 'EE SHARE']);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fputcsv($out, []);
            fputcsv($out, ['TOTAL', '', '', number_format($rows->sum(fn($r) => (float) str_replace(',', '', $r[3])), 2)]);
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ────────────────────────────────────────────────────────────────────────
    // SSS Voluntary — stub (system-generated via SSS portal)
    // ────────────────────────────────────────────────────────────────────────
    public function sssVoluntary()
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        [$year, $month, $cutoff] = $this->remittanceFilters();
        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        $batches = \Modules\Payroll\Models\PayrollBatch::query()
            ->whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->when($cutoff === '1st', fn($q) => $q->whereDay('period_start', '<=', 15))
            ->when($cutoff === '2nd', fn($q) => $q->whereDay('period_start', '>', 15))
            ->pluck('id');

        $deductionTypeId = \Modules\Payroll\Models\DeductionType::where('code', 'SSS')->value('id');

        $rows = \Modules\Payroll\Models\PayrollDeduction::with('entry.employee')
            ->whereIn('payroll_entry_id', function ($q) use ($batches) {
                $q->select('id')->from('payroll_entries')->whereIn('payroll_batch_id', $batches);
            })
            ->where('deduction_type_id', $deductionTypeId)
            ->where('amount', '>', 0)
            ->get()
            ->map(function ($ded) {
                $emp = $ded->entry->employee;
                return [
                    strtoupper($emp->last_name . ', ' . $emp->first_name),
                    $emp->sss_no ?? '',
                    number_format($ded->amount, 2),
                ];
            })
            ->sortBy(fn($r) => $r[0])
            ->values();

        $filename = "SSS_Voluntary_{$year}_{$month}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows, $monthName, $year) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SSS Voluntary Contributions — ' . $monthName . ' ' . $year]);
            fputcsv($out, ['Note: Extracted from the system and generates PDF Billing and SSS Remittance via SSS Employer Portal.']);
            fputcsv($out, []);
            fputcsv($out, ['NAME', 'SSS NO.', 'AMOUNT']);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fputcsv($out, []);
            fputcsv($out, ['TOTAL', '', number_format($rows->sum(fn($r) => (float) str_replace(',', '', $r[2])), 2)]);
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ────────────────────────────────────────────────────────────────────────
    // LBP Loan
    // ────────────────────────────────────────────────────────────────────────
    public function lbpLoan(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        [$year, $month, $cutoff] = $this->remittanceFilters();
        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        if ($request->has('download')) {
            return Excel::download(
                new LbpLoanExport($year, $month, $cutoff),
                "LBP_Loan_{$year}_{$monthName}.xlsx"
            );
        }

        // Preview: fetch totals for the view
        $batches = \Modules\Payroll\Models\PayrollBatch::query()
            ->whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->when($cutoff === '1st', fn($q) => $q->whereDay('period_start', '<=', 15))
            ->when($cutoff === '2nd', fn($q) => $q->whereDay('period_start', '>', 15))
            ->pluck('id');

        $deductionTypeId = \Modules\Payroll\Models\DeductionType::where('code', 'LBP_LOAN')->value('id');

        if (!$deductionTypeId) {
            // LBP_LOAN deduction type not found, return empty results
            $rows = collect();
        } else {
            $rows = \Modules\Payroll\Models\PayrollDeduction::with('entry.employee')
                ->whereIn('payroll_entry_id', fn($q) => $q->select('id')->from('payroll_entries')->whereIn('payroll_batch_id', $batches))
                ->where('deduction_type_id', $deductionTypeId)
                ->where('amount', '>', 0)
                ->get();
        }

        return view('payroll::reports.remittances', [
            'year'          => $year,
            'month'         => $month,
            'cutoff'        => $cutoff,
            'currentYear'   => now()->year,
            'months'        => $this->monthNames(),
            'activeReport'  => 'lbp',
            'reportRows'    => $rows,
            'grandTotal'    => $rows->sum('amount'),
            'employeeCount' => $rows->count(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // CARESS IX Union Dues
    // ────────────────────────────────────────────────────────────────────────
    public function caressUnion(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        [$year, $month, $cutoff] = $this->remittanceFilters();
        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        if ($request->has('download')) {
            return Excel::download(
                new CaressUnionDuesExport($year, $month, $cutoff),
                "CARESS_UnionDues_{$year}_{$monthName}.xlsx"
            );
        }

        $batches = \Modules\Payroll\Models\PayrollBatch::query()
            ->whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->when($cutoff === '1st', fn($q) => $q->whereDay('period_start', '<=', 15))
            ->when($cutoff === '2nd', fn($q) => $q->whereDay('period_start', '>', 15))
            ->pluck('id');

        $deductionTypeId = \Modules\Payroll\Models\DeductionType::where('code', 'CARESS_UNION')->value('id');

        if (!$deductionTypeId) {
            $rows = collect();
        } else {
            $rows = \Modules\Payroll\Models\PayrollDeduction::with('entry.employee')
                ->whereIn('payroll_entry_id', fn($q) => $q->select('id')->from('payroll_entries')->whereIn('payroll_batch_id', $batches))
                ->where('deduction_type_id', $deductionTypeId)
                ->where('amount', '>', 0)
                ->get();
        }

        return view('payroll::reports.remittances', [
            'year'          => $year,
            'month'         => $month,
            'cutoff'        => $cutoff,
            'currentYear'   => now()->year,
            'months'        => $this->monthNames(),
            'activeReport'  => 'caress_union',
            'reportRows'    => $rows,
            'grandTotal'    => $rows->sum('amount'),
            'employeeCount' => $rows->count(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // CARESS IX Mortuary
    // ────────────────────────────────────────────────────────────────────────
    public function caressMortuary(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        [$year, $month, $cutoff] = $this->remittanceFilters();
        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        if ($request->has('download')) {
            return Excel::download(
                new CaressMortuaryExport($year, $month, $cutoff),
                "CARESS_Mortuary_{$year}_{$monthName}.xlsx"
            );
        }

        $batches = \Modules\Payroll\Models\PayrollBatch::query()
            ->whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->when($cutoff === '1st', fn($q) => $q->whereDay('period_start', '<=', 15))
            ->when($cutoff === '2nd', fn($q) => $q->whereDay('period_start', '>', 15))
            ->pluck('id');

        $deductionTypeId = \Modules\Payroll\Models\DeductionType::where('code', 'CARESS_MORTUARY')->value('id');

        if (!$deductionTypeId) {
            $rows = collect();
        } else {
            $rows = \Modules\Payroll\Models\PayrollDeduction::with('entry.employee')
                ->whereIn('payroll_entry_id', fn($q) => $q->select('id')->from('payroll_entries')->whereIn('payroll_batch_id', $batches))
                ->where('deduction_type_id', $deductionTypeId)
                ->where('amount', '>', 0)
                ->get();
        }

        return view('payroll::reports.remittances', [
            'year'          => $year,
            'month'         => $month,
            'cutoff'        => $cutoff,
            'currentYear'   => now()->year,
            'months'        => $this->monthNames(),
            'activeReport'  => 'caress_mortuary',
            'reportRows'    => $rows,
            'grandTotal'    => $rows->sum('amount'),
            'employeeCount' => $rows->count(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // MASS
    // ────────────────────────────────────────────────────────────────────────
    public function mass(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        [$year, $month, $cutoff] = $this->remittanceFilters();
        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        if ($request->has('download')) {
            return Excel::download(
                new MassExport($year, $month, $cutoff),
                "MASS_{$year}_{$monthName}.xlsx"
            );
        }

        $batches = \Modules\Payroll\Models\PayrollBatch::query()
            ->whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->when($cutoff === '1st', fn($q) => $q->whereDay('period_start', '<=', 15))
            ->when($cutoff === '2nd', fn($q) => $q->whereDay('period_start', '>', 15))
            ->pluck('id');

        $deductionTypeId = \Modules\Payroll\Models\DeductionType::where('code', 'MASS')->value('id');

        if (!$deductionTypeId) {
            $rows = collect();
        } else {
            $rows = \Modules\Payroll\Models\PayrollDeduction::with('entry.employee')
                ->whereIn('payroll_entry_id', fn($q) => $q->select('id')->from('payroll_entries')->whereIn('payroll_batch_id', $batches))
                ->where('deduction_type_id', $deductionTypeId)
                ->where('amount', '>', 0)
                ->get();
        }

        return view('payroll::reports.remittances', [
            'year'          => $year,
            'month'         => $month,
            'cutoff'        => $cutoff,
            'currentYear'   => now()->year,
            'months'        => $this->monthNames(),
            'activeReport'  => 'mass',
            'reportRows'    => $rows,
            'grandTotal'    => $rows->sum('amount'),
            'employeeCount' => $rows->count(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Provident Fund
    // ────────────────────────────────────────────────────────────────────────
    public function providentFund(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        [$year, $month, $cutoff] = $this->remittanceFilters();
        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        if ($request->has('download')) {
            return Excel::download(
                new ProvidentFundExport($year, $month, $cutoff),
                "ProvidentFund_{$year}_{$monthName}.xlsx"
            );
        }

        $batches = \Modules\Payroll\Models\PayrollBatch::query()
            ->whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->when($cutoff === '1st', fn($q) => $q->whereDay('period_start', '<=', 15))
            ->when($cutoff === '2nd', fn($q) => $q->whereDay('period_start', '>', 15))
            ->pluck('id');

        $deductionTypeId = \Modules\Payroll\Models\DeductionType::where('code', 'PROVIDENT_FUND')->value('id');

        if (!$deductionTypeId) {
            $rows = collect();
        } else {
            $rows = \Modules\Payroll\Models\PayrollDeduction::with('entry.employee')
                ->whereIn('payroll_entry_id', fn($q) => $q->select('id')->from('payroll_entries')->whereIn('payroll_batch_id', $batches))
                ->where('deduction_type_id', $deductionTypeId)
                ->where('amount', '>', 0)
                ->get();
        }

        return view('payroll::reports.remittances', [
            'year'          => $year,
            'month'         => $month,
            'cutoff'        => $cutoff,
            'currentYear'   => now()->year,
            'months'        => $this->monthNames(),
            'activeReport'  => 'provident_fund',
            'reportRows'    => $rows,
            'grandTotal'    => $rows->sum('amount'),
            'employeeCount' => $rows->count(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // BTR Refund
    // ────────────────────────────────────────────────────────────────────────
    public function btrRefund(Request $request)
    {
        $this->authorizeRole(['payroll_officer', 'hrmo', 'accountant']);

        [$year, $month, $cutoff] = $this->remittanceFilters();
        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        if ($request->has('download')) {
            return Excel::download(
                new BtrRefundExport($year, $month, $cutoff),
                "BTR_Refund_{$year}_{$monthName}.xlsx"
            );
        }

        $batches = \Modules\Payroll\Models\PayrollBatch::query()
            ->whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->when($cutoff === '1st', fn($q) => $q->whereDay('period_start', '<=', 15))
            ->when($cutoff === '2nd', fn($q) => $q->whereDay('period_start', '>', 15))
            ->pluck('id');

        $deductionTypes = \Modules\Payroll\Models\DeductionType::whereIn('code', ['WHT', 'REFUND_VARIOUS'])->pluck('id');

        $rows = \Modules\Payroll\Models\PayrollDeduction::with(['entry.employee', 'deductionType'])
            ->whereIn('payroll_entry_id', function ($q) use ($batches) {
                $q->select('id')->from('payroll_entries')->whereIn('payroll_batch_id', $batches);
            })
            ->whereIn('deduction_type_id', $deductionTypes)
            ->where('amount', '>', 0)
            ->get();

        return view('payroll::reports.remittances', [
            'year'          => $year,
            'month'         => $month,
            'cutoff'        => $cutoff,
            'currentYear'   => now()->year,
            'months'        => $this->monthNames(),
            'activeReport'  => 'btr',
            'reportRows'    => $rows,
            'grandTotal'    => $rows->sum('amount'),
            'employeeCount' => $rows->count(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Private helpers
    // ─────────────────────────────────────────────────────────────────────────
    private function authorizeRole(array $roles): void
    {
        // Use native authorize() instead of manual role checks
        abort(403, 'Use $this->authorize() with proper policies instead of authorizeRole()');
    }

    private function monthNames(): array
    {
        return [
            1 => 'January',   2 => 'February',  3 => 'March',
            4 => 'April',     5 => 'May',        6 => 'June',
            7 => 'July',      8 => 'August',     9 => 'September',
            10 => 'October',  11 => 'November', 12 => 'December',
        ];
    }
}
