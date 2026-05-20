<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Models\DeductionCategory;
use Modules\Payroll\Models\DeductionType;
use Illuminate\Http\Request;

/**
 * DeductionCategoryController
 *
 * Manages the deduction_categories table introduced in Enhancement #2.
 *
 * Routes to add to payroll/routes/web.php:
 *
 *   Route::resource('deduction-categories', DeductionCategoryController::class)
 *        ->except(['show'])
 *        ->names('deduction-categories');
 *   Route::patch('deduction-categories/{deductionCategory}/toggle',
 *                [DeductionCategoryController::class, 'toggle'])
 *        ->name('deduction-categories.toggle');
 */
class DeductionCategoryController extends Controller
{
    /** List all categories. */
    public function index()
    {
        $categories = DeductionCategory::ordered()->get();

        // For each category, also count how many deduction types use it
        $typeCounts = DeductionType::query()
            ->selectRaw('category, COUNT(*) as cnt')
            ->groupBy('category')
            ->pluck('cnt', 'category');

        return view('payroll::deduction-categories.index', compact('categories', 'typeCounts'));
    }

    /** Create form. */
    public function create()
    {
        $nextOrder = DeductionCategory::max('display_order') + 1;
        return view('payroll::deduction-categories.create', compact('nextOrder'));
    }

    /** Persist a new category. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'key'           => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                'unique:deduction_categories,key',
            ],
            'label'         => 'required|string|max:100|unique:deduction_categories,label',
            'display_order' => 'required|integer|min:0',
        ]);

        $data['is_active'] = true;

        DeductionCategory::create($data);

        return redirect()->route('deduction-categories.index')
            ->with('success', "Category \"{$data['label']}\" created.");
    }

    /** Edit form. */
    public function edit(DeductionCategory $deductionCategory)
    {
        return view('payroll::deduction-categories.edit', compact('deductionCategory'));
    }

    /** Update. The `key` is immutable after creation (mirrors deduction_types.category). */
    public function update(Request $request, DeductionCategory $deductionCategory)
    {
        $data = $request->validate([
            // key is immutable
            'label'         => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('deduction_categories', 'label')
                    ->ignore($deductionCategory->id),
            ],
            'display_order' => 'required|integer|min:0',
        ]);

        $deductionCategory->update($data);

        return redirect()->route('deduction-categories.index')
            ->with('success', "Category \"{$deductionCategory->label}\" updated.");
    }

    /** Toggle active/inactive. Cannot deactivate a category that still has active deduction types. */
    public function toggle(DeductionCategory $deductionCategory)
    {
        // Guard: prevent deactivating a category that still has active types
        if ($deductionCategory->is_active) {
            $activeCount = DeductionType::where('category', $deductionCategory->key)
                ->where('is_active', true)
                ->count();

            if ($activeCount > 0) {
                return back()->with(
                    'error',
                    "Cannot deactivate \"{$deductionCategory->label}\" — it still has {$activeCount} active deduction type(s). Deactivate those first."
                );
            }
        }

        $deductionCategory->update(['is_active' => ! $deductionCategory->is_active]);
        $state = $deductionCategory->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Category \"{$deductionCategory->label}\" {$state}.");
    }

    /**
     * Permanently delete a category.
     *
     * Only allowed when:
     *   1. The category is inactive.
     *   2. No deduction types are assigned to it (active or otherwise).
     *
     * This prevents orphaning existing deduction types whose `category` column
     * references this category's key.
     */
    public function destroy(DeductionCategory $deductionCategory)
    {
        if ($deductionCategory->is_active) {
            return back()->with('error', "Cannot delete an active category. Deactivate it first.");
        }

        $typeCount = DeductionType::where('category', $deductionCategory->key)->count();

        if ($typeCount > 0) {
            return back()->with(
                'error',
                "Cannot delete \"{$deductionCategory->label}\" — it still has {$typeCount} deduction type(s) assigned. Reassign or delete those types first."
            );
        }

        $label = $deductionCategory->label;
        $deductionCategory->delete();

        return redirect()->route('deduction-categories.index')
            ->with('success', "Category \"{$label}\" has been permanently deleted.");
    }
}
