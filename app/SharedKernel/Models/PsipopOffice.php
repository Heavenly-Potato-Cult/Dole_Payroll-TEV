<?php

namespace App\SharedKernel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class PsipopOffice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    /**
     * Name of the catch-all bucket every employee falls into when PSIPOP
     * placement isn't yet known (new HRIS syncs, imports, manual creation).
     * Must match the seeder's last row — see PsipopOfficeSeeder.
     */
    public const NAME_UNASSIGNED = 'UNASSIGNED / FOR PSIPOP REVIEW';

    protected static function booted(): void
    {
        // Default display order everywhere this model is queried,
        // matching the DBM-mandated PSIPOP section order (sort_order).
        static::addGlobalScope('sortOrder', function ($query) {
            $query->orderBy('sort_order');
        });
    }

    // ── Relationships ────────────────────────────────────────────

    /**
     * Employees placed in this PSIPOP office grouping.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(\App\SharedKernel\Models\Employee::class);
    }

    // ── Helpers ──────────────────────────────────────────────────

    /**
     * Resolve (and lightly cache) the id of the fallback "unassigned"
     * bucket. Used by Employee::booted() so every new record — including
     * ones created by the HRIS pullFromApi() sync, which has no PSIPOP
     * field in its payload — always groups into a real, visible row
     * instead of a silent null.
     *
     * Falls back to creating the row on the fly if the seeder hasn't run
     * yet, so this never throws in a fresh environment.
     */
    public static function unassignedId(): int
    {
        return Cache::remember('psipop_office:unassigned_id', now()->addHour(), function () {
            return static::withoutGlobalScopes()
                ->firstOrCreate(
                    ['name' => self::NAME_UNASSIGNED],
                    ['sort_order' => 7, 'is_active' => true]
                )
                ->getKey();
        });
    }
}
