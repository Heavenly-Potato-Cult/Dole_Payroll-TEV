<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Models\DeductionType;
use Modules\Payroll\Models\DeductionCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * DeductionTypeController
 *
 * CMS for managing deduction types.
 *
 * ── CRITICAL CONTRACT ────────────────────────────────────────────────────────
 * The `code` field is IMMUTABLE after creation. It is the cross-system
 * contract key used by:
 *   1. DeductionService::resolveDeductions()         — computed[] map keys
 *   2. PayrollComputationService::computeEntry()     — match() code keys
 *   3. employee_deduction_enrollments.deduction_type_id — via code lookup
 *
 * Enhancement #1 — Override amounts:
 *   Authorized users may set a manual `override_amount` on a computed type.
 *   DeductionService::resolveDeductions() checks isOverridden() first and
 *   uses override_amount when set, bypassing the formula.
 *
 * Enhancement #1b — Lock / Unlock (is_computed toggle):
 *   `is_computed` may now be changed via the Edit UI.
 *   - Locking (false → true) re-enables formula-driven computation.
 *   - Unlocking (true → false) disables the formula; the type becomes
 *     enrollment-based (manual amounts per employee).
 *   When unlocking, any active override is automatically cleared.
 *
 * Enhancement #2 — Dynamic categories:
 *   categoryLabels() now reads from deduction_categories table instead of a
 *   hard-coded array. A dedicated DeductionCategoryController manages CRUD.
 *
 * Enhancement #3 — Display order uniqueness:
 *   display_order is validated to be unique *within its category* on both
 *   store() and update(). Duplicates across categories are allowed.
 *
 * FIX — existingOrders moved to controller:
 *   Previously the Blade @section('scripts') called DeductionType::all()
 *   directly inside a @json() directive.  Any PHP exception there silently
 *   broke the entire <script> block (including the submit-listener setup),
 *   which is why the Save button appeared to do nothing.  The data is now
 *   computed here and passed as a plain PHP variable.
 * ─────────────────────────────────────────────────────────────────────────────
 */
class DeductionTypeController extends Controller
{
    /** Display the full list of deduction types, grouped by category. */
    public function index()
    {
        $types = DeductionType::orderBy('display_order')
            ->orderBy('name')
            ->get();

        $grouped = $types->groupBy('category');

        $categoryLabels = self::categoryLabels();

        return view('payroll::deduction-types.index', compact('grouped', 'categoryLabels'));
    }

    /** Show the create form. */
    public function create()
    {
        $categories     = DeductionCategory::active()->ordered()->get();
        $categoryLabels = $categories->pluck('label', 'key')->all();
        $nextOrder      = DeductionType::max('display_order') + 1;

        // FIX: compute existingOrders in PHP so the Blade scripts section
        // never needs to run a raw model query inside @json().
        $existingOrders = DeductionType::all()
            ->groupBy('category')
            ->map(fn ($items) => $items->pluck('display_order')->toArray())
            ->toArray();

        return view('payroll::deduction-types.create',
            compact('categories', 'categoryLabels', 'nextOrder', 'existingOrders'));
    }

    /**
     * Persist a new deduction type.
     *
     * New types are always manual (is_computed = false) because no formula
     * exists for them in DeductionService.  Lock them via Edit after adding
     * the formula to the service.
     */
    public function store(Request $request)
    {
        $validKeys = DeductionCategory::active()->pluck('key')->all();

        $data = $request->validate([
            'code'          => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_]+$/',
                'unique:deduction_types,code',
            ],
            'name'          => 'required|string|max:200',
            'category'      => ['required', Rule::in($validKeys)],
            'display_order' => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = DeductionType::where('category', $request->input('category'))
                        ->where('display_order', $value)
                        ->exists();
                    if ($exists) {
                        $fail("Order #{$value} is already used in this category. Choose a different number.");
                    }
                },
            ],
            'notes' => 'nullable|string|max:500',
        ]);

        $data['is_computed'] = false; // User-created types are never engine-computed
        $data['is_active']   = true;

        DeductionType::create($data);

        return redirect()->route('deduction-types.index')
            ->with('success', "Deduction type \"{$data['name']}\" created successfully.");
    }

    /** Show the edit form. */
    public function edit(DeductionType $deductionType)
    {
        $categories     = DeductionCategory::active()->ordered()->get();
        $categoryLabels = $categories->pluck('label', 'key')->all();

        // FIX: pass existingOrders (excluding current record) from PHP.
        $existingOrders = DeductionType::where('id', '!=', $deductionType->id)
            ->get()
            ->groupBy('category')
            ->map(fn ($items) => $items->pluck('display_order')->toArray())
            ->toArray();

        // Build the formula description for this type (for the preview panel).
        $formulaDescription = self::formulaDescription($deductionType->code);

        return view('payroll::deduction-types.edit',
            compact('deductionType', 'categories', 'categoryLabels',
                    'existingOrders', 'formulaDescription'));
    }

    /**
     * Update an existing deduction type.
     *
     * Enhancement #1b: `is_computed` is now accepted from the form.
     *   - Switching to manual (false) clears any active override.
     *   - Switching to auto-compute (true) re-enables the formula.
     */
    public function update(Request $request, DeductionType $deductionType)
    {
        $validKeys = DeductionCategory::active()->pluck('key')->all();

        $data = $request->validate([
            'name'            => 'required|string|max:200',
            'category'        => ['required', Rule::in($validKeys)],
            'display_order'   => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request, $deductionType) {
                    $exists = DeductionType::where('category', $request->input('category'))
                        ->where('display_order', $value)
                        ->where('id', '!=', $deductionType->id)
                        ->exists();
                    if ($exists) {
                        $fail("Order #{$value} is already used in this category. Choose a different number.");
                    }
                },
            ],
            'notes'           => 'nullable|string|max:500',
            // Enhancement #1b — allow toggling the computation mode
            'is_computed'     => 'nullable|boolean',
            // Enhancement #1 — override fields
            'override_amount' => 'nullable|numeric|min:0',
            'override_note'   => 'nullable|string|max:300',
            'clear_override'  => 'nullable|boolean',
        ]);

        // Resolve final is_computed state (checkbox: present = true, absent = false)
        $newIsComputed = (bool) ($data['is_computed'] ?? false);

        $updateData = [
            'name'        => $data['name'],
            'category'    => $data['category'],
            'display_order' => $data['display_order'],
            'notes'       => $data['notes'] ?? null,
            'is_computed' => $newIsComputed,
        ];

        // Handle override only when the type is (or remains) computed
        if ($newIsComputed) {
            if (! empty($data['clear_override'])) {
                $updateData['override_amount'] = null;
                $updateData['override_note']   = null;
            } elseif (array_key_exists('override_amount', $data) && $data['override_amount'] !== null) {
                $updateData['override_amount'] = $data['override_amount'];
                $updateData['override_note']   = $data['override_note'] ?? null;
            }
        } else {
            // Switching to manual — always clear any active override
            $updateData['override_amount'] = null;
            $updateData['override_note']   = null;
        }

        $deductionType->update($updateData);

        $modeLabel = $newIsComputed ? 'locked (auto-computed)' : 'unlocked (manual)';

        return redirect()->route('deduction-types.index')
            ->with('success', "Deduction type \"{$deductionType->name}\" updated. Mode: {$modeLabel}.");
    }

    /**
     * Toggle is_active on/off.
     */
    public function toggle(DeductionType $deductionType)
    {
        $deductionType->update(['is_active' => ! $deductionType->is_active]);
        $state = $deductionType->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "\"{$deductionType->name}\" has been {$state}.");
    }

    /**
     * Permanently delete a deduction type.
     *
     * Only allowed when the type is inactive — this prevents accidental removal
     * of types that are referenced by active payroll runs or enrollments.
     * For types with payroll history, deactivate instead of deleting.
     */
    public function destroy(DeductionType $deductionType)
    {
        if ($deductionType->is_active) {
            return back()->with('error', "Cannot delete an active deduction type. Deactivate it first.");
        }

        $name = $deductionType->name;
        $deductionType->delete();

        return redirect()->route('deduction-types.index')
            ->with('success', "Deduction type \"{$name}\" has been permanently deleted.");
    }

    /**
     * Reorder via AJAX drag-and-drop.
     * Exempt from per-category uniqueness check (batch overwrite).
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'items'         => 'required|array',
            'items.*.id'    => 'required|integer|exists:deduction_types,id',
            'items.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->input('items') as $item) {
            DeductionType::where('id', $item['id'])
                ->update(['display_order' => $item['order']]);
        }

        return response()->json(['ok' => true]);
    }

    // ── Shared helpers ─────────────────────────────────────────────────────

    public static function categoryLabels(): array
    {
        try {
            $labels = DeductionCategory::labelsMap();
            if (! empty($labels)) {
                return $labels;
            }
        } catch (\Throwable) {
            // Table doesn't exist yet (pre-migration) — fall back gracefully
        }

        return self::fallbackCategoryLabels();
    }

    public static function fallbackCategoryLabels(): array
    {
        return [
            'pagibig'    => 'PAG-IBIG / HDMF',
            'philhealth' => 'PhilHealth',
            'gsis'       => 'GSIS',
            'other_gov'  => 'Government / Tax',
            'loan'       => 'Bank Loans',
            'caress'     => 'CARESS IX',
            'misc'       => 'Miscellaneous',
        ];
    }

    /**
     * Human-readable formula description for the edit page preview panel.
     * Returns null for non-computed / unknown codes.
     */
    public static function formulaDescription(string $code): ?array
    {
        return match ($code) {
            'PAG_IBIG_1' => [
                'label'       => 'PAG-IBIG I (HDMF Mandatory)',
                'formula'     => '2% of Basic Monthly Salary, capped at ₱100/month EE share → ÷ 2 per cut-off.',
                'variables'   => ['basic_monthly'],
                'js_formula'  => 'Math.min(basic * 0.02, 100) / 2',
            ],
            'PHILHEALTH' => [
                'label'       => 'PhilHealth Mandatory Premium',
                'formula'     => '5% of Basic (floor ₱500, ceiling ₱5,000/month) → 50% EE share → ÷ 2 per cut-off.',
                'variables'   => ['basic_monthly'],
                'js_formula'  => 'Math.max(250, Math.min(basic * 0.05 * 0.5, 2500)) / 2',
            ],
            'GSIS_LIFE_RETIREMENT', 'GSIS_LIFE_RET' => [
                'label'       => 'GSIS Life & Retirement (Personal Share)',
                'formula'     => '9% of Basic Monthly Salary → ÷ 2 per cut-off.',
                'variables'   => ['basic_monthly'],
                'js_formula'  => 'basic * 0.09 / 2',
            ],
            'WITHHOLDING_TAX', 'WHT' => [
                'label'       => 'Withholding Tax (BIR TRAIN Law)',
                'formula'     => 'Annual projection method: (Accumulated Gross ÷ Cut-off No.) × 24 − mandatory deductions → BIR graduated table → ÷ 24. Varies per employee and YTD gross.',
                'variables'   => ['basic_monthly', 'pera_monthly', 'ytd_gross', 'cutoff_number'],
                'js_formula'  => null, // Too complex for a JS preview
            ],
            default => null,
        };
    }
}
