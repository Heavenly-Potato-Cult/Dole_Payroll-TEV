<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDeductionEnrollment extends Model
{
    protected $fillable = [
        'employee_id',
        'deduction_type_id',
        'account_number',    // ← new: distinguishes multiple accounts of the same loan-category type
        'amount',
        'percentage_override',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'amount'             => 'decimal:2',
        'percentage_override' => 'decimal:2',
        'effective_from'     => 'date',
        'effective_to'       => 'date',
        'is_active'          => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\SharedKernel\Models\Employee::class);
    }

    public function deductionType(): BelongsTo
    {
        return $this->belongsTo(DeductionType::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Enrollments active during a given payroll date.
     * Use this when building a payroll run to pull the correct amounts.
     */
    public function scopeActiveOn($query, string $date)
    {
        return $query->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date);
            });
    }
}
