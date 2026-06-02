<?php

namespace Modules\Payroll\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSnapshot extends Model
{
    protected $fillable = [
        'payroll_batch_id',
        'employee_id',
        // Aggregated fields (kept for backward compatibility)
        'days_present',
        'lwop_days',
        'late_minutes',
        'undertime_minutes',
        'leave_credits',
        // New: day-by-day breakdown for on-the-fly cutoff calculation
        'daily_logs',
        // New: true = days 1-15 (1st cutoff), false = days 16-end (2nd cutoff)
        // null = full-month snapshot (new default)
        'is_first_cutoff',
        // Correction tracking
        'is_corrected',
        'correction_note',
        'corrected_by',
        'corrected_at',
        'source',
        'fetched_at',
    ];

    protected $casts = [
        'days_present'      => 'decimal:3',
        'lwop_days'         => 'decimal:3',
        'late_minutes'      => 'integer',
        'undertime_minutes' => 'integer',
        'leave_credits'     => 'decimal:3',
        'daily_logs'        => 'array',   // JSON → PHP array automatically
        'is_first_cutoff'   => 'boolean',
        'is_corrected'      => 'boolean',
        'corrected_at'      => 'datetime',
        'fetched_at'        => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PayrollBatch::class, 'payroll_batch_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\SharedKernel\Models\Employee::class);
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'corrected_by');
    }

    // ── Daily-log helpers ─────────────────────────────────────────────

    /**
     * Return only the daily-log entries that fall in the 1st cutoff (days 1-15).
     *
     * @return array<string, array>  Keys are date strings ('YYYY-MM-DD').
     */
    public function firstCutoffLogs(): array
    {
        return $this->filterLogsByCutoff(true);
    }

    /**
     * Return only the daily-log entries that fall in the 2nd cutoff (days 16-end).
     *
     * @return array<string, array>  Keys are date strings ('YYYY-MM-DD').
     */
    public function secondCutoffLogs(): array
    {
        return $this->filterLogsByCutoff(false);
    }

    /**
     * Filter daily_logs by cutoff half.
     *
     * @param  bool  $firstCutoff  true = days 1-15, false = days 16-end
     */
    protected function filterLogsByCutoff(bool $firstCutoff): array
    {
        if (empty($this->daily_logs)) {
            return [];
        }

        return array_filter(
            $this->daily_logs,
            fn(array $log) => ($log['is_first_cutoff'] ?? false) === $firstCutoff
        );
    }

    /**
     * Count days where the employee was present in a given cutoff half.
     *
     * @param  bool  $firstCutoff
     */
    public function daysPresent(bool $firstCutoff): float
    {
        $logs = $this->filterLogsByCutoff($firstCutoff);

        return (float) collect($logs)
            ->where('present', true)
            ->count();
    }

    /**
     * Sum late_minutes for a given cutoff half.
     */
    public function lateMinutes(bool $firstCutoff): int
    {
        return (int) collect($this->filterLogsByCutoff($firstCutoff))
            ->sum('late_minutes');
    }

    /**
     * Sum undertime_minutes for a given cutoff half.
     */
    public function undertimeMinutes(bool $firstCutoff): int
    {
        return (int) collect($this->filterLogsByCutoff($firstCutoff))
            ->sum('undertime_minutes');
    }

    /**
     * Build the metrics array for a specific cutoff, ready for
     * PayrollComputationService::computeEntry().
     *
     * @param  bool  $firstCutoff  true = 1st cutoff, false = 2nd cutoff
     * @param  float $ytdGross     Pass-through; real YTD tracking deferred.
     */
    public function toCutoffAttendanceArray(bool $firstCutoff, float $ytdGross = 0.0): array
    {
        $logs = $this->filterLogsByCutoff($firstCutoff);

        $daysPresent      = (float) collect($logs)->where('present', true)->count();
        $lwopDays         = (float) collect($logs)->where('present', false)->count();
        $lateMinutes      = (int)   collect($logs)->sum('late_minutes');
        $undertimeMinutes = (int)   collect($logs)->sum('undertime_minutes');

        return [
            'days_present'    => $daysPresent,
            'lwop_days'       => $lwopDays,
            'late_minutes'    => $lateMinutes,
            'undertime_mins'  => $undertimeMinutes,
            'ytd_gross'       => $ytdGross,
        ];
    }

    // ── Legacy helper (kept for backward compatibility) ────────────────

    /**
     * Convert to the array shape that PayrollComputationService::computeEntry() expects.
     * Uses full-month aggregated values (not split by cutoff).
     *
     * @deprecated  Use toCutoffAttendanceArray() for cutoff-aware splits.
     */
    public function toAttendanceArray(): array
    {
        return [
            'days_present'   => (float) $this->days_present,
            'lwop_days'      => (float) $this->lwop_days,
            'late_minutes'   => (int)   $this->late_minutes,
            'undertime_mins' => (int)   $this->undertime_minutes,
            'ytd_gross'      => 0.0,
        ];
    }

    // ── Example daily_logs structure (for reference) ──────────────────
    //
    // [
    //   "2026-05-01" => [
    //       "present"           => true,
    //       "late_minutes"      => 0,
    //       "undertime_minutes" => 0,
    //       "is_first_cutoff"   => true,   // day 1 → 1st cutoff
    //   ],
    //   "2026-05-15" => [
    //       "present"           => true,
    //       "late_minutes"      => 10,
    //       "undertime_minutes" => 0,
    //       "is_first_cutoff"   => true,   // day 15 → still 1st cutoff
    //   ],
    //   "2026-05-16" => [
    //       "present"           => false,
    //       "late_minutes"      => 0,
    //       "undertime_minutes" => 0,
    //       "is_first_cutoff"   => false,  // day 16 → 2nd cutoff
    //   ],
    // ]
}