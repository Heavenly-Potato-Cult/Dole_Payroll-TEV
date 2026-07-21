<?php

namespace Modules\Payroll\Services;

use App\SharedKernel\Models\Employee;
use Carbon\Carbon;
use Modules\Payroll\Models\Allowances\AllowanceAssignmentEntry;
use Modules\Payroll\Models\Allowances\AllowanceType;
use Modules\Payroll\Models\Allowances\EmployeeAllowance;
use Modules\Payroll\Models\Allowances\PayrollEntryAllowance;
use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Models\PayrollEntry;

class AllowanceService
{
    /**
     * Resolve allowance lines for an employee in a payroll period.
     *
     * Thin wrapper around resolveForPeriod() — kept for backward
     * compatibility with existing PayrollBatch call sites. No behavior
     * change vs. the previous implementation.
     *
     * @return array<int, array{allowance_type_id: int, code: string, name: string, amount: float}>
     */
    public function resolveForPayroll(Employee $employee, PayrollBatch $batch): array
    {
        $periodStart = Carbon::parse($batch->period_start ?? "{$batch->period_year}-{$batch->period_month}-01");

        // period_end is now optional; fall back to end-of-month when absent.
        $periodEnd = $batch->period_end
            ? Carbon::parse($batch->period_end)
            : $periodStart->copy()->endOfMonth();

        return $this->resolveForPeriod(
            $employee,
            (int) $batch->period_year,
            (int) $batch->period_month,
            $periodStart,
            $periodEnd
        );
    }

    /**
     * Resolve allowance lines for an employee over an arbitrary period.
     *
     * This is the extracted core of what used to live only in
     * resolveForPayroll(), so both the regular PayrollBatch flow and any
     * other batch type (e.g. SpecialPayrollBatch, which is a different
     * Eloquent model and can't be type-hinted here) can share one
     * resolution/precedence path instead of forking the logic.
     *
     * Sources (later sources override earlier for the same allowance type):
     *   1. Active employee_allowances (standing/recurring)
     *   2. Legacy PERA fallback (employee.pera column, if no standing PERA line)
     *   3. Released allowance_assignment entries for the same period_year +
     *      period_month (applies regardless of caller — no special-casing
     *      of newly-hired employees here; see Goal-1 Q2 discussion)
     *
     * @return array<int, array{allowance_type_id: int, code: string, name: string, amount: float}>
     */
    public function resolveForPeriod(
        Employee $employee,
        int $periodYear,
        int $periodMonth,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        $lines = [];

        // --- 1. Standing / recurring allowances -----------------------------------
        $standing = EmployeeAllowance::query()
            ->with('allowanceType')
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->where('effectivity_date', '<=', $periodEnd->toDateString())
            ->where(function ($q) use ($periodStart) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', $periodStart->toDateString());
            })
            ->get()
            ->filter(fn ($row) => $row->allowanceType?->is_active);

        foreach ($standing as $row) {
            $lines[$row->allowance_type_id] = $this->lineFromType(
                $row->allowanceType,
                (float) $row->amount
            );
        }

        // --- 2. Legacy PERA fallback (employee.pera column) -----------------------
        $hasPera = collect($lines)->contains(fn ($l) => $l['code'] === 'PERA');
        if (! $hasPera) {
            $peraType = AllowanceType::where('code', 'PERA')->first();
            $peraAmt  = (float) ($employee->pera ?? 0);
            if ($peraType && $peraAmt > 0) {
                $lines[$peraType->id] = $this->lineFromType($peraType, $peraAmt);
            }
        }

        // --- 3. Assignment entries (override standing for the same type) ---------------
        $assignmentEntries = AllowanceAssignmentEntry::query()
            ->with(['allowanceType', 'assignment'])
            ->where('employee_id', $employee->id)
            ->whereHas('assignment', function ($q) use ($periodYear, $periodMonth) {
                $q->where('period_year', $periodYear)
                  ->where('period_month', $periodMonth)
                  ->whereIn('status', ['released']);
            })
            ->get()
            ->filter(fn ($row) => $row->allowanceType?->is_active);

        foreach ($assignmentEntries as $entry) {
            $lines[$entry->allowance_type_id] = $this->lineFromType(
                $entry->allowanceType,
                (float) $entry->amount
            );
        }

        return array_values($lines);
    }

    /**
     * Persist resolved allowance lines on a payroll entry (upsert-style).
     *
     * @param  array<int, array{allowance_type_id: int, code: string, name: string, amount: float}>  $lines
     */
    public function syncPayrollEntryAllowances(PayrollEntry $entry, array $lines): void
    {
        $entry->allowances()->delete();

        foreach ($lines as $line) {
            PayrollEntryAllowance::create([
                'payroll_entry_id'  => $entry->id,
                'allowance_type_id' => $line['allowance_type_id'],
                'code'              => $line['code'],
                'name'              => $line['name'],
                'amount'            => round($line['amount'], 2),
            ]);
        }
    }

    /** @return array{pera: float, rata: float, total: float} */
    public function summarize(array $lines): array
    {
        $pera  = 0.0;
        $rata  = 0.0;
        $total = 0.0;

        foreach ($lines as $line) {
            $amt   = (float) $line['amount'];
            $total += $amt;
            if ($line['code'] === 'PERA') {
                $pera += $amt;
            } elseif ($line['code'] === 'RATA') {
                $rata += $amt;
            }
        }

        return [
            'pera'  => round($pera, 2),
            'rata'  => round($rata, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Build allowance column metadata for the payroll register table.
     *
     * @return array{
     *   columns: \Illuminate\Support\Collection,
     *   totals: array<string, float>,
     *   amountsForEntry: callable(PayrollEntry): array<string, float>
     * }
     */
    public function buildRegisterColumns(iterable $entries): array
    {
        $columns = AllowanceType::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $entryList = $entries instanceof \Illuminate\Support\Collection
            ? $entries
            : collect($entries);

        $rowAmounts = $entryList->mapWithKeys(fn ($entry) => [
            $entry->id => $this->resolveEntryAllowanceAmounts($entry, $columns),
        ]);

        $totals = [];
        foreach ($columns as $col) {
            $totals[$col->code] = round(
                $rowAmounts->sum(fn ($amounts) => $amounts[$col->code] ?? 0),
                2
            );
        }

        $amountsForEntry = fn (PayrollEntry $entry) => $this->resolveEntryAllowanceAmounts($entry, $columns);

        return compact('columns', 'totals', 'amountsForEntry');
    }

    /** @return array<string, float> */
    private function resolveEntryAllowanceAmounts(PayrollEntry $entry, $columns): array
    {
        $amounts = [];

        foreach ($columns as $col) {
            $line = $entry->relationLoaded('allowances')
                ? $entry->allowances->firstWhere('code', $col->code)
                : null;

            if ($line) {
                $amt = (float) $line->amount;
            } elseif ($col->code === 'PERA') {
                $amt = (float) $entry->pera;
            } elseif ($col->code === 'RATA') {
                $amt = (float) $entry->rata;
            } else {
                $amt = 0.0;
            }

            $amounts[$col->code] = round($amt, 2);
        }

        return $amounts;
    }

    /**
     * Filter resolved allowance lines down to a selected set of allowance
     * type IDs and pro-rate each by (working_days / divisor), rounding the
     * same way NewlyHiredPayrollService rounds basic salary and PERA.
     *
     * PERA is always excluded here — it's already a first-class column in
     * NewlyHiredPayrollService::compute() (pro-rated separately from
     * employee->pera), so including it again via the generic allowance
     * path would double-count it.
     *
     * @param  array<int, array{allowance_type_id:int, code:string, name:string, amount:float}>  $lines
     * @param  int[]  $selectedTypeIds  Allowance type IDs the preparer checked in the UI
     * @return array<int, array{allowance_type_id:int, code:string, name:string, full_amount:float, amount:float}>
     */
    public function proRateLines(array $lines, array $selectedTypeIds, int $workingDays, int $divisor = 22): array
    {
        $selected = array_flip($selectedTypeIds);

        $result = [];
        foreach ($lines as $line) {
            if ($line['code'] === 'PERA') {
                continue;
            }
            if (! isset($selected[$line['allowance_type_id']])) {
                continue;
            }

            $fullAmount = (float) $line['amount'];
            $prorated   = round(($fullAmount / $divisor) * $workingDays, 2);

            $result[] = [
                'allowance_type_id' => $line['allowance_type_id'],
                'code'              => $line['code'],
                'name'              => $line['name'],
                'full_amount'       => round($fullAmount, 2),
                'amount'            => $prorated,
            ];
        }

        return $result;
    }

    private function lineFromType(AllowanceType $type, float $amount): array
    {
        return [
            'allowance_type_id' => $type->id,
            'code'              => $type->code,
            'name'              => $type->name,
            'amount'            => round($amount, 2),
        ];
    }
}
