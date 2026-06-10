<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DeductionTypeCategory
 *
 * Managed categories for deduction types.  The `code` column maps 1-to-1
 * with the existing deduction_types.category string and must never change
 * after creation — it is the runtime key used by DeductionService and
 * PayrollComputationService.
 *
 * Soft-deletes are used instead of hard deletes because a category that
 * has deduction types attached must never be physically removed.
 *
 * @property int         $id
 * @property string      $code            Immutable. Maps to deduction_types.category.
 * @property string      $name            Display label shown in the UI.
 * @property string|null $description     Optional longer description.
 * @property int         $display_order
 * @property bool        $is_active
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 */
class DeductionTypeCategory extends Model
{
    use SoftDeletes;

    protected $table = 'deduction_type_categories';

    protected $fillable = [
        'code',
        'name',
        'description',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    /**
     * All deduction types that belong to this category.
     * Includes inactive types — callers should scope as needed.
     */
    public function deductionTypes(): HasMany
    {
        return $this->hasMany(DeductionType::class, 'deduction_type_category_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    /** Only active, non-deleted categories. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Default sort by display_order, then name. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * True when this category has at least one attached deduction type
     * (active or inactive).  Used to block hard deletes and warn on soft
     * deletes.
     */
    public function hasDeductionTypes(): bool
    {
        return $this->deductionTypes()->exists();
    }

    /**
     * Count of active deduction types in this category.
     */
    public function activeDeductionTypeCount(): int
    {
        return $this->deductionTypes()->where('is_active', true)->count();
    }
}