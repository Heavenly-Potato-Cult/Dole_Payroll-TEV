<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * DeductionType
 *
 * Three-tier deduction resolution (used by DeductionService):
 *
 *  Tier 1 — Formula-driven  (is_computed = true)
 *    Amounts are calculated per-employee using salary-based formulas.
 *    override_amount bypasses the formula when set (Enhancement #1).
 *    is_locked on these types means override_amount is treated as a
 *    global fixed amount — HR cannot edit per-employee.
 *    Codes: PAG_IBIG_1, PHILHEALTH, GSIS_LIFE_RETIREMENT, WITHHOLDING_TAX
 *
 *  Tier 2 — Locked global amount  (is_computed = false, is_locked = true)
 *    default_amount is applied uniformly to all employees.
 *    Employee enrollment form shows the amount read-only.
 *    Changing default_amount in the CMS immediately affects all employees
 *    on the next payroll run — no per-employee record changes needed.
 *    Typical examples: MASS, CARESS IX Union Dues, fixed HMO premium.
 *
 *  Tier 3 — Unlocked / per-employee  (is_computed = false, is_locked = false)
 *    default_amount (if set) pre-fills the enrollment form.
 *    HR may override per employee. Used for loans and variable deductions.
 *    Loan-category types (category = 'loan' or 'caress') are ALWAYS treated
 *    as Tier 3 by DeductionService regardless of is_locked, because loan
 *    amortisations are inherently per-employee.
 */
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
        // Enhancement #1 — formula override for computed types
        'override_amount',
        'override_note',
        // Global amount + lock mechanism
        'default_amount',
        'is_locked',
    ];

    protected $casts = [
        'is_computed'     => 'boolean',
        'is_active'       => 'boolean',
        'is_locked'       => 'boolean',
        'display_order'   => 'integer',
        'override_amount' => 'decimal:2',
        'default_amount'  => 'decimal:2',
    ];

    // ── Category constants ────────────────────────────────────────────────
    const CAT_PAGIBIG    = 'pagibig';
    const CAT_PHILHEALTH = 'philhealth';
    const CAT_GSIS       = 'gsis';
    const CAT_OTHER_GOV  = 'other_gov';
    const CAT_LOAN       = 'loan';
    const CAT_CARESS     = 'caress';
    const CAT_MISC       = 'misc';

    /**
     * Categories whose types are ALWAYS treated as per-employee (Tier 3),
     * even if is_locked = true.  Loan amortisations differ per person.
     */
    const LOAN_CATEGORIES = [self::CAT_LOAN, self::CAT_CARESS];

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

    public function scopeLocked(Builder $query): Builder
    {
        return $query->where('is_locked', true);
    }

    // ── Tier helpers ──────────────────────────────────────────────────────

    /**
     * True for the 4 formula-driven government-mandatory types.
     * These always use is_computed = true in the database.
     */
    public function isFormulaDriven(): bool
    {
        return $this->is_computed;
    }

    /**
     * True when is_locked is set AND the type is not in a loan category.
     * Loan-category types are always per-employee regardless.
     */
    public function isEffectivelyLocked(): bool
    {
        if (in_array($this->category, self::LOAN_CATEGORIES)) {
            return false;
        }
        return (bool) $this->is_locked;
    }

    /**
     * Returns the global amount to use when effectively locked.
     * For formula types, this is override_amount.
     * For manual types, this is default_amount.
     * Returns null if no global amount has been configured.
     */
    public function globalAmount(): ?float
    {
        if ($this->isFormulaDriven()) {
            return $this->override_amount !== null ? (float) $this->override_amount : null;
        }
        return $this->default_amount !== null ? (float) $this->default_amount : null;
    }

    // ── Enhancement #1 helpers (preserved) ───────────────────────────────

    /**
     * True when a computed (formula) type has a manual override set.
     */
    public function isOverridden(): bool
    {
        return $this->is_computed && $this->override_amount !== null;
    }

    /**
     * Clears the formula override, restoring formula-based computation.
     */
    public function clearOverride(): void
    {
        $this->update(['override_amount' => null, 'override_note' => null]);
    }
}
