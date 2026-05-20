<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class DeductionType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'display_order',
        'category',
        'is_computed',
        'is_active',
        'notes',
        // Enhancement #1 — override support for computed types
        'override_amount',
        'override_note',
    ];

    protected $casts = [
        'is_computed'     => 'boolean',
        'is_active'       => 'boolean',
        'display_order'   => 'integer',
        'override_amount' => 'decimal:2',
    ];

    // ── Category constants (kept for any code that still references them) ──
    const CAT_PAGIBIG    = 'pagibig';
    const CAT_PHILHEALTH = 'philhealth';
    const CAT_GSIS       = 'gsis';
    const CAT_OTHER_GOV  = 'other_gov';
    const CAT_LOAN       = 'loan';
    const CAT_CARESS     = 'caress';
    const CAT_MISC       = 'misc';

    // ── Relationships ─────────────────────────────────────────────────────

    public function enrollments(): HasMany
    {
        return $this->hasMany(EmployeeDeductionEnrollment::class);
    }

    public function payrollDeductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order');
    }

    public function scopeComputed(Builder $query): Builder
    {
        return $query->where('is_computed', true);
    }

    public function scopeManual(Builder $query): Builder
    {
        return $query->where('is_computed', false);
    }

    // ── Enhancement #1 helpers ────────────────────────────────────────────

    /**
     * Returns true when a computed type has been manually overridden.
     */
    public function isOverridden(): bool
    {
        return $this->is_computed && $this->override_amount !== null;
    }

    /**
     * Clears the override, restoring formula-based computation.
     */
    public function clearOverride(): void
    {
        $this->update(['override_amount' => null, 'override_note' => null]);
    }
}
