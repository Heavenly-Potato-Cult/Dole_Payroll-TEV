<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Models\DeductionType;
use Modules\Payroll\Models\DeductionTypeCategory;
use App\SharedKernel\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

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
 *
 * ── Category strategy ────────────────────────────────────────────────────────
 * categoryLabels() now loads from deduction_type_categories, falling back to
 * the hardcoded array when the table is unavailable (e.g. before migration).
 * On store/update, BOTH `category` (the runtime string) and
 * `deduction_type_category_id` (the FK) are written so the two columns stay
 * in sync.
 * ─────────────────────────────────────────────────────────────────────────────
 */
class DeductionTypeController extends Controller
{
    /** Display the full list of deduction types, grouped by category. */
    public function index()
    {
        $types = DeductionType::with('deductionTypeCategory')
            ->withCount('assignedEmployees')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $grouped        = $types->groupBy('category');
        $categoryLabels = self::categoryLabels();

        return view('payroll::deduction-types.index', compact('grouped', 'categoryLabels'));
    }

    /** Show the create form. */
    public function create()
    {
        $categories  = self::loadActiveCategories();
        $categoryLabels = $categories->pluck('name', 'code')->toArray()
                          ?: self::fallbackCategoryLabels();

        $nextOrder = DeductionType::max('display_order') + 1;

        $existingOrders = DeductionType::all()
            ->groupBy('category')
            ->map(fn ($items) => $items->pluck('display_order')->toArray())
            ->toArray();

        $loanCategories = DeductionType::LOAN_CATEGORIES;
        $employees      = self::loadAssignableEmployees();

        return view('payroll::deduction-types.create',
            compact('categories', 'categoryLabels', 'nextOrder', 'existingOrders', 'loanCategories', 'employees'));
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
        $validCodes = self::loadActiveCategories()->pluck('code')->toArray()
                      ?: array_keys(self::fallbackCategoryLabels());

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_]+$/',
                'unique:deduction_types,code',
            ],
            'name'                       => 'required|string|max:200',
            'deduction_type_category_id' => ['required', 'integer', 'exists:deduction_type_categories,id'],
            'display_order' => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = DeductionType::where('category', $this->categoryCodeFromId($request->input('deduction_type_category_id')))
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
            // Employee assignment scope
            'assignment_scope' => ['nullable', Rule::in(['all', 'specific'])],
            'employee_ids'      => ['nullable', 'array'],
            'employee_ids.*'    => ['integer', 'exists:employees,id'],
        ]);

        // Resolve the category string from the FK
        $data['category'] = $this->categoryCodeFromId($data['deduction_type_category_id']);

        $data['is_computed']      = false;
        $data['is_active']        = true;
        $data['is_locked']        = (bool) ($data['is_locked'] ?? false);
        $data['default_amount']   = $data['default_amount'] ?? null;
        $data['assignment_scope'] = $data['assignment_scope'] ?? 'all';

        $employeeIds = $data['employee_ids'] ?? [];
        unset($data['employee_ids']);

        $deductionType = DeductionType::create($data);

        // Only sync the pivot when scope is 'specific' — see
        // DeductionType docblock: pivot rows are an inclusion whitelist
        // that's simply ignored (not deleted) when scope = 'all'.
        if ($deductionType->assignment_scope === 'specific') {
            $deductionType->assignedEmployees()->sync($employeeIds);
        }

        return redirect()->route('deduction-types.index')
            ->with('success', "Deduction type \"{$data['name']}\" created successfully.");
    }

    /** Show the edit form. */
    public function edit(DeductionType $deductionType)
    {
        $categories     = self::loadActiveCategories();
        $categoryLabels = $categories->pluck('name', 'code')->toArray()
                          ?: self::fallbackCategoryLabels();

        $existingOrders = DeductionType::where('id', '!=', $deductionType->id)
            ->get()
            ->groupBy('category')
            ->map(fn ($items) => $items->pluck('display_order')->toArray())
            ->toArray();

        $formulaDescription = self::formulaDescription($deductionType->code);
        $loanCategories     = DeductionType::LOAN_CATEGORIES;
        $employees          = self::loadAssignableEmployees();
        $assignedEmployeeIds = $deductionType->assignedEmployees()->pluck('employees.id')->all();

        return view('payroll::deduction-types.edit',
            compact('deductionType', 'categories', 'categoryLabels',
                    'existingOrders', 'formulaDescription', 'loanCategories',
                    'employees', 'assignedEmployeeIds'));
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
        $data = $request->validate([
            'name'                       => 'required|string|max:200',
            'deduction_type_category_id' => ['required', 'integer', 'exists:deduction_type_categories,id'],
            'display_order' => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($request, $deductionType) {
                    $catCode = $this->categoryCodeFromId($request->input('deduction_type_category_id'));
                    $exists = DeductionType::where('category', $catCode)
                        ->where('display_order', $value)
                        ->where('id', '!=', $deductionType->id)
                        ->exists();
                    if ($exists) {
                        $fail("Order #{$value} is already used in this category. Choose a different number.");
                    }
                },
            ],
            'is_active'      => 'nullable|boolean',
            'notes'          => 'nullable|string|max:500',
            'default_amount' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'is_locked'      => 'nullable|boolean',
            'percentage'     => ['nullable', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
            // Tier 1 formula override fields (validated but only applied for is_computed types)
            'override_amount' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'override_note'   => 'nullable|string|max:500',
            'clear_override'  => 'nullable|boolean',
            // formula_rate_* (submitted as percentages, stored as decimals)
            'formula_rate'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'formula_rate_low'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'formula_rate_threshold' => ['nullable', 'numeric', 'min:0'],
            'formula_monthly_floor'  => ['nullable', 'numeric', 'min:0'],
            'formula_monthly_ceiling'=> ['nullable', 'numeric', 'min:0'],
            'formula_monthly_cap'    => ['nullable', 'numeric', 'min:0'],
            // WHT min/max override
            'min_override_amount' => ['nullable', 'numeric', 'min:0'],
            'max_override_amount' => ['nullable', 'numeric', 'min:0'],
            // Employee assignment scope
            'assignment_scope' => ['nullable', Rule::in(['all', 'specific'])],
            'employee_ids'      => ['nullable', 'array'],
            'employee_ids.*'    => ['integer', 'exists:employees,id'],
        ]);

        // Resolve category string from FK and keep in sync
        $categoryCode = $this->categoryCodeFromId($data['deduction_type_category_id']);

        $updateData = [
            'name'                       => $data['name'],
            'category'                   => $categoryCode,
            'deduction_type_category_id' => $data['deduction_type_category_id'],
            'display_order'              => $data['display_order'],
            'is_active'                  => (bool) ($data['is_active'] ?? $deductionType->is_active),
            'notes'                      => $data['notes'] ?? null,
            'default_amount'             => $data['default_amount'] ?? null,
            'is_locked'                  => (bool) ($data['is_locked'] ?? false),
            'percentage'                 => $data['percentage'] ?? null,
            'assignment_scope'           => $data['assignment_scope'] ?? 'all',
        ];

        // WHT min/max overrides (available for all non-computed types)
        if (array_key_exists('min_override_amount', $data)) {
            $updateData['min_override_amount'] = $data['min_override_amount'];
        }
        if (array_key_exists('max_override_amount', $data)) {
            $updateData['max_override_amount'] = $data['max_override_amount'];
        }

        $newIsComputed = $deductionType->is_computed; // is_computed is immutable via UI
        $newIsLocked   = $updateData['is_locked'];

        $isPagibigCode = in_array($deductionType->code, ['PAG_IBIG_1', 'PAGIBIG_1']);
        $isWhtCode     = in_array($deductionType->code, ['WITHHOLDING_TAX', 'WHT']);

        if ($newIsComputed) {
            // ── Tier 1: handle formula override ──────────────────────────
            if (! empty($data['clear_override'])) {
                $updateData['override_amount'] = null;
                $updateData['override_note']   = null;
            } elseif (array_key_exists('override_amount', $data) && $data['override_amount'] !== null) {
                $updateData['override_amount'] = $data['override_amount'];
                $updateData['override_note']   = $data['override_note'] ?? null;
            }

            // ── formula_rate_* columns ────────────────────────────────────
            if (! $isWhtCode) {
                if (array_key_exists('formula_rate', $data)) {
                    $updateData['formula_rate'] = $data['formula_rate'] !== null
                        ? round((float) $data['formula_rate'] / 100, 4)
                        : null;
                }
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
        }

        $deductionType->update($updateData);

        // Only sync the pivot when scope is 'specific'. If scope = 'all',
        // the submitted employee list is deliberately ignored and existing
        // pivot rows are left untouched (see DeductionType docblock) — so
        // toggling back to 'all' and later back to 'specific' doesn't lose
        // a previously curated list.
        $overlapWarning = null;
        if ($updateData['assignment_scope'] === 'specific') {
            $employeeIds = $data['employee_ids'] ?? [];
            $deductionType->assignedEmployees()->sync($employeeIds);
            $overlapWarning = $this->checkAssignmentOverlap($deductionType, $employeeIds);
        }

        $lockLabel = $newIsLocked ? 'Locked (global amount)' : 'Unlocked (per-employee)';
        $modeLabel = $newIsComputed ? 'Auto-computed' : $lockLabel;

        $message = "Deduction type \"{$deductionType->name}\" updated. Mode: {$modeLabel}.";

        $redirect = redirect()->route('deduction-types.index')->with('success', $message);

        if ($overlapWarning) {
            $redirect->with('warning', $overlapWarning);
        }

        return $redirect;
    }

    /**
     * Non-blocking safeguard for the GSIS/Pag-IBIG duplicate-type scenario:
     * if another ACTIVE type sharing the same category has an overlapping
     * assigned-employee set, surface a warning (doesn't block the save).
     * Only checked against other 'specific'-scope types, since 'all'-scope
     * types by definition include everyone and would always "overlap".
     */
    protected function checkAssignmentOverlap(DeductionType $deductionType, array $employeeIds): ?string
    {
        if (empty($employeeIds)) {
            return null;
        }

        $siblings = DeductionType::where('category', $deductionType->category)
            ->where('id', '!=', $deductionType->id)
            ->where('is_active', true)
            ->where('assignment_scope', 'specific')
            ->with(['assignedEmployees' => function ($q) use ($employeeIds) {
                $q->whereIn('employees.id', $employeeIds);
            }])
            ->get();

        foreach ($siblings as $sibling) {
            $overlapCount = $sibling->assignedEmployees->count();
            if ($overlapCount > 0) {
                return "{$overlapCount} employee(s) are assigned to both \"{$deductionType->name}\" "
                     . "and \"{$sibling->name}\" — confirm this is intentional.";
            }
        }

        return null;
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
     *
     * Guards:
     *   1. Must be inactive — active types cannot be deleted.
     *   2. Must have no payroll history — types referenced in payroll_deductions
     *      must be kept for audit trail integrity.
     *   3. Must have no active employee enrollments — belt-and-suspenders check.
     */
    public function destroy(DeductionType $deductionType)
    {
        if ($deductionType->is_active) {
            return back()->with('error', "Cannot delete \"{$deductionType->name}\" — deactivate it first.");
        }

$payrollUsage = DB::table('payroll_deductions')
    ->where('deduction_type_id', $deductionType->id)
    ->count();

        if ($payrollUsage > 0) {
            return back()->with('error', "Cannot delete \"{$deductionType->name}\" — it appears in {$payrollUsage} payroll record(s) and must be kept for audit purposes.");
        }

$enrollmentUsage = DB::table('employee_deduction_enrollments')
    ->where('deduction_type_id', $deductionType->id)
    ->count();

        if ($enrollmentUsage > 0) {
            return back()->with('error', "Cannot delete \"{$deductionType->name}\" — it has {$enrollmentUsage} employee enrollment record(s) referencing it.");
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

    /**
     * Returns active employees for the assignment picker, with division
     * eager-loaded so the picker can offer a division filter.
     *
     * Employees are considered assignable when status = 'active' and
     * is_excluded = false — there is no is_active column on employees
     * (that column exists on divisions, not employees).
     *
     * Returns an empty collection if the table/relation is unavailable.
     */
    public static function loadAssignableEmployees()
    {
        try {
            return Employee::with('division')
                ->where('status', 'active')
                ->where('is_excluded', false)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * Returns an active-ordered collection of DeductionTypeCategory models.
     * Returns an empty collection if the table does not exist yet (pre-migration).
     */
    public static function loadActiveCategories()
    {
        try {
            return DeductionTypeCategory::active()->ordered()->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * Returns ['code' => 'Label'] map for use in views and validation.
     * Prefers live DB data; falls back to hardcoded array.
     */
    public static function categoryLabels(): array
    {
        $dbLabels = self::loadActiveCategories()->pluck('name', 'code')->toArray();
        return $dbLabels ?: self::fallbackCategoryLabels();
    }

    /**
     * Hardcoded fallback — used before migration runs or when the DB is
     * unavailable.  Also used by DeductionService / PayrollComputationService
     * indirectly (they match on the code string, not this array).
     */
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
     * Looks up the category `code` string for a given category ID.
     * Falls back to 'misc' if the ID is not found.
     */
    protected function categoryCodeFromId(mixed $id): string
    {
        if (! $id) {
            return 'misc';
        }
        try {
            $cat = DeductionTypeCategory::withTrashed()->find((int) $id);
            return $cat?->code ?? 'misc';
        } catch (\Throwable) {
            return 'misc';
        }
    }

    /**
     * Human-readable formula description for the edit page preview panel.
     * Returns null for non-computed / unknown codes.
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
