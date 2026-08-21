<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SpecialPayrollBatch
 *
 * Covers: newly_hired, salary_differential, nosi, nosa, step_increment, generic_special
 *
 * Table columns (from migration 2026_03_20_200011_create_special_payroll_batches_table,
 * plus 2026_08_18_083047_add_pera_override_to_special_payroll_batches_table
 * and 2026_08_19_..._add_pera_resolved_amount_to_special_payroll_batches_table):
 *   id, type, title, year, month, effectivity_date,
 *   period_start, period_end, employee_id,
 *   old_basic_salary, new_basic_salary, differential_amount,
 *   old_step, new_step, old_salary_grade, new_salary_grade,
 *   old_position, new_position, pro_rated_days,
 *   gross_amount, deductions_amount, gsis_rate_applied,
 *   pera_override, pera_resolved_amount, net_amount,
 *   status, approved_by, approved_at, remarks, timestamps
 *
 * @property int $id
 * @property string $type
 * @property string $title
 * @property int $year
 * @property int $month
 * @property \Carbon\Carbon|null $effectivity_date
 * @property \Carbon\Carbon|null $period_start
 * @property \Carbon\Carbon|null $period_end
 * @property int $employee_id
 * @property float|null $old_basic_salary
 * @property float|null $new_basic_salary
 * @property float|null $differential_amount
 * @property float|null $pera_override  Manual PERA figure — an override of the
 *   auto pro-rated amount for type=newly_hired/transferee, or a flat one-time
 *   back-pay adjustment for type=salary_differential/nosi/nosa. Null = no
 *   PERA involved in this batch.
 * @property float|null $pera_resolved_amount  Monthly PERA base resolved via
 *   AllowanceService::resolveForPeriod() at creation time and frozen from
 *   then on (type=newly_hired/transferee only). Null on batches created
 *   before this column existed, and whenever pera_override is set instead.
 * @property float|null $pro_rated_days
 * @property float|null $gross_amount
 * @property float|null $deductions_amount
 * @property float|null $net_amount
 * @property string $status
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property string|null $remarks
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\SharedKernel\Models\Employee $employee
 * @property-read \App\Models\User $approver
 * @property-read \App\Models\User $creator
 */
class SpecialPayrollBatch extends Model
{
    protected $fillable = [
        'type',
        'title',
        'year',
        'month',
        'effectivity_date',
        'period_start',
        'period_end',
        'employee_id',
        'old_basic_salary',
        'new_basic_salary',
        'differential_amount',
        'old_step',
        'new_step',
        'old_salary_grade',
        'new_salary_grade',
        'old_position',
        'new_position',
        'pro_rated_days',
        'gross_amount',
        'deductions_amount',
        'gsis_rate_applied',
        'pera_override',
        'pera_resolved_amount',
        'net_amount',
        'status',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected $casts = [
        'effectivity_date' => 'date',
        'period_start'     => 'date',
        'period_end'       => 'date',
        'approved_at'      => 'datetime',
        'old_basic_salary' => 'decimal:2',
        'new_basic_salary' => 'decimal:2',
        'differential_amount' => 'decimal:2',
        'pro_rated_days'   => 'decimal:3',
        'gross_amount'     => 'decimal:2',
        'deductions_amount'=> 'decimal:2',
        'gsis_rate_applied'=> 'decimal:4',
        'pera_override'    => 'decimal:2',
        'pera_resolved_amount' => 'decimal:2',
        'net_amount'       => 'decimal:2',
    ];
    // ── Relationships ──────────────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\SharedKernel\Models\Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Optional allowance lines (RATA/etc) applied to this batch — currently
     * only populated for type = newly_hired / transferee via
     * NewlyHiredPayrollService + AllowanceService::proRateLines().
     */
    public function allowances()
    {
        return $this->hasMany(
            \Modules\Payroll\Models\Allowances\SpecialPayrollBatchAllowance::class,
            'special_payroll_batch_id'
        );
    }

    public function creator(): BelongsTo
    {
        // created_by is not in the migration — use created_at / Auth context instead.
        // If you add created_by later: return $this->belongsTo(User::class, 'created_by');
        return $this->belongsTo(\App\Models\User::class, 'approved_by'); // placeholder
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeNewlyHired($query)
    {
        return $query->where('type', 'newly_hired');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isReleased(): bool
    {
        return $this->status === 'released';
    }

    public function periodLabel(): string
    {
        if ($this->period_start && $this->period_end) {
            return $this->period_start->format('M d') . '–' . $this->period_end->format('d, Y');
        }
        return $this->effectivity_date ? $this->effectivity_date->format('M d, Y') : '—';
    }
}
