<?php

namespace Modules\Payroll\Services;

use App\SharedKernel\Models\Employee;
use Modules\Payroll\Models\AttendanceSnapshot;
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
     *
     * Phase 3: Semi-monthly denominator is kept only for the cutoff-split
     * helper (reporting / payslip breakdown). All live computation now always
     * uses the monthly denominator.
     */
    const DENOMINATOR_SEMI_MONTHLY = 22;
    const DENOMINATOR_MONTHLY      = 44;

    public function __construct(
        protected DeductionService $deductionService,
        protected \Modules\Payroll\Services\AllowanceService $allowanceService
    ) {}

    // ═══════════════════════════════════════════════════════════════════
    //  Primary entry point — one employee, one monthly batch
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Compute a full MONTHLY payroll entry for one employee in a batch.
     * Persists PayrollEntry + PayrollDeduction rows (upsert-style).
     *
     * Phase 3 changes vs old semi-monthly approach:
     *  - Always uses DENOMINATOR_MONTHLY (44) — no config flag needed.
     *  - Attendance array is the FULL-MONTH snapshot (pulled once per month).
     *  - The optional $snapshot param allows the caller to pass the loaded
     *    AttendanceSnapshot model so cutoff split helpers work without an
     *    extra query.
     *
     * Fix log:
     *  2026-06-10  daysWorked is now derived from the attendance array and
     *              passed to resolveDeductions() so GSIS is prorated correctly
     *              for employees with LWOP days in the period.
     *
     * @param  Employee               $employee
     * @param  PayrollBatch           $batch
     * @param  array                  $attendance  Shape:
     *   [
     *     'lwop_days'       => float,   // full-month LWOP days (credit-exhausted)
     *     'late_minutes'    => int,     // full-month cumulative late minutes
     *     'undertime_mins'  => int,     // full-month cumulative undertime minutes
     *     'ytd_gross'       => float,   // year-to-date gross BEFORE this payroll (WHT)
     *     'days_present'    => float,   // (optional) days actually worked — for GSIS proration
     *   ]
     * @param  AttendanceSnapshot|null $snapshot   Optional — needed only for cutoff split
     * @return PayrollEntry  (loaded with deductions relation)
     */
    public function computeEntry(
        Employee $employee,
        PayrollBatch $batch,
        array $attendance = [],
        ?AttendanceSnapshot $snapshot = null
    ): PayrollEntry {
        // ── 1. Attendance defaults ────────────────────────────────────────
        $lwopDays      = (float) ($attendance['lwop_days']      ?? 0);
        $lateMinutes   = (int)   ($attendance['late_minutes']   ?? 0);
        $undertimeMins = (int)   ($attendance['undertime_mins'] ?? $attendance['undertime_minutes'] ?? 0);
        $ytdGross      = (float) ($attendance['ytd_gross']      ?? 0);

        // Days worked — used for GSIS proration ONLY when employee has LWOP.
        // Only pass daysWorked to resolveDeductions() when lwopDays > 0;
        // otherwise pass null to skip proration entirely (full month worked).
        $totalDays  = self::DENOMINATOR_SEMI_MONTHLY; // 22 working days/month
        $daysWorked = isset($attendance['days_present'])
            ? (int) $attendance['days_present']
            : (int) max(0, $totalDays - (int) $lwopDays);

        // Only prorate GSIS when there are actual LWOP days.
        // Passing null tells resolveDeductions() to use the full monthly amount.
        $gsisProrateDays = $lwopDays > 0 ? $daysWorked : null;

        // ── 2. Gross income components (full month) ───────────────────────
        $basicMonthly = (float) $employee->basic_monthly_salary;

        $allowanceLines = $this->allowanceService->resolveForPayroll($employee, $batch);
        $allowanceSum   = $this->allowanceService->summarize($allowanceLines);

        // Full monthly salary — no longer divided by 2
        $salaryEarned = round($basicMonthly, 2);
        $peraEarned   = $allowanceSum['pera'];
        $rataEarned   = $allowanceSum['rata'];
        $totalAllowances = $allowanceSum['total'];
        $grossEarned  = $salaryEarned + $totalAllowances;

        // ── 3. Attendance deductions (monthly denominator = 44) ───────────
        //
        //   Daily rate  = basic_monthly / 44
        //   Hourly rate = daily_rate / 8
        //
        //   LWOP   = (lwop_days / 44) * basic_monthly
        //   Late   = hours_late * hourly_rate + Table-IV(remaining_mins) * daily_rate
        //   Undertime follows the same rule as late minutes
        //
        //   Per DOLE RO9: deductions hit LEAVE CREDITS first.
        //   AttendanceService resolves leave credits before passing lwop_days
        //   here — only credit-exhausted days reach this service.

        $denominator = self::DENOMINATOR_MONTHLY;
        $dailyRate   = round($basicMonthly / $denominator, 6);
        $hourlyRate  = round($dailyRate / 8, 6);

        // LWOP
        $lwopDeduction = round(($lwopDays / $denominator) * $basicMonthly, 2);

        // Tardiness (late)
        $lateHours   = intdiv($lateMinutes, 60);
        $lateRemMins = $lateMinutes % 60;
        $tardiness   = round(
            ($lateHours * $hourlyRate)
            + ($this->minuteEquivalent($lateRemMins) * $dailyRate),
            2
        );

        // Undertime
        $utHours      = intdiv($undertimeMins, 60);
        $utRemMins    = $undertimeMins % 60;
        $undertimeDed = round(
            ($utHours * $hourlyRate)
            + ($this->minuteEquivalent($utRemMins) * $dailyRate),
            2
        );

        $totalAttendanceDed = round($lwopDeduction + $tardiness + $undertimeDed, 2);

        // ── 4. Statutory / other deductions via DeductionService ──────────
        // daysWorked is only passed (non-null) when employee has LWOP days,
        // so GSIS proration only activates for incomplete months.
        $deductionLines = $this->deductionService->resolveDeductions(
            $employee,
            $batch,
            $ytdGross,
            $gsisProrateDays,
            $totalDays
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
            $salaryEarned, $peraEarned, $rataEarned, $totalAllowances,
            $allowanceLines,
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
                    'gross_income'     => round($salaryEarned + $totalAllowances, 2),
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

            $this->allowanceService->syncPayrollEntryAllowances($entry, $allowanceLines);

            return $entry->load(['deductions', 'allowances']);
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Batch-level entry point
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Run computeEntry() for every active employee in one go.
     * Called by PayrollController@compute.
     *
     * The attendanceMap comes from AttendanceService::getAttendanceForBatch()
     * and contains full-month aggregated values keyed by employee_id.
     *
     * Snapshots are eager-loaded in a single query so computeCutoffSplit()
     * can access daily_logs without N+1 queries.
     *
     * Fix log:
     *  2026-06-10  ytdGross is now queried from prior payroll_entries before
     *              each computeEntry() call so Withholding Tax is computed
     *              correctly. Previously ytdGross was always 0.0, causing WHT
     *              to always land in the 0% bracket and return 0.00 for every
     *              employee.
     *
     * @param  PayrollBatch $batch
     * @param  array        $attendanceMap  [ employee_id (int) => attendance array ]
     * @return array  ['computed' => int, 'errors' => string[]]
     */
    public function computeBatch(PayrollBatch $batch, array $attendanceMap = []): array
    {
        $employees = Employee::where('status', 'active')
            ->where('is_excluded', false)
            ->with(['deductionEnrollments.deductionType'])
            ->get();

        // Pre-load all snapshots for this batch keyed by employee_id
        $snapshots = AttendanceSnapshot::where('payroll_batch_id', $batch->id)
            ->get()
            ->keyBy('employee_id');

        // Pre-load YTD gross for all active employees in one query.
        // period_year lives on payroll_batches not payroll_entries, so we
        // join through the batches table to filter by year correctly.
        $ytdGrossMap = PayrollEntry::join('payroll_batches', 'payroll_entries.payroll_batch_id', '=', 'payroll_batches.id')
            ->where('payroll_batches.period_year', $batch->period_year)
            ->where('payroll_entries.payroll_batch_id', '!=', $batch->id)
            ->where('payroll_batches.status', '!=', 'draft')
            ->selectRaw('payroll_entries.employee_id, SUM(payroll_entries.gross_income) as ytd_gross')
            ->groupBy('payroll_entries.employee_id')
            ->pluck('ytd_gross', 'employee_id');

        $computed = 0;
        $errors   = [];

        foreach ($employees as $employee) {
            try {
                $attendance = $attendanceMap[$employee->id] ?? [];
                $snapshot   = $snapshots->get($employee->id);

                // Inject the correct ytd_gross so WHT is computed properly.
                // If no prior entries exist for this year, defaults to 0.0
                // (correct for the first payroll run of the year).
                $attendance['ytd_gross'] = (float) ($ytdGrossMap->get($employee->id) ?? 0.0);

                $this->computeEntry($employee, $batch, $attendance, $snapshot);
                $computed++;
            } catch (\Throwable $e) {
                Log::error("Payroll compute error — Employee #{$employee->id}: " . $e->getMessage());
                $errors[] = "#{$employee->id} {$employee->full_name}: " . $e->getMessage();
            }
        }

        Log::info("Batch compute complete for batch #{$batch->id}", [
            'computed' => $computed,
            'errors'   => count($errors),
        ]);

        return compact('computed', 'errors');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Cutoff split — for reporting & payslip breakdown (Phase 6 / 7)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Split a computed monthly PayrollEntry into 1st-cutoff and 2nd-cutoff
     * values based on ACTUAL attendance days in each half.
     *
     * This is used by:
     *  - PayrollReportController  (Phase 6) when cutoff='1st' or '2nd'
     *  - Payslip view             (Phase 7) for side-by-side breakdown
     *
     * Split logic (per plan — actual attendance days, NOT 50/50):
     *   ratio_1st = days_present_1st / total_days_present
     *   ratio_2nd = days_present_2nd / total_days_present
     *   gross_1st = ratio_1st * monthly_gross  (etc.)
     *
     * Net pay per cutoff is derived as:
     *   net_1st = gross_1st - (total_deductions * ratio_1st)
     *   net_2nd = gross_2nd - (total_deductions * ratio_2nd)
     *
     * If daily_logs is empty (legacy snapshot without day detail), falls back
     * to a 50/50 split so the method always returns a usable result.
     *
     * @param  PayrollEntry       $entry     The computed monthly entry
     * @param  AttendanceSnapshot $snapshot  Must have daily_logs populated
     * @return array{
     *   first_cutoff: array{
     *     gross_income: float, basic_salary: float, pera: float, rata: float,
     *     lwop_deduction: float, tardiness: float, undertime: float,
     *     net_amount: float,
     *     days_present: float, late_minutes: int, undertime_minutes: int
     *   },
     *   second_cutoff: array{ ... same keys ... }
     * }
     */
    public function computeCutoffSplit(PayrollEntry $entry, AttendanceSnapshot $snapshot): array
    {
        $dailyLogs = $snapshot->daily_logs ?? [];

        // ── Calculate presence counts per cutoff ──────────────────────────
        if (! empty($dailyLogs)) {
            $firstLogs  = array_filter($dailyLogs, fn($log) => ($log['is_first_cutoff'] ?? false) === true);
            $secondLogs = array_filter($dailyLogs, fn($log) => ($log['is_first_cutoff'] ?? false) === false);

            $daysPresent1st = (float) collect($firstLogs)->where('present', true)->count();
            $daysPresent2nd = (float) collect($secondLogs)->where('present', true)->count();
            $totalPresent   = $daysPresent1st + $daysPresent2nd;

            $lateMinutes1st      = (int) collect($firstLogs)->sum('late_minutes');
            $lateMinutes2nd      = (int) collect($secondLogs)->sum('late_minutes');
            $undertimeMinutes1st = (int) collect($firstLogs)->sum('undertime_minutes');
            $undertimeMinutes2nd = (int) collect($secondLogs)->sum('undertime_minutes');
        } else {
            // Legacy fallback — no daily detail, split 50/50
            $totalPresent        = (float) $snapshot->days_present ?: 1.0; // avoid div/0
            $daysPresent1st      = round($totalPresent / 2, 3);
            $daysPresent2nd      = $totalPresent - $daysPresent1st;
            $lateMinutes1st      = (int) round($snapshot->late_minutes / 2);
            $lateMinutes2nd      = $snapshot->late_minutes - $lateMinutes1st;
            $undertimeMinutes1st = (int) round($snapshot->undertime_minutes / 2);
            $undertimeMinutes2nd = $snapshot->undertime_minutes - $undertimeMinutes1st;
        }

        // Guard against zero present days (e.g. full-month LWOP)
        if ($totalPresent <= 0) {
            $ratio1st = 0.5;
            $ratio2nd = 0.5;
        } else {
            $ratio1st = $daysPresent1st / $totalPresent;
            $ratio2nd = $daysPresent2nd / $totalPresent;
        }

        // ── Proportional split of gross components ────────────────────────
        $gross1st = $this->applyCutoffRatio($entry, $ratio1st);
        $gross2nd = $this->applyCutoffRatio($entry, $ratio2nd);

        // ── Net pay per cutoff ────────────────────────────────────────────
        // Deductions are split by the same ratio so net = gross_cutoff - ded_cutoff.
        // Statutory deductions (GSIS, PhilHealth, WHT, PAG-IBIG) are schedule-based
        // and handled separately by reporting export classes (Phase 6), but for the
        // payslip display this proportional approach is accurate enough.
        $gross1st['net_amount'] = round(
            $gross1st['gross_income'] - round($entry->total_deductions * $ratio1st, 2),
            2
        );
        $gross2nd['net_amount'] = round(
            $gross2nd['gross_income'] - round($entry->total_deductions * $ratio2nd, 2),
            2
        );

        // Staple the per-cutoff attendance metrics on
        $gross1st['days_present']      = $daysPresent1st;
        $gross1st['late_minutes']      = $lateMinutes1st;
        $gross1st['undertime_minutes'] = $undertimeMinutes1st;

        $gross2nd['days_present']      = $daysPresent2nd;
        $gross2nd['late_minutes']      = $lateMinutes2nd;
        $gross2nd['undertime_minutes'] = $undertimeMinutes2nd;

        return [
            'first_cutoff'  => $gross1st,
            'second_cutoff' => $gross2nd,
        ];
    }

    /**
     * Apply a ratio to all monetary fields of a PayrollEntry.
     * Returns a plain array — does NOT persist anything.
     *
     * @param  PayrollEntry $entry
     * @param  float        $ratio  0.0–1.0
     * @return array
     */
    protected function applyCutoffRatio(PayrollEntry $entry, float $ratio): array
    {
        return [
            'gross_income'   => round($entry->gross_income   * $ratio, 2),
            'basic_salary'   => round($entry->basic_salary   * $ratio, 2),
            'pera'           => round($entry->pera            * $ratio, 2),
            'rata'           => round($entry->rata            * $ratio, 2),
            'lwop_deduction' => round($entry->lwop_deduction  * $ratio, 2),
            'tardiness'      => round($entry->tardiness       * $ratio, 2),
            'undertime'      => round($entry->undertime       * $ratio, 2),
            // Note: statutory deductions (GSIS, HDMF, PhilHealth, WHT) are NOT
            // split proportionally here — they are schedule-based and handled
            // separately by the reporting export classes (Phase 6).
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Internal helpers
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Wraps minutesToDays() from the TableIVConverter trait.
     * Example: 15 minutes → 0.031 (Table IV lookup, NOT 0.03125 computed)
     */
    protected function minuteEquivalent(int $minutes): float
    {
        if ($minutes <= 0) return 0.0;
        return (float) $this->minutesToDays($minutes);
    }
}