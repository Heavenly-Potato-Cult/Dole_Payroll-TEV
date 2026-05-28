<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Models\DeductionType;
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
 *   1. DeductionService::resolveDeductions()         — tier resolution
 *   2. PayrollComputationService::computeEntry()     — match() code keys
 *   3. employee_deduction_enrollments.deduction_type_id — via code lookup
 *
 * ── Three-Tier Model ─────────────────────────────────────────────────────────
 *
 *  Tier 1 — Formula-driven  (is_computed = true)
 *    Amounts calculated per-employee from salary. is_locked + override_amount
 *    turns them into a global fixed amount bypassing the formula.
 *    formula_rate_* columns allow the Payroll Officer to adjust statutory
 *    rates (PAG-IBIG, PhilHealth, GSIS) without touching code.
 *
 *  Tier 2 — Locked global  (is_computed = false, is_locked = true)
 *    default_amount applied to ALL employees. HR cannot edit per-employee.
 *    Changing default_amount in CMS takes effect on the next payroll run.
 *    Loan-category types (category IN ['loan','caress']) are EXEMPT — they
 *    are always Tier 3 even when is_locked = true.
 *
 *  Tier 3 — Per-employee  (is_computed = false, is_locked = false OR loan category)
 *    default_amount (if set) pre-fills the enrollment form. HR may override
 *    per employee. Used for loans and variable deductions.
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
        $categoryLabels = self::fallbackCategoryLabels();
        $nextOrder      = DeductionType::max('display_order') + 1;

        $existingOrders = DeductionType::all()
            ->groupBy('category')
            ->map(fn ($items) => $items->pluck('display_order')->toArray())
            ->toArray();

        $loanCategories = DeductionType::LOAN_CATEGORIES;

        return view('payroll::deduction-types.create',
            compact('categoryLabels', 'nextOrder', 'existingOrders', 'loanCategories'));
    }

    /**
     * Persist a new deduction type.
     *
     * New types are always manual (is_computed = false).
     * is_locked and default_amount may be set at creation time.
     * formula_rate_* columns are NOT exposed on the create form
     * (only computed types use them, and new types are always manual).
     */
    public function store(Request $request)
    {
        $validKeys = array_keys(self::fallbackCategoryLabels());

        $data = $request->validate([
            'code'           => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_]+$/',
                'unique:deduction_types,code',
            ],
            'name'           => 'required|string|max:200',
            'category'       => ['required', Rule::in($validKeys)],
            'display_order'  => [
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
            'notes'          => 'nullable|string|max:500',
            'default_amount' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'is_locked'      => 'nullable|boolean',
            'percentage'     => ['nullable', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
        ]);

        $data['is_computed'] = false;
        $data['is_active']   = true;
        $data['is_locked']   = (bool) ($data['is_locked'] ?? false);
        $data['default_amount'] = $data['default_amount'] ?? null;

        DeductionType::create($data);

        return redirect()->route('deduction-types.index')
            ->with('success', "Deduction type \"{$data['name']}\" created successfully.");
    }

    /** Show the edit form. */
    public function edit(DeductionType $deductionType)
    {
        $categoryLabels = self::fallbackCategoryLabels();

        $existingOrders = DeductionType::where('id', '!=', $deductionType->id)
            ->get()
            ->groupBy('category')
            ->map(fn ($items) => $items->pluck('display_order')->toArray())
            ->toArray();

        $formulaDescription = self::formulaDescription($deductionType->code);
        $loanCategories     = DeductionType::LOAN_CATEGORIES;

        return view('payroll::deduction-types.edit',
            compact('deductionType', 'categoryLabels',
                    'existingOrders', 'formulaDescription', 'loanCategories'));
    }

    /**
     * Update an existing deduction type.
     *
     * Handles all three tiers:
     *   - Tier 1 (is_computed): override_amount + is_locked for global formula bypass.
     *                           formula_rate_* columns for configurable statutory rates
     *                           (PAG-IBIG, PhilHealth, GSIS only — WHT excluded).
     *   - Tier 2 (manual, locked): default_amount saved; per-employee editing blocked.
     *   - Tier 3 (manual, unlocked): default_amount saved as pre-fill default.
     *
     * ── formula_rate_* conversion note ──────────────────────────────────────
     * The edit form accepts rate fields as percentages (e.g. "5.00" for 5 %)
     * because that is what users naturally understand. The DB stores them as
     * decimal fractions (e.g. 0.0500) because that is what DeductionService
     * multiplies directly against salaries. This method divides by 100 before
     * saving and the formulaDescription() / blade display multiplies by 100
     * when reading back, so the round-trip is transparent to the user.
     */
    public function update(Request $request, DeductionType $deductionType)
    {
        $validKeys = array_keys(self::fallbackCategoryLabels());

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

            // Global amount + lock (all types)
            'default_amount'  => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'is_locked'       => 'nullable|boolean',

            // Percentage-based deduction (manual types)
            'percentage'      => ['nullable', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],

            // Tier 1 formula override (is_computed types only)
            'is_computed'     => 'nullable|boolean',
            'override_amount' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'override_note'   => 'nullable|string|max:300',
            'clear_override'  => 'nullable|boolean',

            // ── Formula rate columns (Tier 1 / is_computed types only) ──
            // Accepted as percentages in the UI (0–100), stored as decimals (÷100).
            // PAG-IBIG: formula_rate, formula_rate_low, formula_rate_threshold, formula_monthly_cap
            // PhilHealth: formula_rate, formula_monthly_floor, formula_monthly_ceiling
            // GSIS: formula_rate
            // WHT: none — intentionally excluded
            'formula_rate'           => ['nullable', 'numeric', 'min:0', 'max:100',
                                         'regex:/^\d+(\.\d{1,2})?$/'],
            'formula_rate_low'       => ['nullable', 'numeric', 'min:0', 'max:100',
                                         'regex:/^\d+(\.\d{1,2})?$/'],
            'formula_rate_threshold' => ['nullable', 'numeric', 'min:0',
                                         'regex:/^\d+(\.\d{1,2})?$/'],
            'formula_monthly_floor'  => ['nullable', 'numeric', 'min:0',
                                         'regex:/^\d+(\.\d{1,2})?$/'],
            'formula_monthly_ceiling'=> ['nullable', 'numeric', 'min:0',
                                         'regex:/^\d+(\.\d{1,2})?$/'],
            'formula_monthly_cap'    => ['nullable', 'numeric', 'min:0',
                                         'regex:/^\d+(\.\d{1,2})?$/'],
        ]);

        // ── Additional cross-field validation for formula rates ───────────
        // Only enforce when the type is computed and this is not WHT.
        $isComputedEdit = (bool) ($data['is_computed'] ?? $deductionType->is_computed);
        $isWhtCode      = in_array($deductionType->code, ['WITHHOLDING_TAX', 'WHT']);
        $isPagibigCode  = in_array($deductionType->code, ['PAG_IBIG_1', 'PAGIBIG_1']);

        if ($isComputedEdit && !$isWhtCode) {
            // Ensure low rate does not exceed main rate (PAG-IBIG only)
            if ($isPagibigCode
                && isset($data['formula_rate'], $data['formula_rate_low'])
                && (float) $data['formula_rate_low'] > (float) $data['formula_rate']
            ) {
                return back()
                    ->withInput()
                    ->withErrors(['formula_rate_low' =>
                        'The Low-Salary Rate cannot be higher than the Main Rate.']);
            }

            // Ensure floor does not exceed ceiling (PhilHealth only)
            if ($deductionType->code === 'PHILHEALTH'
                && isset($data['formula_monthly_floor'], $data['formula_monthly_ceiling'])
                && (float) $data['formula_monthly_floor'] > (float) $data['formula_monthly_ceiling']
            ) {
                return back()
                    ->withInput()
                    ->withErrors(['formula_monthly_floor' =>
                        'The Minimum Monthly Premium cannot be higher than the Maximum Monthly Premium.']);
            }
        }

        // ── Build the update payload ──────────────────────────────────────
        $newIsComputed = (bool) ($data['is_computed'] ?? $deductionType->is_computed);
        $newIsLocked   = (bool) ($data['is_locked'] ?? false);

        $updateData = [
            'name'           => $data['name'],
            'category'       => $data['category'],
            'display_order'  => $data['display_order'],
            'notes'          => $data['notes'] ?? null,
            'is_computed'    => $newIsComputed,
            'is_locked'      => $newIsLocked,
            'default_amount' => $data['default_amount'] ?? null,
            'percentage'     => $data['percentage'] ?? null,
        ];

        // ── Tier 1: formula override handling ────────────────────────────
        if ($newIsComputed) {
            if (! empty($data['clear_override'])) {
                $updateData['override_amount'] = null;
                $updateData['override_note']   = null;
            } elseif (array_key_exists('override_amount', $data) && $data['override_amount'] !== null) {
                $updateData['override_amount'] = $data['override_amount'];
                $updateData['override_note']   = $data['override_note'] ?? null;
            }

            // ── formula_rate_* columns ────────────────────────────────────
            // Only persist when the type is computed AND not WHT.
            // Rates submitted as percentages (e.g. 2.00) → stored as decimals (0.0200).
            // Columns irrelevant to a given code are left unchanged (not cleared)
            // so that an admin changing just the main rate doesn't accidentally
            // null out the threshold or cap values.
            if (! $isWhtCode) {
                // formula_rate applies to PAG-IBIG, PhilHealth, and GSIS
                if (array_key_exists('formula_rate', $data)) {
                    $updateData['formula_rate'] = $data['formula_rate'] !== null
                        ? round((float) $data['formula_rate'] / 100, 4)
                        : null;
                }

                // PAG-IBIG-only columns
                if ($isPagibigCode) {
                    if (array_key_exists('formula_rate_low', $data)) {
                        $updateData['formula_rate_low'] = $data['formula_rate_low'] !== null
                            ? round((float) $data['formula_rate_low'] / 100, 4)
                            : null;
                    }
                    if (array_key_exists('formula_rate_threshold', $data)) {
                        $updateData['formula_rate_threshold'] = $data['formula_rate_threshold'] ?? null;
                    }
                    if (array_key_exists('formula_monthly_cap', $data)) {
                        $updateData['formula_monthly_cap'] = $data['formula_monthly_cap'] ?? null;
                    }
                }

                // PhilHealth-only columns
                if ($deductionType->code === 'PHILHEALTH') {
                    if (array_key_exists('formula_monthly_floor', $data)) {
                        $updateData['formula_monthly_floor'] = $data['formula_monthly_floor'] ?? null;
                    }
                    if (array_key_exists('formula_monthly_ceiling', $data)) {
                        $updateData['formula_monthly_ceiling'] = $data['formula_monthly_ceiling'] ?? null;
                    }
                }
            }
        } else {
            // Switching to manual — clear any formula override
            $updateData['override_amount'] = null;
            $updateData['override_note']   = null;
            // formula_rate_* columns are intentionally NOT cleared when switching
            // a type to manual: the data is harmless there and avoids accidental
            // data loss if someone toggles is_computed back and forth.
        }

        $deductionType->update($updateData);

        $lockLabel = $newIsLocked ? 'Locked (global amount)' : 'Unlocked (per-employee)';
        $modeLabel = $newIsComputed ? 'Auto-computed' : $lockLabel;

        return redirect()->route('deduction-types.index')
            ->with('success', "Deduction type \"{$deductionType->name}\" updated. Mode: {$modeLabel}.");
    }

    /** Toggle is_active on/off. */
    public function toggle(DeductionType $deductionType)
    {
        $deductionType->update(['is_active' => ! $deductionType->is_active]);
        $state = $deductionType->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "\"{$deductionType->name}\" has been {$state}.");
    }

    /**
     * Permanently delete a deduction type.
     * Only allowed when inactive.
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

    /** Reorder via AJAX drag-and-drop. */
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
        return self::fallbackCategoryLabels();
    }

    public static function fallbackCategoryLabels(): array
    {
        return [
            'pagibig'    => 'PAG-IBIG / HDMF',
            'philhealth' => 'PhilHealth',
            'gsis'       => 'GSIS',
            'other_gov'  => 'Government / Tax',
            'other'      => 'Other Deductions',
            'loan'       => 'Bank Loans',
            'caress'     => 'CARESS IX',
            'misc'       => 'Miscellaneous',
        ];
    }

    /**
     * Human-readable formula description for the edit page preview panel.
     * Returns null for non-computed / unknown codes.
     *
     * The formula strings reference the DB-configurable rate columns so
     * users understand which numbers in the description they can change.
     */
    public static function formulaDescription(string $code): ?array
    {
        return match ($code) {
            'PAG_IBIG_1', 'PAGIBIG_1' => [
                'label'      => 'PAG-IBIG I (HDMF Mandatory)',
                'formula'    => 'Apply Main Rate (default 2%) if salary > Salary Threshold (default ₱1,500), '
                              . 'or Low-Salary Rate (default 1%) if salary ≤ threshold. '
                              . 'Cap the monthly employee share at Monthly Cap (default ₱100). '
                              . 'Divide by 2 for the cut-off deduction.',
                'variables'  => ['basic_monthly'],
                'js_formula' => 'Math.min(basic * (basic <= threshold ? rateLow : rate), cap) / 2',
            ],
            'PHILHEALTH' => [
                'label'      => 'PhilHealth Mandatory Premium',
                'formula'    => 'Multiply basic salary by Premium Rate (default 5%). '
                              . 'Clamp the result between Minimum Monthly Premium (default ₱500) '
                              . 'and Maximum Monthly Premium (default ₱5,000). '
                              . 'Employee pays 50% of the clamped total. Divide by 2 for the cut-off.',
                'variables'  => ['basic_monthly'],
                'js_formula' => 'Math.max(floor, Math.min(basic * rate, ceiling)) * 0.5 / 2',
            ],
            'GSIS_LIFE_RETIREMENT', 'GSIS_LIFE_RET' => [
                'label'      => 'GSIS Life & Retirement (Personal Share)',
                'formula'    => 'Multiply basic salary by Personal Share Rate (default 9%). '
                              . 'Prorate for incomplete months if days worked < total days. '
                              . 'Divide by 2 for the cut-off.',
                'variables'  => ['basic_monthly'],
                'js_formula' => 'basic * rate / 2',
            ],
            'WITHHOLDING_TAX', 'WHT' => [
                'label'      => 'Withholding Tax (BIR TRAIN Law)',
                'formula'    => 'Annual projection method: accumulated gross ÷ cut-off number × 24, '
                              . 'minus annual mandatory deductions (GSIS 9%, PhilHealth, HDMF), '
                              . 'then apply BIR graduated tax table, divide by 24. '
                              . 'Rates are hardcoded — see note on the edit page.',
                'variables'  => ['basic_monthly', 'pera_monthly', 'ytd_gross', 'cutoff_number'],
                'js_formula' => null,
            ],
            default => null,
        };
    }
}
