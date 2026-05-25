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
 *   1. DeductionService::resolveDeductions()         — tier resolution
 *   2. PayrollComputationService::computeEntry()     — match() code keys
 *   3. employee_deduction_enrollments.deduction_type_id — via code lookup
 *
 * ── Three-Tier Model ─────────────────────────────────────────────────────────
 *
 *  Tier 1 — Formula-driven  (is_computed = true)
 *    Amounts calculated per-employee from salary. is_locked + override_amount
 *    turns them into a global fixed amount bypassing the formula.
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
        $categories     = DeductionCategory::active()->ordered()->get();
        $categoryLabels = $categories->pluck('label', 'key')->all();
        $nextOrder      = DeductionType::max('display_order') + 1;

        $existingOrders = DeductionType::all()
            ->groupBy('category')
            ->map(fn ($items) => $items->pluck('display_order')->toArray())
            ->toArray();

        // Loan categories — used by JS to hide the lock toggle for loan types
        $loanCategories = DeductionType::LOAN_CATEGORIES;

        return view('payroll::deduction-types.create',
            compact('categories', 'categoryLabels', 'nextOrder', 'existingOrders', 'loanCategories'));
    }

    /**
     * Persist a new deduction type.
     *
     * New types are always manual (is_computed = false).
     * is_locked and default_amount may be set at creation time.
     */
    public function store(Request $request)
    {
        $validKeys = DeductionCategory::active()->pluck('key')->all();

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
            'default_amount' => 'nullable|numeric|min:0',
            'is_locked'      => 'nullable|boolean',
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
        $categories     = DeductionCategory::active()->ordered()->get();
        $categoryLabels = $categories->pluck('label', 'key')->all();

        $existingOrders = DeductionType::where('id', '!=', $deductionType->id)
            ->get()
            ->groupBy('category')
            ->map(fn ($items) => $items->pluck('display_order')->toArray())
            ->toArray();

        $formulaDescription = self::formulaDescription($deductionType->code);
        $loanCategories     = DeductionType::LOAN_CATEGORIES;

        return view('payroll::deduction-types.edit',
            compact('deductionType', 'categories', 'categoryLabels',
                    'existingOrders', 'formulaDescription', 'loanCategories'));
    }

    /**
     * Update an existing deduction type.
     *
     * Handles all three tiers:
     *   - Tier 1 (is_computed): override_amount + is_locked for global formula bypass.
     *   - Tier 2 (manual, locked): default_amount saved; per-employee editing blocked.
     *   - Tier 3 (manual, unlocked): default_amount saved as pre-fill default.
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
            // Global amount + lock
            'default_amount'  => 'nullable|numeric|min:0',
            'is_locked'       => 'nullable|boolean',
            // Tier 1 formula override (is_computed types only)
            'is_computed'     => 'nullable|boolean',
            'override_amount' => 'nullable|numeric|min:0',
            'override_note'   => 'nullable|string|max:300',
            'clear_override'  => 'nullable|boolean',
        ]);

        $newIsComputed = (bool) ($data['is_computed'] ?? $deductionType->is_computed);
        $newIsLocked   = (bool) ($data['is_locked'] ?? false);

        $updateData = [
            'name'           => $data['name'],
            'category'       => $data['category'],
            'display_order'  => $data['display_order'],
            'notes'          => $data['notes'] ?? null,
            'is_computed'    => $newIsComputed,
            'is_locked'      => $newIsLocked,
            'default_amount' => isset($data['default_amount']) ? $data['default_amount'] : null,
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
        } else {
            // Switching to manual — clear any formula override
            $updateData['override_amount'] = null;
            $updateData['override_note']   = null;
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
            'PAG_IBIG_1', 'PAGIBIG_1' => [
                'label'       => 'PAG-IBIG I (HDMF Mandatory)',
                'formula'     => '2% of Basic Monthly Salary (1% if basic ≤ ₱1,500), capped at ₱100/month EE share → ÷ 2 per cut-off.',
                'variables'   => ['basic_monthly'],
                'js_formula'  => 'Math.min(basic * (basic <= 1500 ? 0.01 : 0.02), 100) / 2',
            ],
            'PHILHEALTH' => [
                'label'       => 'PhilHealth Mandatory Premium',
                'formula'     => '5% of Basic (floor ₱500, ceiling ₱5,000/month) → 50% EE share → ÷ 2 per cut-off.',
                'variables'   => ['basic_monthly'],
                'js_formula'  => 'Math.max(125, Math.min(basic * 0.05 * 0.5, 2500)) / 2',
            ],
            'GSIS_LIFE_RETIREMENT', 'GSIS_LIFE_RET' => [
                'label'       => 'GSIS Life & Retirement (Personal Share)',
                'formula'     => '9% of Basic Monthly Salary → ÷ 2 per cut-off.',
                'variables'   => ['basic_monthly'],
                'js_formula'  => 'basic * 0.09 / 2',
            ],
            'WITHHOLDING_TAX', 'WHT' => [
                'label'       => 'Withholding Tax (BIR TRAIN Law)',
                'formula'     => 'Annual projection method: accumulated gross ÷ cut-off no. × 24 minus annual mandatory deductions → BIR graduated table → ÷ 24. Varies per employee and YTD gross.',
                'variables'   => ['basic_monthly', 'pera_monthly', 'ytd_gross', 'cutoff_number'],
                'js_formula'  => null,
            ],
            default => null,
        };
    }
}
