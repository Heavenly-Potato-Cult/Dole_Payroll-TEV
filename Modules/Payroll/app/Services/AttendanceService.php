<?php

namespace Modules\Payroll\Services;

use Modules\Payroll\Models\AttendanceSnapshot;
use App\SharedKernel\Models\Employee;
use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Traits\TableIVConverter;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    use TableIVConverter;

    /**
     * Fixed working-day denominator per DOLE RO9 payroll rules.
     */
    const WORK_DAYS_DENOMINATOR = 22;

    /**
     * Standard work schedule thresholds (DOLE RO9).
     * Can be customized based on local rules via config if needed.
     */
    const MORNING_START_THRESHOLD = '08:00:00';  // 8:00 AM
    const AFTERNOON_END_THRESHOLD = '17:00:00';  // 5:00 PM

    // ═══════════════════════════════════════════════════════════════════
    //  STEP 1 — Pull from HRIS and store (called from PayrollController)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Fetch attendance for ALL active employees for a batch's cut-off period
     * and upsert into attendance_snapshots.
     *
     * Re-pulling is safe — upsert overwrites existing rows for the same
     * (payroll_batch_id, employee_id) pair. Any HR corrections are reset
     * on a re-pull, which is intentional so stale corrections don't persist.
     *
     * @param  PayrollBatch $batch
     * @return array  ['pulled' => int, 'errors' => string[]]
     */
    public function pullForBatch(PayrollBatch $batch): array
    {
        $hris = app(\App\SharedKernel\Services\HrisApiService::class);

        // ONE bulk HTTP call instead of 82
        $attendanceMap = $hris->fetchAttendanceBulk(
            $batch->period_start,
            $batch->period_end
        );

        if (empty($attendanceMap)) {
            return ['pulled' => 0, 'errors' => ['Bulk attendance fetch returned no data.']];
        }

        $employees = Employee::where('status', 'active')
            ->where('is_excluded', false)
            ->orderBy('id')
            ->get();
        $firstId   = $employees->first()->id; // = 8
        $pulled    = 0;
        $errors    = [];

        foreach ($employees as $employee) {
            try {
                // Map DB id (8–89) to API key (EMP001–EMP082)
                $apiKey = 'EMP' . str_pad($employee->id - $firstId + 1, 3, '0', STR_PAD_LEFT);

                $raw = $attendanceMap[$apiKey] ?? null;

                if (! $raw) {
                    $errors[] = "#{$employee->id} {$employee->full_name}: not found in API (key: {$apiKey})";
                    continue;
                }

                // Parse raw daily logs to compute metrics
                $metrics = $this->parseDailyLogs($raw);

                AttendanceSnapshot::updateOrCreate(
                    [
                        'payroll_batch_id' => $batch->id,
                        'employee_id'      => $employee->id,
                    ],
                    [
                        'days_present'      => $metrics['days_present'],
                        'lwop_days'         => $metrics['lwop_days'],
                        'late_minutes'      => $metrics['late_minutes'],
                        'undertime_minutes' => $metrics['undertime_minutes'],
                        'leave_credits'     => $metrics['leave_credits'],
                        'is_corrected'      => false,
                        'correction_note'   => null,
                        'corrected_by'      => null,
                        'corrected_at'      => null,
                        'source'            => 'hris_api',
                        'fetched_at'        => now(),
                    ]
                );

                $pulled++;
            } catch (\Throwable $e) {
                Log::error("Attendance snapshot failed — Employee #{$employee->id}: " . $e->getMessage());
                $errors[] = "#{$employee->id} {$employee->full_name}: " . $e->getMessage();
            }
        }

        Log::info("Attendance pull complete for batch #{$batch->id}", [
            'pulled' => $pulled,
            'errors' => count($errors),
        ]);

        return compact('pulled', 'errors');
    }

    /**
     * Parse raw daily logs from HRIS API and compute attendance metrics.
     *
     * Expected raw structure (production HRIS API):
     * [
     *   'user_id' => 'EMP001',
     *   'daily_logs' => [
     *     [
     *       'date' => '2026-05-01',
     *       'am' => ['in' => '07:55:00', 'out' => '12:00:00'],
     *       'pm' => ['in' => '13:00:00', 'out' => '17:05:00']
     *     ],
     *     ...
     *   ],
     *   'leave_credits' => 15.0
     * ]
     *
     * Fallback structure (dummy API for backward compatibility):
     * [
     *   'employee_id' => 'EMP001',
     *   'days_present' => 11.0,
     *   'lwop_days' => 0.0,
     *   'late_minutes' => 0,
     *   'undertime_minutes' => 0,
     *   'leave_credits' => 15.0
     * ]
     *
     * @param  array $raw
     * @return array  ['days_present', 'lwop_days', 'late_minutes', 'undertime_minutes', 'leave_credits']
     */
    protected function parseDailyLogs(array $raw): array
    {
        // Check if this is the new raw daily logs format
        if (isset($raw['daily_logs']) && is_array($raw['daily_logs'])) {
            return $this->computeMetricsFromDailyLogs($raw);
        }

        // Fallback to legacy aggregated format (dummy API)
        return [
            'days_present'      => (float) ($raw['days_present']      ?? 0),
            'lwop_days'         => (float) ($raw['lwop_days']         ?? 0),
            'late_minutes'      => (int)   ($raw['late_minutes']      ?? 0),
            'undertime_minutes' => (int)   ($raw['undertime_minutes'] ?? 0),
            'leave_credits'     => (float) ($raw['leave_credits']     ?? 0),
        ];
    }

    /**
     * Compute attendance metrics from raw daily logs.
     *
     * @param  array $raw  Raw data with 'daily_logs' array
     * @return array  Computed metrics
     */
    protected function computeMetricsFromDailyLogs(array $raw): array
    {
        $dailyLogs = $raw['daily_logs'] ?? [];
        $daysPresent = 0.0;
        $lwopDays = 0.0;
        $lateMinutes = 0;
        $undertimeMinutes = 0;
        $leaveCredits = (float) ($raw['leave_credits'] ?? 0);

        foreach ($dailyLogs as $log) {
            $date = $log['date'] ?? null;
            $am = $log['am'] ?? [];
            $pm = $log['pm'] ?? [];

            // Skip if no date
            if (! $date) {
                continue;
            }

            // Check if this is a regular workday (Monday-Friday, excluding holidays)
            // For now, we assume all dates in the cutoff are workdays unless marked otherwise
            // TODO: Integrate with holiday calendar for accurate LWOP calculation
            $isWorkday = $this->isWorkday($date);

            if (! $isWorkday) {
                continue;
            }

            // Check for valid logs
            $amIn = $am['in'] ?? null;
            $amOut = $am['out'] ?? null;
            $pmIn = $pm['in'] ?? null;
            $pmOut = $pm['out'] ?? null;

            $hasValidLogs = ($amIn || $amOut || $pmIn || $pmOut);

            if ($hasValidLogs) {
                // Count as present
                $daysPresent += 1.0;

                // Compute tardiness (late minutes)
                if ($amIn) {
                    $lateMinutes += $this->computeLateMinutes($amIn);
                }

                // Compute undertime
                if ($pmOut) {
                    $undertimeMinutes += $this->computeUndertimeMinutes($pmOut);
                }
            } else {
                // All logs are null on a regular workday -> count as LWOP
                $lwopDays += 1.0;
            }
        }

        return [
            'days_present'      => $daysPresent,
            'lwop_days'         => $lwopDays,
            'late_minutes'      => $lateMinutes,
            'undertime_minutes' => $undertimeMinutes,
            'leave_credits'     => $leaveCredits,
        ];
    }

    /**
     * Check if a given date is a regular workday.
     * For now, assumes Monday-Friday are workdays.
     * TODO: Integrate with holiday calendar.
     *
     * @param  string $date  YYYY-MM-DD
     * @return bool
     */
    protected function isWorkday(string $date): bool
    {
        try {
            $dayOfWeek = date('N', strtotime($date)); // 1 (Monday) to 7 (Sunday)
            return $dayOfWeek >= 1 && $dayOfWeek <= 5; // Monday-Friday
        } catch (\Exception $e) {
            Log::warning("Failed to parse date for workday check: {$date}");
            return true; // Assume workday if parsing fails
        }
    }

    /**
     * Compute late minutes based on AM clock-in time.
     * If clock-in is after the threshold (08:00:00), calculate the delta.
     *
     * @param  string $timeIn  HH:MM:SS format
     * @return int  Late minutes (0 if on time or early)
     */
    protected function computeLateMinutes(string $timeIn): int
    {
        try {
            $threshold = self::MORNING_START_THRESHOLD;
            $inTime = strtotime($timeIn);
            $thresholdTime = strtotime($threshold);

            if ($inTime > $thresholdTime) {
                $diffSeconds = $inTime - $thresholdTime;
                return (int) round($diffSeconds / 60);
            }

            return 0;
        } catch (\Exception $e) {
            Log::warning("Failed to compute late minutes for time: {$timeIn}");
            return 0;
        }
    }

    /**
     * Compute undertime minutes based on PM clock-out time.
     * If clock-out is before the threshold (17:00:00), calculate the missing minutes.
     *
     * @param  string $timeOut  HH:MM:SS format
     * @return int  Undertime minutes (0 if on time or late)
     */
    protected function computeUndertimeMinutes(string $timeOut): int
    {
        try {
            $threshold = self::AFTERNOON_END_THRESHOLD;
            $outTime = strtotime($timeOut);
            $thresholdTime = strtotime($threshold);

            if ($outTime < $thresholdTime) {
                $diffSeconds = $thresholdTime - $outTime;
                return (int) round($diffSeconds / 60);
            }

            return 0;
        } catch (\Exception $e) {
            Log::warning("Failed to compute undertime minutes for time: {$timeOut}");
            return 0;
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  STEP 2 — Read snapshots (called from PayrollController@compute)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Return the stored attendance map for a batch, keyed by employee_id (integer).
     * Reads from attendance_snapshots — does NOT call the HRIS API.
     *
     * Returns an empty array if no snapshots exist yet (batch hasn't been pulled).
     * PayrollController@compute checks for this and blocks computation.
     *
     * @param  PayrollBatch $batch
     * @return array  [ employee_id (int) => [ 'lwop_days'=>, 'late_minutes'=>, ... ] ]
     */
    public function getAttendanceForBatch(PayrollBatch $batch): array
    {
        return AttendanceSnapshot::where('payroll_batch_id', $batch->id)
            ->get()
            ->keyBy('employee_id')
            ->map(fn (AttendanceSnapshot $snap) => $snap->toAttendanceArray())
            ->toArray();
    }

    /**
     * How many snapshots exist for this batch.
     * Used by PayrollController to gate the Compute button.
     */
    public function snapshotCount(PayrollBatch $batch): int
    {
        return AttendanceSnapshot::where('payroll_batch_id', $batch->id)->count();
    }

    /**
     * How many snapshots for this batch have been manually corrected by HR.
     * Shown in the show.blade.php attendance review panel.
     */
    public function correctedCount(PayrollBatch $batch): int
    {
        return AttendanceSnapshot::where('payroll_batch_id', $batch->id)
            ->where('is_corrected', true)
            ->count();
    }

    // ═══════════════════════════════════════════════════════════════════
    //  HR Correction
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Allow HR to manually override one employee's attendance snapshot.
     * Called from a future AttendanceController (or inline from PayrollController).
     *
     * @param  AttendanceSnapshot $snapshot
     * @param  array              $data  keys: lwop_days, late_minutes, undertime_minutes, correction_note
     * @param  int                $userId  Auth::id() of the HR officer making the correction
     */
    public function correctSnapshot(AttendanceSnapshot $snapshot, array $data, int $userId): void
    {
        $snapshot->update([
            'lwop_days'         => (float) ($data['lwop_days']         ?? $snapshot->lwop_days),
            'late_minutes'      => (int)   ($data['late_minutes']      ?? $snapshot->late_minutes),
            'undertime_minutes' => (int)   ($data['undertime_minutes'] ?? $snapshot->undertime_minutes),
            'is_corrected'      => true,
            'correction_note'   => $data['correction_note'] ?? null,
            'corrected_by'      => $userId,
            'corrected_at'      => now(),
            'source'            => 'manual',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Single-employee compute (unchanged — used by PayrollComputationService)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Process attendance data and compute deduction amounts for one employee.
     * Input is the raw array (from snapshot or directly), not a model.
     *
     * Per DOLE RO9 rules: tardiness deductions hit LEAVE CREDITS first.
     * Late minutes are converted to equivalent leave days and deducted from
     * vacation_leave_balance before being treated as LWOP.
     *
     * @return array {
     *   lwop_salary, lwop_pera, tardiness_amount,
     *   lwop_days, late_minutes, undertime_minutes,
     *   tardiness_days, total_deduction
     * }
     */
    public function compute(Employee $employee, array $attendance): array
    {
        $basicSalary      = (float) $employee->basic_salary;
        $pera             = (float) $employee->pera;
        $denom            = self::WORK_DAYS_DENOMINATOR;

        $lwopDays         = (float) ($attendance['lwop_days']         ?? 0);
        $lateMinutes      = (int)   ($attendance['late_minutes']      ?? 0);
        $undertimeMinutes = (int)   ($attendance['undertime_minutes'] ?? 0);

        // ── Deduct late from vacation leave credits first ─────────────────
        // Convert late minutes to equivalent leave days (8 hours = 1 day = 480 mins)
        $lateDaysEquivalent = $lateMinutes / 480;
        
        // Get employee's current vacation leave balance
        $vacationBalance = (float) ($employee->vacation_leave_balance ?? 0);
        
        // Deduct from vacation leave balance first
        if ($vacationBalance > 0 && $lateDaysEquivalent > 0) {
            if ($vacationBalance >= $lateDaysEquivalent) {
                // Enough credits to cover all tardiness
                $employee->vacation_leave_balance = $vacationBalance - $lateDaysEquivalent;
                $employee->save();
                $lateMinutesAfterCredits = 0;
            } else {
                // Partial coverage - credits exhausted, remaining becomes LWOP
                $employee->vacation_leave_balance = 0;
                $employee->save();
                $remainingLateDays = $lateDaysEquivalent - $vacationBalance;
                $lateMinutesAfterCredits = (int) round($remainingLateDays * 480);
            }
        } else {
            $lateMinutesAfterCredits = $lateMinutes;
        }

        // Use late_minutes after credits for tardiness calculation
        $effectiveLateMinutes = $lateMinutesAfterCredits;

        // LWOP deductions
        $lwopSalary = round(($lwopDays / $denom) * $basicSalary, 2);
        $lwopPera   = round(($lwopDays / $denom) * $pera, 2);

        // Tardiness (late + undertime combined via Table IV)
        $totalTardyMinutes = $effectiveLateMinutes + $undertimeMinutes;
        $tardinessDays     = $this->minutesToDays($totalTardyMinutes);
        $dailyRate         = round($basicSalary / $denom, 4);
        $tardinessAmount   = round($tardinessDays * $dailyRate, 2);

        return [
            'lwop_salary'       => $lwopSalary,
            'lwop_pera'         => $lwopPera,
            'tardiness_amount'  => $tardinessAmount,
            'lwop_days'         => $lwopDays,
            'late_minutes'      => $effectiveLateMinutes,
            'undertime_minutes' => $undertimeMinutes,
            'tardiness_days'    => $tardinessDays,
            'total_deduction'   => round($lwopSalary + $lwopPera + $tardinessAmount, 2),
        ];
    }
}
