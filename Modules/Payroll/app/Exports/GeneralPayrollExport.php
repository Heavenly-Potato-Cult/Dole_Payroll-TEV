<?php

namespace Modules\Payroll\Exports;

use App\SharedKernel\Models\PsipopOffice;
use Modules\Payroll\Models\AttendanceSnapshot;
use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Models\PayrollEntry;
use Modules\Payroll\Services\PayrollComputationService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * General Payroll Register workbook (DOLE RO9 — "01A-General-Payroll-Monthly").
 *
 * Two sheets, matching the DOLE-provided sample workbook:
 *   1. "General Payroll Register" — one row per employee, grouped into
 *      PSIPOP office sections (in psipop_offices.sort_order), each with a
 *      subtotal row, followed by a "TOTAL PER DIVISION" recap block and a
 *      grand total. Basic/PERA/RATA/Gross/deductions/Net Amount are always
 *      the FULL MONTH figures — no cutoff-based scaling. 1st/2nd Cutoff Net
 *      Pay are separate columns (not the old approximation), sourced from
 *      PayrollComputationService::computeCutoffSplit() — the same
 *      authoritative split already used in the Employee module and Regular
 *      Payroll module. See GeneralPayrollRegisterSheet.
 *   2. "New Net Pay" — Name / Position / Plantilla / 1st-cutoff net /
 *      2nd-cutoff net / total / difference, always both cutoffs. See
 *      NewNetPaySheet.
 *
 * `?cutoff=` now only controls WHICH of the two split columns the register
 * sheet shows (both / 1st only / 2nd only) — it no longer scales any other
 * figure. Batches are monthly only (no `cutoff` column on payroll_batches —
 * see PayrollComputationService::computeEntry() docblock: "one employee,
 * one monthly batch"), so there was never a legitimate per-cutoff subset of
 * rows to filter down to.
 */
class GeneralPayrollExport implements WithMultipleSheets
{
    protected int $year;
    protected int $month;
    protected string $cutoff; // '1st' | '2nd' | 'both' — see class docblock

    /**
     * Ordered groups: [
     *   ['office' => PsipopOffice|null, 'rows' => [['entry' => PayrollEntry, 'amounts' => array], ...]],
     *   ...
     * ] — ordered by psipop_offices.sort_order, employees within a group
     * ordered by last name.
     */
    protected array $groups = [];

    /** Deduction columns present in this period: [id => ['name' => ..., 'order' => ...]] */
    protected array $deductionColumns = [];

    protected int $employeeCount = 0;
    protected float $totalGross = 0;
    protected float $totalNet   = 0;

    /** True if at least one entry had no AttendanceSnapshot to derive a real cutoff split from (fell back to 50/50). */
    protected bool $hasEstimatedSplit = false;

    public function __construct(int $year, int $month, string $cutoff = 'both')
    {
        $this->year   = $year;
        $this->month  = $month;
        $this->cutoff = $cutoff;

        $this->compute();
    }

    protected function compute(): void
    {
        $batchIds = PayrollBatch::query()
            ->where('period_year', $this->year)
            ->where('period_month', $this->month)
            ->pluck('id');

        $entries = PayrollEntry::query()
            ->whereIn('payroll_batch_id', $batchIds)
            ->with([
                'employee.psipopOffice',
                'deductions.deductionType',
                'allowances.allowanceType',
            ])
            ->get();

        // ── Discover which deduction types actually appear (amount > 0) ──
        foreach ($entries as $entry) {
            foreach ($entry->deductions as $ded) {
                if ((float) $ded->amount <= 0 || !$ded->deductionType) {
                    continue;
                }
                $typeId = $ded->deductionType->id;
                if (!isset($this->deductionColumns[$typeId])) {
                    $this->deductionColumns[$typeId] = [
                        'name'  => $ded->deductionType->name,
                        'order' => $ded->deductionType->display_order ?? 999,
                    ];
                }
            }
        }
        uasort($this->deductionColumns, fn ($a, $b) => $a['order'] <=> $b['order']);

        // ── Resolve display amounts per entry and bucket into PSIPOP groups ──
        $buckets = []; // officeId (or 0 for "no office") => ['office' => PsipopOffice|null, 'rows' => []]

        foreach ($entries as $entry) {
            $amounts = $this->resolveEntryAmounts($entry);

            $office   = $entry->employee->psipopOffice ?? null;
            $officeId = $office->id ?? 0;

            if (!isset($buckets[$officeId])) {
                $buckets[$officeId] = ['office' => $office, 'rows' => []];
            }
            $buckets[$officeId]['rows'][] = ['entry' => $entry, 'amounts' => $amounts];

            $this->employeeCount++;
            $this->totalGross += $amounts['gross_income'];
            $this->totalNet   += $amounts['net_amount'];
        }

        // Sort groups by sort_order (the "no office" bucket, officeId 0,
        // should not occur in practice — Employee::booted() always assigns
        // the Unassigned office — but sorts last defensively if it does).
        uasort($buckets, function ($a, $b) {
            $orderA = $a['office']->sort_order ?? PHP_INT_MAX;
            $orderB = $b['office']->sort_order ?? PHP_INT_MAX;
            return $orderA <=> $orderB;
        });

        // Sort employees within each group by last name.
        foreach ($buckets as &$bucket) {
            usort($bucket['rows'], fn ($a, $b) => $a['entry']->employee->last_name <=> $b['entry']->employee->last_name
                ?: $a['entry']->employee->first_name <=> $b['entry']->employee->first_name);
        }
        unset($bucket);

        $this->groups = array_values($buckets);
    }

    /**
     * Resolve an employee's display amounts. Basic/PERA/RATA/Gross/
     * deductions/Net Amount are always the full monthly PayrollEntry
     * values — no scaling. 1st/2nd cutoff net pay are computed separately
     * via PayrollComputationService::computeCutoffSplit() and returned
     * alongside, for the register sheet's dedicated split columns.
     */
    protected function resolveEntryAmounts(PayrollEntry $entry): array
    {
        $dedByType = [];
        foreach ($entry->deductions as $ded) {
            if ($ded->deductionType) {
                $dedByType[$ded->deductionType->id] = ($dedByType[$ded->deductionType->id] ?? 0) + (float) $ded->amount;
            }
        }

        [$firstCutoffNet, $secondCutoffNet, $estimated] = $this->resolveCutoffNetPay($entry);

        return [
            'basic_salary'      => (float) $entry->basic_salary,
            'pera'              => $this->allowanceAmount($entry, 'PERA'),
            'rata'              => $this->allowanceAmount($entry, 'RATA'),
            'gross_income'      => (float) $entry->gross_income,
            'dedByType'         => $dedByType,
            'total_deductions'  => (float) $entry->total_deductions,
            'net_amount'        => (float) $entry->net_amount,
            'first_cutoff_net'  => $firstCutoffNet,
            'second_cutoff_net' => $secondCutoffNet,
            'split_estimated'   => $estimated,
        ];
    }

    /**
     * Authoritative 1st/2nd cutoff net pay for one entry, via the same
     * PayrollComputationService::computeCutoffSplit() used by the Employee
     * module and Regular Payroll module. Falls back to an even 50/50 split
     * (flagged) only when no AttendanceSnapshot exists for this employee/batch.
     *
     * @return array{0: float, 1: float, 2: bool} [firstCutoffNet, secondCutoffNet, estimated]
     */
    protected function resolveCutoffNetPay(PayrollEntry $entry): array
    {
        $snapshot = AttendanceSnapshot::query()
            ->where('payroll_batch_id', $entry->payroll_batch_id)
            ->where('employee_id', $entry->employee_id)
            ->first();

        if (!$snapshot) {
            $this->hasEstimatedSplit = true;
            $half = round((float) $entry->net_amount / 2, 2);
            return [$half, round((float) $entry->net_amount - $half, 2), true];
        }

        $split = app(PayrollComputationService::class)->computeCutoffSplit($entry, $snapshot);

        return [
            (float) $split['first_cutoff']['net_amount'],
            (float) $split['second_cutoff']['net_amount'],
            false,
        ];
    }

    /**
     * Resolve an allowance amount (PERA / RATA) for an entry.
     * Prefers the payroll_entry_allowances relation (joined via allowance_types.code);
     * falls back to a denormalized column on the entry if the relation yields nothing,
     * since some setups may keep PERA/RATA flattened onto payroll_entries directly.
     */
    protected function allowanceAmount(PayrollEntry $entry, string $code): float
    {
        $fromRelation = $entry->allowances
            ->filter(fn ($a) => $a->allowanceType && $a->allowanceType->code === $code)
            ->sum('amount');

        if ($fromRelation > 0) {
            return (float) $fromRelation;
        }

        $column = strtolower($code); // 'pera' / 'rata'
        return (float) ($entry->{$column} ?? 0);
    }

    // ── Workbook assembly ────────────────────────────────────────────────

    public function sheets(): array
    {
        return [
            new GeneralPayrollRegisterSheet($this->groups, $this->deductionColumns, $this->year, $this->month, $this->cutoff, $this->hasEstimatedSplit),
            new NewNetPaySheet($this->year, $this->month),
        ];
    }

    // ── Public getters (used by PayrollReportController for the blade preview) ──

    public function getEmployeeCount(): int
    {
        return $this->employeeCount;
    }

    public function getGrandTotalNet(): float
    {
        return $this->totalNet;
    }

    public function getGrandTotalGross(): float
    {
        return $this->totalGross;
    }

    public function getDeductionColumns(): array
    {
        return $this->deductionColumns;
    }

    /**
     * @return array<int, array{name: string, count: int}> One entry per
     * PSIPOP office group actually present this period, in sort_order.
     */
    public function getOfficeGroups(): array
    {
        return array_map(fn ($g) => [
            'name'  => $g['office']->name ?? PsipopOffice::NAME_UNASSIGNED,
            'count' => count($g['rows']),
        ], $this->groups);
    }

    public function hasEstimatedSplit(): bool
    {
        return $this->hasEstimatedSplit;
    }
}
