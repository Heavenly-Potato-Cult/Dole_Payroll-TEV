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
     * Compute a full or PARTIAL MONTHLY payroll entry for one employee in a batch.
     * Persists PayrollEntry + PayrollDeduction rows (upsert-style).
     *
     * Phase 3 changes vs old semi-monthly approach:
     *  - Always uses DENOMINATOR_MONTHLY (44) — no config flag needed.
     *  - Attendance array is the FULL-MONTH snapshot (pulled once per month).
     *  - The optional $snapshot param allows the caller to pass the loaded
     *    AttendanceSnapshot model so cutoff split helpers work without an
     *    extra query.
     *
     * Phase 8 — Partial compute (compute options modal):
     *  - $options selectively gates which components get RE-resolved this
     *    pass: apply_attendance, apply_deductions, apply_allowances, apply_lwop.
     *  - A component that is NOT selected is NOT zeroed — its last-computed
     *    value (from the existing PayrollEntry / PayrollDeduction rows) is
     *    carried over unchanged and folded into the recombined
     *    total_deductions / net_amount. If no prior entry exists yet, the
     *    carried-over value is simply 0 for that component.
     *  - If ALL flags are false/omitted, the entry is computed as base
     *    monthly salary only, with every other component carried over from
     *    whatever was last persisted (or 0 on first compute).
     *  - Entries flagged is_manually_overridden are protected: compute will
     *    refuse to touch them unless $options['force'] === true.
     *  - When $options['force'] === true, any component that is NOT selected
     *    this pass is reset to zero / cleared instead of carrying over its
     *    last-computed value (clean discard). Without force, unselected
     *    components always carry over unchanged, same as before.
     *
     * Fix log:
     *  2026-06-10  daysWorked is now derived from the attendance array and
     *              passed to resolveDeductions() so GSIS is prorated correctly
     *              for employees with LWOP days in the period.
     *  2026-06-30  Added partial-compute support with carry-over semantics
     *              and a manual-override guard (Phase 8).
     *  2026-08-06  Force Re-compute now performs a clean discard: unchecked
     *              components reset to zero instead of carrying over when
     *              force=true. Previously force only bypassed the
     *              is_manually_overridden guard and had no effect on
     *              carry-over behavior for unchecked components.
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
     * @param  array                   $options    Shape:
     *   [
     *     'apply_attendance' => bool,  // tardiness + undertime (default false)
     *     'apply_deductions' => bool,  // statutory deductions   (default false)
     *     'apply_allowances' => bool,  // PERA/RATA/etc.         (default false)
     *     'apply_lwop'       => bool,  // LWOP deduction         (default false)
     *     'force'            => bool,  // bypass is_manually_overridden guard
     *   ]
     * @return PayrollEntry  (loaded with deductions relation)
     *
     * @throws \RuntimeException  when the existing entry is manually
     *         overridden and $options['force'] is not true.
     */
    public function computeEntry(
        Employee $employee,
        PayrollBatch $batch,
        array $attendance = [],
        ?AttendanceSnapshot $snapshot = null,
        array $options = []
    ): PayrollEntry {
        $applyAttendance = (bool) ($options['apply_attendance'] ?? false);
        $applyDeductions = (bool) ($options['apply_deductions'] ?? false);
        $applyAllowances = (bool) ($options['apply_allowances'] ?? false);
        $applyLwop       = (bool) ($options['apply_lwop'] ?? false);
        $force           = (bool) ($options['force'] ?? false);

        // ── 0. Manual-override guard ───────────────────────────────────────
        // Load any existing entry up front — needed both for the guard and
        // for carry-over of skipped components below.
        $existing = PayrollEntry::with('deductions', 'allowances')
            ->where('payroll_batch_id', $batch->id)
            ->where('employee_id', $employee->id)
            ->first();

        if ($existing && $existing->is_manually_overridden && ! $force) {
            throw new \RuntimeException(
                "Entry for {$employee->full_name} is manually overridden and was skipped. " .
                "Use the force-recompute option to overwrite it."
            );
        }

        // ── 1. Basic salary (always computed — full month, never partial) ──
        $basicMonthly = (float) $employee->basic_monthly_salary;
        $salaryEarned = round($basicMonthly, 2);

        $denominator = self::DENOMINATOR_MONTHLY;
        $dailyRate   = round($basicMonthly / $denominator, 6);
        $hourlyRate  = round($dailyRate / 8, 6);

        // ── 2. Allowances ────────────────────────────────────────────────
        if ($applyAllowances) {
            $allowanceLines = $this->allowanceService->resolveForPayroll($employee, $batch);
            $allowanceSum   = $this->allowanceService->summarize($allowanceLines);
            $peraEarned     = $allowanceSum['pera'];
            $rataEarned     = $allowanceSum['rata'];
            $totalAllowances = $allowanceSum['total'];
            $allowancesWereTouched = true;
        } elseif ($force) {
            // Force discard: unchecked component resets to zero instead of
            // carrying over, and existing PayrollEntryAllowance rows are
            // cleared (empty array, not the null sentinel).
            $peraEarned            = 0.0;
            $rataEarned            = 0.0;
            $totalAllowances       = 0.0;
            $allowanceLines        = [];
            $allowancesWereTouched = true;
        } else {
            // Normal (non-force) recompute: carry over from the existing entry.
            $peraEarned      = (float) ($existing->pera ?? 0);
            $rataEarned      = (float) ($existing->rata ?? 0);
            $totalAllowances = $existing
                ? round(((float) $existing->gross_income) - ((float) $existing->basic_salary), 2)
                : 0.0;
            $allowanceLines  = null; // sentinel: don't touch PayrollEntryAllowance rows
            $allowancesWereTouched = false;
        }

        // ── 3. Attendance deductions (tardiness / undertime) ────────────────
        if ($applyAttendance) {
            $lateMinutes   = (int) ($attendance['late_minutes'] ?? 0);
            $undertimeMins = (int) ($attendance['undertime_mins'] ?? $attendance['undertime_minutes'] ?? 0);

            $lateHours   = intdiv($lateMinutes, 60);
            $lateRemMins = $lateMinutes % 60;
            $tardiness   = round(
                ($lateHours * $hourlyRate) + ($this->minuteEquivalent($lateRemMins) * $dailyRate),
                2
            );

            $utHours      = intdiv($undertimeMins, 60);
            $utRemMins    = $undertimeMins % 60;
            $undertimeDed = round(
                ($utHours * $hourlyRate) + ($this->minuteEquivalent($utRemMins) * $dailyRate),
                2
            );
        } elseif ($force) {
            // Force discard: reset instead of carrying over.
            $tardiness    = 0.0;
            $undertimeDed = 0.0;
        } else {
            // Carry over.
            $tardiness    = (float) ($existing->tardiness ?? 0);
            $undertimeDed = (float) ($existing->undertime ?? 0);
        }

        // ── 4. LWOP — independently gated from attendance ───────────────────
        if ($applyLwop) {
            $lwopDays      = (float) ($attendance['lwop_days'] ?? 0);
            $lwopDeduction = round(($lwopDays / $denominator) * $basicMonthly, 2);
        } elseif ($force) {
            // Force discard: reset instead of carrying over.
            $lwopDeduction = 0.0;
            $lwopDays      = 0.0;
        } else {
            // Carry over both the deduction amount AND the day count, since
            // the day count feeds GSIS proration below when deductions ARE
            // being re-applied this pass.
            $lwopDeduction = (float) ($existing->lwop_deduction ?? 0);
            $lwopDays      = $existing
                ? round(($lwopDeduction / max($basicMonthly, 0.01)) * $denominator, 3)
                : 0.0;
        }

        $totalAttendanceDed = round($lwopDeduction + $tardiness + $undertimeDed, 2);

        // ── 5. Statutory / other deductions via DeductionService ────────────
        if ($applyDeductions) {
            $totalDays  = self::DENOMINATOR_SEMI_MONTHLY; // 22 working days/month
            $daysWorked = isset($attendance['days_present'])
                ? (int) $attendance['days_present']
                : (int) max(0, $totalDays - (int) $lwopDays);

            // Only prorate GSIS when there are actual LWOP days (whether
            // freshly applied this pass or carried over from a prior pass).
            $gsisProrateDays = $lwopDays > 0 ? $daysWorked : null;
            $ytdGross        = (float) ($attendance['ytd_gross'] ?? 0);

            $deductionLines = $this->deductionService->resolveDeductions(
                $employee,
                $batch,
                $ytdGross,
                $gsisProrateDays,
                $totalDays
            );
            $statutoryTotal = round(collect($deductionLines)->sum('amount'), 2);
        } elseif ($force) {
            // Force discard: clear existing PayrollDeduction rows (empty
            // array, not the null sentinel) instead of carrying their sum over.
            $deductionLines = [];
            $statutoryTotal = 0.0;
        } else {
            // Carry over — don't touch PayrollDeduction rows, just reuse
            // their persisted sum for the recombined total below.
            $deductionLines = null; // sentinel: don't touch PayrollDeduction rows
            $statutoryTotal = $existing
                ? round($existing->deductions->sum('amount'), 2)
                : 0.0;
        }

        // ── 6. Recombine totals from fresh + carried-over components ───────
        $grossEarned      = round($salaryEarned + $totalAllowances, 2);
        $totalDeductions  = round($statutoryTotal + $totalAttendanceDed, 2);
        $netAmount        = round($grossEarned - $totalDeductions, 2);

        // Cumulative union — once a component has been genuinely applied at
        // least one pass, it stays marked applied even on later passes that
        // skip it and carry the value forward instead. This is what the
        // submit() hard gate checks, NOT the flags from this single pass.
        $appliedComponents = array_merge(
            $existing?->applied_components ?? [],
            [
                'attendance' => $applyAttendance || (bool) (($existing?->applied_components ?? [])['attendance'] ?? false),
                'deductions' => $applyDeductions || (bool) (($existing?->applied_components ?? [])['deductions'] ?? false),
                'allowances' => $applyAllowances || (bool) (($existing?->applied_components ?? [])['allowances'] ?? false),
                'lwop'       => $applyLwop       || (bool) (($existing?->applied_components ?? [])['lwop'] ?? false),
            ]
        );

        // ── 7. Persist ───────────────────────────────────────────────────
        return DB::transaction(function () use (
            $employee, $batch,
            $salaryEarned, $peraEarned, $rataEarned, $grossEarned,
            $allowanceLines, $allowancesWereTouched,
            $lwopDeduction, $tardiness, $undertimeDed,
            $totalDeductions, $netAmount,
            $deductionLines, $appliedComponents,
            $applyAttendance
        ) {
            /** @var PayrollEntry $entry */
            $entry = PayrollEntry::updateOrCreate(
                [
                    'payroll_batch_id' => $batch->id,
                    'employee_id'      => $employee->id,
                ],
                [
                    'basic_salary'        => $salaryEarned,
                    'pera'                => $peraEarned,
                    'rata'                => $rataEarned,
                    'gross_income'        => $grossEarned,
                    'lwop_deduction'      => $lwopDeduction,
                    'tardiness'           => $tardiness,
                    'undertime'           => $undertimeDed,
                    'total_deductions'    => $totalDeductions,
                    'net_amount'          => $netAmount,
                    'applied_components'  => $appliedComponents,
                    // 2026-08-14: raw per-pass value, deliberately NOT OR'd
                    // against the existing entry — this is what makes the
                    // register's Split column reset to 50/50 the instant
                    // Apply Attendance is unchecked on a later pass, unlike
                    // applied_components above which must stay sticky.
                    'last_attendance_applied' => $applyAttendance,
                ]
            );

            // Only replace line items for categories actually requested this
            // pass — skipped categories (sentinel null) keep what's in the DB.
            if ($deductionLines !== null) {
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
            }

            if ($allowancesWereTouched && $allowanceLines !== null) {
                $this->allowanceService->syncPayrollEntryAllowances($entry, $allowanceLines);
            }

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
     *  2026-06-30  $options is now threaded through to computeEntry() for
     *              partial-compute support. Entries that are manually
     *              overridden and protected (no 'force') raise a
     *              \RuntimeException per-employee, caught here and reported
     *              as a normal per-employee error — they do NOT abort the
     *              rest of the batch.
     *
     * @param  PayrollBatch $batch
     * @param  array        $attendanceMap  [ employee_id (int) => attendance array ]
     * @param  array        $options        See computeEntry() docblock.
     * @return array  ['computed' => int, 'errors' => string[], 'skipped' => int]
     */
    public function computeBatch(PayrollBatch $batch, array $attendanceMap = [], array $options = []): array
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
        $skipped  = 0;
        $errors   = [];

        foreach ($employees as $employee) {
            try {
                $attendance = $attendanceMap[$employee->id] ?? [];
                $snapshot   = $snapshots->get($employee->id);

                // Inject the correct ytd_gross so WHT is computed properly.
                // If no prior entries exist for this year, defaults to 0.0
                // (correct for the first payroll run of the year).
                $attendance['ytd_gross'] = (float) ($ytdGrossMap->get($employee->id) ?? 0.0);

                $this->computeEntry($employee, $batch, $attendance, $snapshot, $options);
                $computed++;
            } catch (\RuntimeException $e) {
                // Manually-overridden entry, protected — count separately
                // from hard errors so the controller can message it clearly.
                $skipped++;
                $errors[] = "#{$employee->id} {$employee->full_name}: " . $e->getMessage();
            } catch (\Throwable $e) {
                Log::error("Payroll compute error — Employee #{$employee->id}: " . $e->getMessage());
                $errors[] = "#{$employee->id} {$employee->full_name}: " . $e->getMessage();
            }
        }

        Log::info("Batch compute complete for batch #{$batch->id}", [
            'computed' => $computed,
            'skipped'  => $skipped,
            'errors'   => count($errors),
            'options'  => $options,
        ]);

        return compact('computed', 'skipped', 'errors');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Cutoff split — for reporting & payslip breakdown (Phase 6 / 7)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Split a computed monthly PayrollEntry into 1st-cutoff and 2nd-cutoff
     * values.
     *
     * Split logic — checked in this order:
     *   1. Employee override (2026-08-14): if
     *      $entry->employee->salary_split_override_pct is set, that fixed
     *      percentage (of NET pay, disbursed at the 1st cutoff) is used
     *      directly, bypassing attendance entirely. This is an explicit
     *      per-employee preference set on the employee's profile — some
     *      employees want a different split than actual days worked.
     *   2. Actual attendance days (unchanged default, per original plan):
     *        ratio_1st = days_present_1st / total_days_present
     *        ratio_2nd = days_present_2nd / total_days_present
     *        gross_1st = ratio_1st * monthly_gross  (etc.)
     *   3. Legacy fallback — no daily_logs detail and no override: 50/50.
     *
     * Net pay per cutoff is derived as:
     *   net_1st = gross_1st - (total_deductions * ratio_1st)
     *   net_2nd = gross_2nd - (total_deductions * ratio_2nd)
     *
     * @param  PayrollEntry       $entry     The computed monthly entry (employee relation used if loaded, lazy-loaded otherwise)
     * @param  AttendanceSnapshot $snapshot  Must have daily_logs populated
     * @return array{
     *   first_cutoff: array{
     *     gross_income: float, basic_salary: float, pera: float, rata: float,
     *     lwop_deduction: float, tardiness: float, undertime: float,
     *     net_amount: float,
     *     days_present: float, late_minutes: int, undertime_minutes: int
     *   },
     *   second_cutoff: array{ ... same keys ... },
     *   is_custom_split: bool,
     *   split_pct_1st: float
     * }
     */
    public function computeCutoffSplit(PayrollEntry $entry, AttendanceSnapshot $snapshot): array
    {
        $dailyLogs = $snapshot->daily_logs ?? [];
        $overridePct = $entry->employee->salary_split_override_pct ?? null;

        // ── Calculate presence counts per cutoff ──────────────────────────
        // Still computed even when an override is active — these day/minute
        // counts stay attendance-accurate for display on the payslip, they
        // just no longer drive the monetary ratio below.
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

        if ($overridePct !== null) {
            // Employee-configured fixed disbursement split — takes priority
            // over attendance entirely.
            $ratio1st = ((float) $overridePct) / 100;
            $ratio2nd = 1 - $ratio1st;
        } elseif ($totalPresent <= 0) {
            // Guard against zero present days (e.g. full-month LWOP)
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
            'first_cutoff'    => $gross1st,
            'second_cutoff'   => $gross2nd,
            'is_custom_split' => $overridePct !== null,
            'split_pct_1st'   => round($ratio1st * 100, 2),
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
