<?php

namespace Modules\Payroll\Services;

use Carbon\Carbon;
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
     */
    const MORNING_START_THRESHOLD = '08:00:00';  // 8:00 AM
    const AFTERNOON_END_THRESHOLD = '17:00:00';  // 5:00 PM

    // ═══════════════════════════════════════════════════════════════════
    //  STEP 1 — Pull from HRIS and store (called from PayrollController)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Fetch attendance for ALL active employees for a batch's FULL MONTH period
     * and upsert into attendance_snapshots.
     *
     * Phase 2 changes:
     *  - Always pulls period_start → period_end (full month; no more per-cutoff logic).
     *  - Stores parsed daily_logs JSON in the snapshot (keyed by date string).
     *  - Sets is_first_cutoff = null (full-month snapshot; cutoff split done on the fly).
     *  - Aggregated fields (days_present, lwop_days, …) still written for backward compat.
     *
     * Re-pulling is safe — upsert overwrites existing rows for the same
     * (payroll_batch_id, employee_id) pair. HR corrections are reset on re-pull.
     *
     * @param  PayrollBatch $batch
     * @return array  ['pulled' => int, 'errors' => string[]]
     */
    public function pullForBatch(PayrollBatch $batch): array
    {
        $hris = app(\App\SharedKernel\Services\HrisApiService::class);

        // ONE bulk HTTP call for the entire month
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

        $firstId = $employees->first()->id; // = 8
        $pulled  = 0;
        $errors  = [];

        foreach ($employees as $employee) {
            try {
                // Map DB id (8–89) to API key (EMP001–EMP082) for legacy format
                $legacyApiKey = 'EMP' . str_pad($employee->id - $firstId + 1, 3, '0', STR_PAD_LEFT);

                // For new granular format, try multiple possible user_id mappings:
                // 1. Direct employee ID (e.g., 8, 9, 10)
                // 2. Sequential index (e.g., 1, 2, 3) if API uses 1-based indexing
                $sequentialIndex = (string) ($employee->id - $firstId + 1);

                // Try legacy format first, then new format variations
                $raw = $attendanceMap[$legacyApiKey]
                    ?? $attendanceMap[(string) $employee->id]
                    ?? $attendanceMap[$sequentialIndex]
                    ?? null;

                if (! $raw) {
                    $errors[] = "#{$employee->id} {$employee->full_name}: not found in API (tried keys: {$legacyApiKey}, {$employee->id}, {$sequentialIndex})";
                    continue;
                }

                // Parse raw daily logs into both aggregated metrics AND
                // the structured daily_logs array (new in Phase 2).
                $parsed = $this->parseDailyLogs($raw, $batch->period_start, $batch->period_end);

                AttendanceSnapshot::updateOrCreate(
                    [
                        'payroll_batch_id' => $batch->id,
                        'employee_id'      => $employee->id,
                    ],
                    [
                        // ── Aggregated (backward-compatible) ─────────────────
                        'days_present'      => $parsed['days_present'],
                        'lwop_days'         => $parsed['lwop_days'],
                        'late_minutes'      => $parsed['late_minutes'],
                        'undertime_minutes' => $parsed['undertime_minutes'],
                        'leave_credits'     => $parsed['leave_credits'],
                        // ── New: structured daily breakdown ──────────────────
                        'daily_logs'        => $parsed['daily_logs'],    // JSON array
                        'is_first_cutoff'   => null,                     // full-month snapshot
                        // ── Correction tracking (reset on re-pull) ───────────
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
            'period'  => $batch->period_start . ' → ' . $batch->period_end,
            'pulled'  => $pulled,
            'errors'  => count($errors),
        ]);

        return compact('pulled', 'errors');
    }

    /**
     * Convenience wrapper — semantically clearer alias used by reporting code.
     *
     * @param  PayrollBatch $batch  Must already have period_start / period_end set to full month.
     * @return array  Same as pullForBatch().
     */
    public function pullForMonth(PayrollBatch $batch): array
    {
        return $this->pullForBatch($batch);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Parsing helpers (HRIS API → structured data)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Parse raw HRIS data and return BOTH aggregated metrics AND the
     * structured daily_logs array needed by Phase 2.
     *
     * Expected raw structure (production HRIS API):
     * [
     *   'user_id'      => 'EMP001',
     *   'daily_logs'   => [
     *     ['date' => '2026-05-01', 'am' => ['in' => '07:55:00', 'out' => '12:00:00'],
     *                              'pm' => ['in' => '13:00:00', 'out' => '17:05:00']],
     *     ...
     *   ],
     *   'leave_credits' => 15.0
     * ]
     *
     * Fallback structure (dummy / legacy API):
     * [
     *   'employee_id'      => 'EMP001',
     *   'days_present'     => 11.0,
     *   'lwop_days'        => 0.0,
     *   'late_minutes'     => 0,
     *   'undertime_minutes'=> 0,
     *   'leave_credits'    => 15.0
     * ]
     *
     * @param  array       $raw
     * @param  string|null $periodStart  YYYY-MM-DD — used to determine is_first_cutoff per day
     * @param  string|null $periodEnd    YYYY-MM-DD
     * @return array {
     *   days_present, lwop_days, late_minutes, undertime_minutes, leave_credits,
     *   daily_logs: array<string, array>
     * }
     */
    protected function parseDailyLogs(array $raw, ?string $periodStart = null, ?string $periodEnd = null): array
    {
        if (isset($raw['daily_logs']) && is_array($raw['daily_logs'])) {
            return $this->computeMetricsFromDailyLogs($raw, $periodStart, $periodEnd);
        }

        // ── Legacy / dummy API fallback ──────────────────────────────────
        // No daily detail available; return empty daily_logs so the column
        // is never null (avoids breaking the new cutoff-split helpers).
        return [
            'days_present'      => (float) ($raw['days_present']       ?? 0),
            'lwop_days'         => (float) ($raw['lwop_days']          ?? 0),
            'late_minutes'      => (int)   ($raw['late_minutes']       ?? 0),
            'undertime_minutes' => (int)   ($raw['undertime_minutes']  ?? 0),
            'leave_credits'     => (float) ($raw['leave_credits']      ?? 0),
            'daily_logs'        => [],   // no detail in legacy format
        ];
    }

    /**
     * Compute aggregated metrics AND build the structured daily_logs array
     * from the production HRIS API format.
     *
     * Each entry in daily_logs is keyed by the date string ('YYYY-MM-DD') and has:
     * [
     *   'present'           => bool,
     *   'late_minutes'      => int,
     *   'undertime_minutes' => int,
     *   'is_first_cutoff'   => bool,   // true = days 1-15, false = days 16-end
     * ]
     *
     * @param  array       $raw
     * @param  string|null $periodStart
     * @param  string|null $periodEnd
     * @return array
     */
    protected function computeMetricsFromDailyLogs(array $raw, ?string $periodStart = null, ?string $periodEnd = null): array
    {
        $rawLogs      = $raw['daily_logs'] ?? [];
        $leaveCredits = (float) ($raw['leave_credits'] ?? 0);

        $daysPresent      = 0.0;
        $lwopDays         = 0.0;
        $lateMinutes      = 0;
        $undertimeMinutes = 0;
        $structuredLogs   = [];   // keyed by date string

        foreach ($rawLogs as $log) {
            $date = $log['date'] ?? null;

            if (! $date) {
                continue;
            }

            // Only process dates that fall within the requested period
            if ($periodStart && $periodEnd && ! $this->isWithinPeriod($date, $periodStart, $periodEnd)) {
                continue;
            }

            if (! $this->isWorkday($date)) {
                continue;
            }

            $am    = $log['am'] ?? [];
            $pm    = $log['pm'] ?? [];
            $amIn  = $am['in']  ?? null;
            $amOut = $am['out'] ?? null;
            $pmIn  = $pm['in']  ?? null;
            $pmOut = $pm['out'] ?? null;

            $hasValidLogs = ($amIn || $amOut || $pmIn || $pmOut);

            // Determine which cutoff half this day belongs to
            $dayOfMonth     = (int) Carbon::parse($date)->format('j');
            $isFirstCutoff  = $dayOfMonth <= 15;

            if ($hasValidLogs) {
                $dayLateMinutes      = $amIn  ? $this->computeLateMinutes($amIn)      : 0;
                $dayUndertimeMinutes = $pmOut ? $this->computeUndertimeMinutes($pmOut) : 0;

                $daysPresent      += 1.0;
                $lateMinutes      += $dayLateMinutes;
                $undertimeMinutes += $dayUndertimeMinutes;

                $structuredLogs[$date] = [
                    'present'           => true,
                    'late_minutes'      => $dayLateMinutes,
                    'undertime_minutes' => $dayUndertimeMinutes,
                    'is_first_cutoff'   => $isFirstCutoff,
                ];
            } else {
                // No logs on a workday → LWOP
                $lwopDays += 1.0;

                $structuredLogs[$date] = [
                    'present'           => false,
                    'late_minutes'      => 0,
                    'undertime_minutes' => 0,
                    'is_first_cutoff'   => $isFirstCutoff,
                ];
            }
        }

        // Keep the structured logs sorted by date (ascending) for consistent UI display
        ksort($structuredLogs);

        return [
            'days_present'      => $daysPresent,
            'lwop_days'         => $lwopDays,
            'late_minutes'      => $lateMinutes,
            'undertime_minutes' => $undertimeMinutes,
            'leave_credits'     => $leaveCredits,
            'daily_logs'        => $structuredLogs,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  STEP 2 — Read snapshots (called from PayrollController@compute)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Return the full-month attendance map for a batch, keyed by employee_id.
     * Reads from attendance_snapshots — does NOT call the HRIS API.
     *
     * Used by PayrollComputationService to compute the monthly payroll.
     *
     * @param  PayrollBatch $batch
     * @return array  [ employee_id (int) => AttendanceArray ]
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
     * Return attendance split by cutoff for a batch, keyed by employee_id.
     * Used by reporting code that still needs 1st / 2nd cutoff breakdowns.
     *
     * @param  PayrollBatch $batch
     * @param  bool         $firstCutoff  true = 1st (days 1-15), false = 2nd (days 16-end)
     * @return array  [ employee_id (int) => CutoffAttendanceArray ]
     */
    public function getAttendanceByCutoff(PayrollBatch $batch, bool $firstCutoff): array
    {
        return AttendanceSnapshot::where('payroll_batch_id', $batch->id)
            ->get()
            ->keyBy('employee_id')
            ->map(fn (AttendanceSnapshot $snap) => $snap->toCutoffAttendanceArray($firstCutoff))
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
     *
     * When a correction is saved on a snapshot that has daily_logs, we also
     * recompute the aggregated totals from the (possibly edited) daily_logs
     * so they stay in sync. If daily_logs is absent (legacy), we accept the
     * passed-in values directly.
     *
     * @param  AttendanceSnapshot $snapshot
     * @param  array              $data    Keys: lwop_days, late_minutes, undertime_minutes,
     *                                          leave_credits, correction_note,
     *                                          daily_logs (optional — full replacement)
     * @param  int                $userId  Auth::id() of the correcting officer
     */
    public function correctSnapshot(AttendanceSnapshot $snapshot, array $data, int $userId): void
    {
        // If the caller is replacing daily_logs entirely, recompute aggregated fields
        // from the new logs so everything stays consistent.
        if (isset($data['daily_logs']) && is_array($data['daily_logs'])) {
            $recomputed = $this->aggregateFromDailyLogs($data['daily_logs']);

            $snapshot->update([
                'daily_logs'        => $data['daily_logs'],
                'days_present'      => $recomputed['days_present'],
                'lwop_days'         => $recomputed['lwop_days'],
                'late_minutes'      => $recomputed['late_minutes'],
                'undertime_minutes' => $recomputed['undertime_minutes'],
                // leave_credits is not derived from daily_logs — keep existing unless explicitly provided
                'leave_credits'     => $data['leave_credits'] ?? $snapshot->leave_credits,
                'is_corrected'      => true,
                'correction_note'   => $data['correction_note'] ?? null,
                'corrected_by'      => $userId,
                'corrected_at'      => now(),
                'source'            => 'manual',
            ]);

            return;
        }

        // No daily_logs replacement — just update aggregated fields directly
        $snapshot->update([
            'days_present'      => $data['days_present']      ?? $snapshot->days_present,
            'lwop_days'         => (float) ($data['lwop_days']         ?? $snapshot->lwop_days),
            'late_minutes'      => (int)   ($data['late_minutes']      ?? $snapshot->late_minutes),
            'undertime_minutes' => (int)   ($data['undertime_minutes'] ?? $snapshot->undertime_minutes),
            'leave_credits'     => (float) ($data['leave_credits']     ?? $snapshot->leave_credits),
            'is_corrected'      => true,
            'correction_note'   => $data['correction_note'] ?? null,
            'corrected_by'      => $userId,
            'corrected_at'      => now(),
            'source'            => 'manual',
        ]);
    }

    /**
     * Recompute aggregated fields from a structured daily_logs array.
     * Used internally when HR replaces daily_logs during a correction.
     *
     * @param  array $dailyLogs  Same structure as AttendanceSnapshot::$daily_logs
     * @return array { days_present, lwop_days, late_minutes, undertime_minutes }
     */
    protected function aggregateFromDailyLogs(array $dailyLogs): array
    {
        $daysPresent      = 0.0;
        $lwopDays         = 0.0;
        $lateMinutes      = 0;
        $undertimeMinutes = 0;

        foreach ($dailyLogs as $log) {
            if ($log['present'] ?? false) {
                $daysPresent      += 1.0;
                $lateMinutes      += (int) ($log['late_minutes']      ?? 0);
                $undertimeMinutes += (int) ($log['undertime_minutes'] ?? 0);
            } else {
                $lwopDays += 1.0;
            }
        }

        return compact('daysPresent', 'lwopDays', 'lateMinutes', 'undertimeMinutes');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Single-employee compute (called from PayrollComputationService)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Process attendance data and compute deduction amounts for one employee.
     * Input is a plain array (from snapshot or directly), not a model.
     *
     * Per DOLE RO9 rules: tardiness deductions hit LEAVE CREDITS first.
     * Late minutes are converted to equivalent leave days and deducted from
     * vacation_leave_balance before being treated as LWOP.
     *
     * @param  Employee $employee
     * @param  array    $attendance  Keys: lwop_days, late_minutes, undertime_mins [, days_present]
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

        $lwopDays         = (float) ($attendance['lwop_days']      ?? 0);
        $lateMinutes      = (int)   ($attendance['late_minutes']   ?? 0);
        $undertimeMinutes = (int)   ($attendance['undertime_mins'] ?? $attendance['undertime_minutes'] ?? 0);

        // ── Deduct late from vacation leave credits first ─────────────────
        // Convert late minutes to equivalent leave days (8 hours = 480 mins = 1 day)
        $lateDaysEquivalent = $lateMinutes / 480;
        $vacationBalance    = (float) ($employee->vacation_leave_balance ?? 0);

        if ($vacationBalance > 0 && $lateDaysEquivalent > 0) {
            if ($vacationBalance >= $lateDaysEquivalent) {
                $employee->vacation_leave_balance = $vacationBalance - $lateDaysEquivalent;
                $employee->save();
                $lateMinutesAfterCredits = 0;
            } else {
                $employee->vacation_leave_balance = 0;
                $employee->save();
                $remainingLateDays       = $lateDaysEquivalent - $vacationBalance;
                $lateMinutesAfterCredits = (int) round($remainingLateDays * 480);
            }
        } else {
            $lateMinutesAfterCredits = $lateMinutes;
        }

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

    // ═══════════════════════════════════════════════════════════════════
    //  Date / time utilities
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Check if a given date falls within a period (inclusive).
     *
     * @param  string $date         YYYY-MM-DD
     * @param  string $periodStart  YYYY-MM-DD
     * @param  string $periodEnd    YYYY-MM-DD
     */
    protected function isWithinPeriod(string $date, string $periodStart, string $periodEnd): bool
    {
        try {
            $d     = Carbon::parse($date);
            $start = Carbon::parse($periodStart);
            $end   = Carbon::parse($periodEnd);

            return $d->between($start, $end, true);
        } catch (\Exception $e) {
            Log::warning("Failed to parse dates for period check: {$date}, {$periodStart}, {$periodEnd}");
            return true; // include by default
        }
    }

    /**
     * Check if a given date is a regular workday (Monday–Friday).
     * TODO: Integrate with holiday calendar for accurate LWOP calculation.
     *
     * @param  string $date  YYYY-MM-DD
     */
    protected function isWorkday(string $date): bool
    {
        try {
            $dayOfWeek = date('N', strtotime($date)); // 1 = Monday … 7 = Sunday
            return $dayOfWeek >= 1 && $dayOfWeek <= 5;
        } catch (\Exception $e) {
            Log::warning("Failed to parse date for workday check: {$date}");
            return true;
        }
    }

    /**
     * Compute late minutes based on AM clock-in time.
     * If clock-in is after 08:00:00, returns the delta in whole minutes.
     *
     * @param  string $timeIn  HH:MM:SS format
     * @return int  Late minutes (0 if on time or early)
     */
    protected function computeLateMinutes(string $timeIn): int
    {
        try {
            $inTime        = strtotime($timeIn);
            $thresholdTime = strtotime(self::MORNING_START_THRESHOLD);

            return $inTime > $thresholdTime
                ? (int) round(($inTime - $thresholdTime) / 60)
                : 0;
        } catch (\Exception $e) {
            Log::warning("Failed to compute late minutes for time: {$timeIn}");
            return 0;
        }
    }

    /**
     * Compute undertime minutes based on PM clock-out time.
     * If clock-out is before 17:00:00, returns the missing minutes.
     *
     * @param  string $timeOut  HH:MM:SS format
     * @return int  Undertime minutes (0 if on time or overtime)
     */
    protected function computeUndertimeMinutes(string $timeOut): int
    {
        try {
            $outTime       = strtotime($timeOut);
            $thresholdTime = strtotime(self::AFTERNOON_END_THRESHOLD);

            return $outTime < $thresholdTime
                ? (int) round(($thresholdTime - $outTime) / 60)
                : 0;
        } catch (\Exception $e) {
            Log::warning("Failed to compute undertime minutes for time: {$timeOut}");
            return 0;
        }
    }
}