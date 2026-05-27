<?php

namespace Modules\Payroll\Services;

use App\SharedKernel\Models\Employee;
use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Models\PayrollEntry;
use Modules\Payroll\Models\PayrollDeduction;
use Modules\Payroll\Traits\TableIVConverter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollComputationService
{
    use TableIVConverter;

    /**
     * Fixed working-day denominator per DOLE RO9 payroll rules.
     * Semi-monthly: 22 days per cutoff
     * Monthly: 44 days per month (22 days × 2 cutoffs)
     */
    const DENOMINATOR_SEMI_MONTHLY = 22;
    const DENOMINATOR_MONTHLY = 44;

    /**
     * Whether to compute payroll on a monthly basis instead of semi-monthly.
     * When true, full monthly salary is used instead of dividing by 2.
     * Set this via config or environment variable.
     */
    protected bool $monthlyComputation = false;

    public function __construct(
        protected DeductionService $deductionService
    ) {
        // Check if monthly computation is enabled via config
        $this->monthlyComputation = config('payroll.monthly_computation', false);
    }

    /**
     * Get the appropriate denominator based on computation mode.
     */
    protected function getDenominator(): int
    {
        return $this->monthlyComputation ? self::DENOMINATOR_MONTHLY : self::DENOMINATOR_SEMI_MONTHLY;
    }

    /**
     * Compute a full payroll entry for one employee in a batch.
     * Persists PayrollEntry + PayrollDeduction rows (upsert-style).
     *
     * @param  Employee      $employee
     * @param  PayrollBatch  $batch
     * @param  array         $attendance  Shape:
     *   [
     *     'lwop_days'       => float,   // leave-without-pay days already approved
     *     'late_minutes'    => int,     // cumulative minutes late for the cut-off
     *     'undertime_mins'  => int,     // cumulative undertime minutes
     *     'ytd_gross'       => float,   // year-to-date gross BEFORE this payroll (for WHT)
     *   ]
     * @return PayrollEntry  (loaded with deductions relation)
     */
    public function computeEntry(Employee $employee, PayrollBatch $batch, array $attendance = []): PayrollEntry
    {
        // ── 1. Attendance defaults ────────────────────────────────────────
        $lwopDays      = (float) ($attendance['lwop_days']      ?? 0);
        $lateMinutes   = (int)   ($attendance['late_minutes']   ?? 0);
        $undertimeMins = (int)   ($attendance['undertime_mins'] ?? 0);
        $ytdGross      = (float) ($attendance['ytd_gross']      ?? 0);

        // ── 2. Gross income components ────────────────────────────────────
        $basicMonthly = (float) $employee->basic_monthly_salary;
        $peraMonthly  = (float) $employee->pera_amount;      // ₱2,000 for most
        $rataMonthly  = (float) ($employee->rata ?? 0);

        // Use full monthly salary for monthly computation, otherwise divide by 2 for semi-monthly
        if ($this->monthlyComputation) {
            $salaryEarned = round($basicMonthly, 2);
            $peraEarned   = round($peraMonthly, 2);
            $rataEarned   = round($rataMonthly, 2);
        } else {
            $salaryEarned = round($basicMonthly / 2, 2);
            $peraEarned   = round($peraMonthly  / 2, 2);
            $rataEarned   = round($rataMonthly  / 2, 2);
        }
        $grossEarned  = $salaryEarned + $peraEarned + $rataEarned;

        // ── 3. Attendance deductions ──────────────────────────────────────
        //   Daily rate  = basic_monthly / denominator (22 for semi-monthly, 44 for monthly)
        //   Hourly rate = daily_rate / 8
        //
        //   LWOP   = (lwop_days / denominator) * basic_monthly   [full-day absences]
        //   Late   = hours_late * hourly_rate + Table-IV(remaining_mins) * daily_rate
        //   Undertime follows the same rule as late minutes
        //
        //   Per DOLE RO9 rules: deductions hit LEAVE CREDITS first.
        //   The caller / AttendanceService resolves leave credits before
        //   passing lwop_days here — only credit-exhausted days reach this service.

        $denominator = $this->getDenominator();
        $dailyRate  = round($basicMonthly / $denominator, 6);
        $hourlyRate = round($dailyRate / 8, 6);

        // LWOP deduction
        $lwopDeduction = round(($lwopDays / $denominator) * $basicMonthly, 2);

        // Tardiness — separate hour and minute components
        $lateHours   = intdiv($lateMinutes, 60);
        $lateRemMins = $lateMinutes % 60;
        $tardiness   = round(
            ($lateHours * $hourlyRate)
            + ($this->minuteEquivalent($lateRemMins) * $dailyRate),
            2
        );

        // Undertime — same conversion as tardiness
        $utHours      = intdiv($undertimeMins, 60);
        $utRemMins    = $undertimeMins % 60;
        $undertimeDed = round(
            ($utHours * $hourlyRate)
            + ($this->minuteEquivalent($utRemMins) * $dailyRate),
            2
        );

        $totalAttendanceDed = round($lwopDeduction + $tardiness + $undertimeDed, 2);

        // ── 4. Resolve deduction lines via DeductionService ──────────────
        //
        //   DeductionService::resolveDeductions() handles:
        //     - Loading active DeductionTypes in display_order
        //     - Loading employee enrollments active on the payroll date
        //     - Computing PAG_IBIG_1, PHILHEALTH, GSIS_LIFE_RETIREMENT, WITHHOLDING_TAX
        //     - Returning only lines with amount > 0
        //
        //   This service no longer duplicates that logic.

        $deductionLines = $this->deductionService->resolveDeductions(
            $employee,
            $batch,
            $ytdGross
        );

        $totalDeductions = round(
            collect($deductionLines)->sum('amount') + $totalAttendanceDed,
            2
        );

        // ── 5. Net pay ────────────────────────────────────────────────────
        $netAmount = round($grossEarned - $totalDeductions, 2);

        // ── 6. Persist ────────────────────────────────────────────────────
        return DB::transaction(function () use (
            $employee, $batch,
            $salaryEarned, $peraEarned, $rataEarned,
            $lwopDeduction, $tardiness, $undertimeDed,
            $totalDeductions, $netAmount,
            $deductionLines
        ) {
            /** @var PayrollEntry $entry */
            $entry = PayrollEntry::updateOrCreate(
                [
                    'payroll_batch_id' => $batch->id,
                    'employee_id'      => $employee->id,
                ],
                [
                    'basic_salary'     => $salaryEarned,
                    'pera'             => $peraEarned,
                    'rata'             => $rataEarned,
                    'gross_income'     => round($salaryEarned + $peraEarned + $rataEarned, 2),
                    'lwop_deduction'   => $lwopDeduction,
                    'tardiness'        => $tardiness,
                    'undertime'        => $undertimeDed,
                    'total_deductions' => $totalDeductions,
                    'net_amount'       => $netAmount,
                ]
            );

            // Replace deduction lines fresh on every compute
            $entry->deductions()->delete();

            foreach ($deductionLines as $line) {
                PayrollDeduction::create([
                    'payroll_entry_id'  => $entry->id,
                    'deduction_type_id' => $line['deduction_type_id'],
                    'code'              => $line['code'],
                    'name'              => $line['name'],
                    'amount'            => $line['amount'],
                ]);
            }

            return $entry->load('deductions');
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Attendance helper — kept here because it belongs to attendance
    //  logic (TableIVConverter trait), not deduction computation.
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Wraps minutesToDays() from the TableIVConverter trait.
     * This is the same method AttendanceService uses — consistent across the app.
     *
     * Example: 15 minutes → 0.031 (Table IV lookup, NOT 0.03125 computed)
     */
    protected function minuteEquivalent(int $minutes): float
    {
        if ($minutes <= 0) return 0.0;

        // minutesToDays() is defined in Modules\Payroll\Traits\TableIVConverter
        return (float) $this->minutesToDays($minutes);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Batch-level entry point
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Run computeEntry() for every active employee in one go.
     * Called by PayrollController@compute.
     *
     * @param  PayrollBatch $batch
     * @param  array        $attendanceMap  [ employee_id => attendance array ]
     * @return array  ['computed' => int, 'errors' => string[]]
     */
    public function computeBatch(PayrollBatch $batch, array $attendanceMap = []): array
    {
        $employees = \App\SharedKernel\Models\Employee::where('status', 'active')
            ->with(['deductionEnrollments.deductionType'])
            ->get();

        $computed = 0;
        $errors   = [];

        foreach ($employees as $employee) {
            try {
                $attendance = $attendanceMap[$employee->id] ?? [];
                $this->computeEntry($employee, $batch, $attendance);
                $computed++;
            } catch (\Throwable $e) {
                Log::error("Payroll compute error — Employee #{$employee->id}: " . $e->getMessage());
                $errors[] = "#{$employee->id} {$employee->full_name}: " . $e->getMessage();
            }
        }

        return compact('computed', 'errors');
    }
}
