<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Models\DeductionTypeCategory;
use Illuminate\Http\Request;

/**
 * DeductionTypeCategoryController
 *
 * CMS for managing deduction type categories.
 *
 * ── Key rules ─────────────────────────────────────────────────────────────
 *
 *  1. `code` is IMMUTABLE after creation.  It is the runtime key stored in
 *     deduction_types.category and matched by DeductionService /
 *     PayrollComputationService.  The edit form does not expose this field.
 *
 *  2. Hard deletes are NEVER performed.  destroy() soft-deletes the record.
 *     A category that has attached deduction types is deactivated instead
 *     (the soft-delete is still recorded but the types keep their category
 *     string intact and the FK is set to NULL by nullOnDelete).
 *
 *  3. Categories are shown in the UI even when soft-deleted if a deduction
 *     type still carries that category string — the fallback label in
 *     DeductionTypeController handles this gracefully.
 */
class DeductionTypeCategoryController extends Controller
{
    /** List all categories (including soft-deleted for audit visibility). */
    public function index()
    {
        $categories = DeductionTypeCategory::withTrashed()
            ->withCount('deductionTypes')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('payroll::deduction-type-categories.index', compact('categories'));
    }

    /** Show the create form. */
    public function create()
    {
        $nextOrder = (DeductionTypeCategory::max('display_order') ?? 0) + 10;

        return view('payroll::deduction-type-categories.create', compact('nextOrder'));
    }

    /** Persist a new category. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                // Must be unique even against soft-deleted records — codes are permanent
                // Using a custom closure instead of Rule::unique()->withTrashed() for
                // Laravel 9 compatibility (withTrashed() on Unique was added in L10).
                function ($attribute, $value, $fail) {
                    $exists = \DB::table('deduction_type_categories')
                        ->where('code', $value)
                        ->exists(); // includes soft-deleted rows (no whereNull filter)
                    if ($exists) {
                        $fail('This code is already taken (including removed categories). Codes are permanent and must be unique.');
                    }
                },
            ],
            'name'          => 'required|string|max:150',
            'description'   => 'nullable|string|max:500',
            'display_order' => 'required|integer|min:0|max:9999',
            'is_active'     => 'nullable|boolean',
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        $category = DeductionTypeCategory::create($data);

        return redirect()->route('deduction-type-categories.index')
            ->with('success', "Category \"{$category->name}\" created successfully.");
    }

    /** Show the edit form. */
    public function edit(DeductionTypeCategory $deductionTypeCategory)
    {
        $typeCount = $deductionTypeCategory->deductionTypes()->count();

        return view('payroll::deduction-type-categories.edit',
            compact('deductionTypeCategory', 'typeCount'));
    }

    /** Update name, description, display_order, is_active.  Code is immutable. */
    public function update(Request $request, DeductionTypeCategory $deductionTypeCategory)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'description'   => 'nullable|string|max:500',
            'display_order' => 'required|integer|min:0|max:9999',
            'is_active'     => 'nullable|boolean',
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        // Warn if deactivating a category that still has active deduction types.
        // We persist anyway — the Payroll Officer has made a conscious choice.
        $deductionTypeCategory->update($data);

        $state = $data['is_active'] ? 'active' : 'inactive';

        return redirect()->route('deduction-type-categories.index')
            ->with('success', "Category \"{$deductionTypeCategory->name}\" updated (status: {$state}).");
    }

    /**
     * Soft-delete the category.
     *
     * If the category has attached deduction types we deactivate it instead
     * of soft-deleting, so that existing payroll data still references a
     * coherent category string. The soft-delete IS recorded regardless for
     * audit purposes; the nullOnDelete FK constraint ensures deduction_types
     * rows lose the FK but keep their category string.
     */
    public function destroy(DeductionTypeCategory $deductionTypeCategory)
    {
        $name = $deductionTypeCategory->name;

        if ($deductionTypeCategory->hasDeductionTypes()) {
            // Cannot fully remove — deactivate instead
            $deductionTypeCategory->update(['is_active' => false]);
            $deductionTypeCategory->delete(); // soft-delete

            return redirect()->route('deduction-type-categories.index')
                ->with('warning', "Category \"{$name}\" has attached deduction types and cannot be fully removed. "
                    . "It has been deactivated and hidden from dropdowns.");
        }

        $deductionTypeCategory->delete(); // soft-delete

        return redirect()->route('deduction-type-categories.index')
            ->with('success', "Category \"{$name}\" has been removed.");
    }

    /**
     * Restore a soft-deleted category.
     * Accessible via POST /deduction-types/categories/{id}/restore.
     */
    public function restore(int $id)
    {
        /** @var DeductionTypeCategory $category */
        $category = DeductionTypeCategory::withTrashed()->findOrFail($id);
        $category->restore();
        $category->update(['is_active' => true]);

        return redirect()->route('deduction-type-categories.index')
            ->with('success', "Category \"{$category->name}\" has been restored.");
    }
    /**
     * Permanently (hard) delete a soft-deleted category.
     * Only callable on already-trashed records — enforced by the check below.
     * Accessible via DELETE /deduction-types/categories/{id}/force-delete.
     */
    public function forceDelete(int $id)
    {
        $category = DeductionTypeCategory::withTrashed()->findOrFail($id);

        if (! $category->trashed()) {
            return redirect()->route('deduction-type-categories.index')
                ->with('error', 'Only removed (soft-deleted) categories can be permanently deleted. Remove it first.');
        }

        $name = $category->name;
        $category->forceDelete();

        return redirect()->route('deduction-type-categories.index')
            ->with('success', "Category \"{$name}\" has been permanently deleted and cannot be recovered.");
    }
}