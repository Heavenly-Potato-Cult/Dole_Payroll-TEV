<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'period_year',
        'period_month',
        // 'cutoff' removed — system now uses a single batch per month.
        'period_start',
        'period_end',
        'release_date',
        'status',
        'created_by',
        'prepared_at',
        // 'hr_approved_by' / 'hr_approved_at' kept in DB for historical data
        // but are no longer written by the new workflow.
        'hr_approved_by',
        'hr_approved_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'released_by',
        'released_at',
        'remarks',
    ];

    protected $casts = [
        'period_year'    => 'integer',
        'period_month'   => 'integer',
        'prepared_at'    => 'datetime',
        'hr_approved_at' => 'datetime',
        'reviewed_at'    => 'datetime',
        'approved_at'    => 'datetime',
        'released_at'    => 'datetime',
        'period_start'   => 'date',
        'period_end'     => 'date',
        'release_date'   => 'date',
    ];

    // ── Status constants ──────────────────────────────────────────────
    // New workflow: draft → computed → pending_accountant → pending_rd → released → locked
    // (pending_hr removed)
    const STATUS_DRAFT               = 'draft';
    const STATUS_COMPUTED            = 'computed';
    const STATUS_PENDING_ACCOUNTANT  = 'pending_accountant';
    const STATUS_PENDING_RD          = 'pending_rd';
    const STATUS_RELEASED            = 'released';
    const STATUS_LOCKED              = 'locked';

    // ── Relationships ─────────────────────────────────────────────────

    /** HR/Payroll Officer who created the batch */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * HR approver — kept for backward compatibility with historical batches.
     * Not used in the new monthly workflow.
     */
    public function hrApprover()
    {
        return $this->belongsTo(\App\Models\User::class, 'hr_approved_by');
    }

    /** Accountant who certified funds */
    public function reviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    /** RD/ARD who approved and released */
    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /** Cashier who locked after disbursement */
    public function releaser()
    {
        return $this->belongsTo(\App\Models\User::class, 'released_by');
    }

    public function entries()
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function attendanceSnapshots()
    {
        return $this->hasMany(AttendanceSnapshot::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(PayrollAuditLog::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * Human-readable period label, e.g. "May 2026".
     */
    public function getPeriodLabelAttribute(): string
    {
        return \Carbon\Carbon::create($this->period_year, $this->period_month, 1)
            ->format('F Y');
    }

    /**
     * Human-readable date range, e.g. "May 1–31, 2026".
     */
    public function getPeriodRangeAttribute(): string
    {
        $start = \Carbon\Carbon::parse($this->period_start)->format('F j');
        $end   = \Carbon\Carbon::parse($this->period_end)->format('j, Y');

        return "{$start}–{$end}";
    }

    /**
     * Checks whether attendance has been pulled for this batch.
     * Uses a count so no extra query is fired if the relation is already loaded.
     */
    public function hasAttendance(): bool
    {
        return $this->attendanceSnapshots()->exists();
    }
}