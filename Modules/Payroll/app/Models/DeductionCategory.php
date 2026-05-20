<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DeductionCategory
 *
 * First-class model for the deduction_categories table.
 * Previously, categories were a hard-coded array in DeductionTypeController.
 * Moving them to the DB makes them fully dynamic through the CMS.
 *
 * The `key` column mirrors the value stored in deduction_types.category,
 * so no FK constraint is needed — the two tables are loosely coupled on purpose.
 */
class DeductionCategory extends Model
{
    protected $table = 'deduction_categories';

    protected $fillable = [
        'key',
        'label',
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
     * Matched by the string key, not a FK integer.
     */
    public function deductionTypes(): HasMany
    {
        return $this->hasMany(DeductionType::class, 'category', 'key');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('label');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Returns [key => label] map — drop-in replacement for the old static array
     * in DeductionTypeController::categoryLabels().
     *
     * Usage:
     *   $categoryLabels = DeductionCategory::labelsMap();
     */
    public static function labelsMap(bool $activeOnly = true): array
    {
        $query = static::ordered();
        if ($activeOnly) {
            $query->active();
        }
        return $query->pluck('label', 'key')->all();
    }
}
