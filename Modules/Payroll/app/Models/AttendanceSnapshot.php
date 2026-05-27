<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSnapshot extends Model
{
    protected $fillable = [
        'payroll_batch_id',
        'employee_id',
        'days_present',
        'lwop_days',
        'late_minutes',
        'undertime_minutes',
        'leave_credits',
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

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * Convert to the array shape that PayrollComputationService@computeEntry expects.
     * Keep the key names in sync with what computeEntry() reads.
     */
    public function toAttendanceArray(): array
    {
        return [
            'lwop_days'       => (float) $this->lwop_days,
            'late_minutes'    => (int)   $this->late_minutes,
            'undertime_mins'  => (int)   $this->undertime_minutes,
            'ytd_gross'       => 0.0,    // TODO: real YTD tracking in a later phase
        ];
    }
}
