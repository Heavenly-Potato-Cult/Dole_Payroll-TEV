@extends('layouts.app')

@section('title', 'Edit — ' . $deductionType->name)
@section('page-title', 'Deduction Types')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Deduction Type</h1>
        <p>
            <span style="font-family:monospace;background:var(--bg);border:1px solid var(--border);padding:1px 8px;border-radius:4px;font-size:0.85rem;">{{ $deductionType->code }}</span>
            &nbsp;<span style="font-size:0.72rem;color:var(--text-light);">🔒 Code is permanent</span>
        </p>
    </div>
    <div>
        <a href="{{ route('deduction-types.index') }}" class="btn btn-outline">← Back to List</a>
    </div>
</div>

<div style="max-width:700px;">

    {{-- ══════════════════════════════════════════════════════════════════
         COMPUTED TYPES ONLY — Formula preview, rate settings, calculator
         ══════════════════════════════════════════════════════════════════ --}}
    @if ($deductionType->is_computed)

        {{-- ── Formula description ─────────────────────────────────────── --}}
        @if ($formulaDescription)
        <div class="card" style="border-left:4px solid #1e40af;margin-bottom:20px;">
            <div class="card-header" style="background:#eff6ff;">
                <h3 style="color:#1e40af;">⚙️ Auto-Computed Formula</h3>
            </div>
            <div class="card-body" style="font-size:0.85rem;">
                <div style="margin-bottom:8px;">
                    <strong>{{ $formulaDescription['label'] }}</strong>
                </div>
                <div style="background:#f0f9ff;padding:10px 14px;border-radius:6px;font-family:monospace;font-size:0.82rem;color:#1e40af;margin-bottom:10px;">
                    {{ $formulaDescription['formula'] }}
                </div>
                <div style="color:var(--text-mid);font-size:0.80rem;">
                    This deduction is calculated <strong>per employee</strong> based on their basic salary.
                    You can set a <strong>Global Override Amount</strong> below to bypass the formula and
                    apply a fixed amount to all employees instead.
                </div>
            </div>
        </div>
        @endif

        {{-- ── Formula Rate Settings ────────────────────────────────────── --}}
        <div class="card" style="border-left:4px solid #059669;margin-bottom:20px;">
            <div class="card-header" style="background:#ecfdf5;">
                <h3 style="color:#065f46;">📐 Formula Rate Settings</h3>
            </div>
            <div class="card-body">

                @php
                    $code         = $deductionType->code;
                    $isPagibig    = in_array($code, ['PAG_IBIG_1', 'PAGIBIG_1']);
                    $isPhilhealth = $code === 'PHILHEALTH';
                    $isGsis       = in_array($code, ['GSIS_LIFE_RETIREMENT', 'GSIS_LIFE_RET']);
                    $isWht        = in_array($code, ['WITHHOLDING_TAX', 'WHT']);
                @endphp

                {{-- WHT — developer-only notice --}}
                @if ($isWht)
                <div style="padding:14px 16px;background:#fef9c3;border:1px solid #fbbf24;border-radius:8px;font-size:0.85rem;color:#78350f;">
                    <div style="font-weight:700;margin-bottom:6px;">⚠ Developer-only configuration</div>
                    Withholding Tax uses the BIR TRAIN Law graduated tax table — six linked brackets
                    where changing one number requires adjusting all the others consistently.
                    Exposing these in a form risks silent miscalculations that would affect
                    <strong>every employee's tax deduction</strong>.
                    <br><br>
                    To update the tax table (e.g. for new TRAIN Law amendments), please ask a
                    developer to update <code>DeductionService::birGraduatedTax()</code> and
                    re-test the affected salary ranges before deploying.
                </div>

                {{-- PAG-IBIG rate fields --}}
                @elseif ($isPagibig)
                <div style="font-size:0.82rem;color:var(--text-mid);margin-bottom:18px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;">
                    These are the contribution rates used to compute each employee's PAG-IBIG I deduction.
                    The system automatically applies the <strong>Low-Salary Rate</strong> when basic pay
                    is at or below the <strong>Salary Threshold</strong>, and the <strong>Main Rate</strong>
                    for all others. The <strong>Monthly Cap</strong> limits the maximum EE share per month
                    before dividing by 2 for the cut-off.
                    <br><br>
                    Current statutory values: <strong>2%</strong> (main) · <strong>1%</strong> (low) ·
                    threshold ₱1,500 · cap ₱100/month.
                    Update these only when HDMF issues a new circular.
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div>
                        <label for="formula_rate" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                            Main Rate (%)
                        </label>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <input type="number" id="formula_rate" name="formula_rate"
                                   value="{{ old('formula_rate', $deductionType->formula_rate !== null ? number_format((float)$deductionType->formula_rate * 100, 2, '.', '') : '') }}"
                                   min="0" max="100" step="0.01" placeholder="2.00" style="max-width:110px;">
                            <span style="font-size:0.82rem;color:var(--text-mid);">%</span>
                        </div>
                        @error('formula_rate')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                        <div style="font-size:0.72rem;color:var(--text-light);margin-top:3px;">Applied when salary &gt; threshold.<br>Statutory: <strong>2.00%</strong></div>
                    </div>
                    <div>
                        <label for="formula_rate_low" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                            Low-Salary Rate (%)
                        </label>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <input type="number" id="formula_rate_low" name="formula_rate_low"
                                   value="{{ old('formula_rate_low', $deductionType->formula_rate_low !== null ? number_format((float)$deductionType->formula_rate_low * 100, 2, '.', '') : '') }}"
                                   min="0" max="100" step="0.01" placeholder="1.00" style="max-width:110px;">
                            <span style="font-size:0.82rem;color:var(--text-mid);">%</span>
                        </div>
                        @error('formula_rate_low')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                        <div style="font-size:0.72rem;color:var(--text-light);margin-top:3px;">Applied when salary ≤ threshold.<br>Statutory: <strong>1.00%</strong></div>
                    </div>
                    <div>
                        <label for="formula_rate_threshold" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                            Salary Threshold (₱/month)
                        </label>
                        <input type="number" id="formula_rate_threshold" name="formula_rate_threshold"
                               value="{{ old('formula_rate_threshold', $deductionType->formula_rate_threshold) }}"
                               min="0" step="0.01" placeholder="1500.00" style="max-width:140px;">
                        @error('formula_rate_threshold')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                        <div style="font-size:0.72rem;color:var(--text-light);margin-top:3px;">Low rate applies at or below this.<br>Statutory: <strong>₱1,500</strong></div>
                    </div>
                    <div>
                        <label for="formula_monthly_cap" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                            Monthly Cap (₱)
                        </label>
                        <input type="number" id="formula_monthly_cap" name="formula_monthly_cap"
                               value="{{ old('formula_monthly_cap', $deductionType->formula_monthly_cap) }}"
                               min="0" step="0.01" placeholder="100.00" style="max-width:140px;">
                        @error('formula_monthly_cap')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                        <div style="font-size:0.72rem;color:var(--text-light);margin-top:3px;">Max monthly EE share before ÷2.<br>Statutory: <strong>₱100</strong></div>
                    </div>
                </div>

                {{-- PhilHealth rate fields --}}
                @elseif ($isPhilhealth)
                <div style="font-size:0.82rem;color:var(--text-mid);margin-bottom:18px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;">
                    PhilHealth premium = basic salary × Premium Rate, clamped between the monthly
                    floor and ceiling. The employee pays 50% of the total; divide by 2 for the cut-off.
                    <br><br>
                    Statutory: <strong>5%</strong> rate · floor ₱500 · ceiling ₱5,000/month.
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div>
                        <label for="formula_rate" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                            Premium Rate (%)
                        </label>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <input type="number" id="formula_rate" name="formula_rate"
                                   value="{{ old('formula_rate', $deductionType->formula_rate !== null ? number_format((float)$deductionType->formula_rate * 100, 2, '.', '') : '') }}"
                                   min="0" max="100" step="0.01" placeholder="5.00" style="max-width:110px;">
                            <span style="font-size:0.82rem;color:var(--text-mid);">%</span>
                        </div>
                        @error('formula_rate')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                        <div style="font-size:0.72rem;color:var(--text-light);margin-top:3px;">Statutory: <strong>5.00%</strong></div>
                    </div>
                    <div>
                        <label for="formula_monthly_floor" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                            Monthly Floor (₱)
                        </label>
                        <input type="number" id="formula_monthly_floor" name="formula_monthly_floor"
                               value="{{ old('formula_monthly_floor', $deductionType->formula_monthly_floor) }}"
                               min="0" step="0.01" placeholder="500.00" style="max-width:120px;">
                        @error('formula_monthly_floor')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                        <div style="font-size:0.72rem;color:var(--text-light);margin-top:3px;">Statutory: <strong>₱500</strong></div>
                    </div>
                    <div>
                        <label for="formula_monthly_ceiling" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                            Monthly Ceiling (₱)
                        </label>
                        <input type="number" id="formula_monthly_ceiling" name="formula_monthly_ceiling"
                               value="{{ old('formula_monthly_ceiling', $deductionType->formula_monthly_ceiling) }}"
                               min="0" step="0.01" placeholder="5000.00" style="max-width:120px;">
                        @error('formula_monthly_ceiling')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                        <div style="font-size:0.72rem;color:var(--text-light);margin-top:3px;">Statutory: <strong>₱5,000</strong></div>
                    </div>
                </div>

                {{-- GSIS rate field --}}
                @elseif ($isGsis)
                <div style="font-size:0.82rem;color:var(--text-mid);margin-bottom:18px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;">
                    GSIS personal share = basic salary × Personal Share Rate, prorated for incomplete
                    months, divided by 2 for the cut-off.
                    <br><br>
                    Statutory: <strong>9%</strong>.
                </div>
                <div style="margin-bottom:16px;">
                    <label for="formula_rate" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Personal Share Rate (%)
                    </label>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <input type="number" id="formula_rate" name="formula_rate"
                               value="{{ old('formula_rate', $deductionType->formula_rate !== null ? number_format((float)$deductionType->formula_rate * 100, 2, '.', '') : '') }}"
                               min="0" max="100" step="0.01" placeholder="9.00" style="max-width:110px;">
                        <span style="font-size:0.82rem;color:var(--text-mid);">%</span>
                    </div>
                    @error('formula_rate')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:3px;">Statutory: <strong>9.00%</strong></div>
                </div>
                @endif

            </div>
        </div>

        {{-- ── Live Formula Calculator (PAG-IBIG, PhilHealth, GSIS only) ── --}}
        @if (!$isWht)
        <div class="card" style="border-left:4px solid #7c3aed;margin-bottom:20px;">
            <div class="card-header" style="background:#f5f3ff;">
                <h3 style="color:#5b21b6;">🧮 Live Formula Preview</h3>
            </div>
            <div class="card-body">
                <div style="font-size:0.82rem;color:var(--text-mid);margin-bottom:16px;">
                    Enter a sample basic salary to preview the deduction at the <strong>currently
                    entered</strong> rates above. Updates live as you change any rate field — no need
                    to save first.
                </div>

                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
                    <label style="font-size:0.78rem;font-weight:700;color:var(--text-mid);white-space:nowrap;">
                        Sample Basic Salary (₱/month)
                    </label>
                    <input type="number" id="previewSalary" value="30000" min="0" step="500"
                           style="max-width:180px;">
                </div>

                {{-- Result panel --}}
                <div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;padding:16px 20px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#7c3aed;margin-bottom:4px;">
                                Monthly EE Share
                            </div>
                            <div style="font-size:1.4rem;font-weight:700;color:#3b0764;" id="previewMonthly">—</div>
                        </div>
                        <div>
                            <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#7c3aed;margin-bottom:4px;">
                                Per Cut-off Deduction
                            </div>
                            <div style="font-size:1.4rem;font-weight:700;color:#3b0764;" id="previewCutoff">—</div>
                        </div>
                    </div>
                    <div style="margin-top:12px;padding-top:10px;border-top:1px solid #ddd6fe;font-size:0.75rem;color:#6d28d9;font-family:monospace;" id="previewBreakdown"></div>
                </div>

                {{-- Salary bracket table for PAG-IBIG --}}
                @if ($isPagibig)
                <div style="margin-top:14px;">
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:8px;">
                        Quick Reference — Common Salary Brackets
                    </div>
                    <table style="width:100%;font-size:0.78rem;border-collapse:collapse;" id="pagibigTable">
                        <thead>
                            <tr style="background:var(--bg);">
                                <th style="padding:6px 10px;text-align:left;border-bottom:1px solid #e2e8f0;color:var(--text-mid);">Basic Salary</th>
                                <th style="padding:6px 10px;text-align:right;border-bottom:1px solid #e2e8f0;color:var(--text-mid);">Rate Applied</th>
                                <th style="padding:6px 10px;text-align:right;border-bottom:1px solid #e2e8f0;color:var(--text-mid);">Monthly EE</th>
                                <th style="padding:6px 10px;text-align:right;border-bottom:1px solid #e2e8f0;color:var(--text-mid);">Per Cut-off</th>
                            </tr>
                        </thead>
                        <tbody id="pagibigTableBody"></tbody>
                    </table>
                </div>
                @endif

                {{-- Salary bracket table for PhilHealth --}}
                @if ($isPhilhealth)
                <div style="margin-top:14px;">
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:8px;">
                        Quick Reference — Common Salary Brackets
                    </div>
                    <table style="width:100%;font-size:0.78rem;border-collapse:collapse;">
                        <thead>
                            <tr style="background:var(--bg);">
                                <th style="padding:6px 10px;text-align:left;border-bottom:1px solid #e2e8f0;color:var(--text-mid);">Basic Salary</th>
                                <th style="padding:6px 10px;text-align:right;border-bottom:1px solid #e2e8f0;color:var(--text-mid);">Total Premium</th>
                                <th style="padding:6px 10px;text-align:right;border-bottom:1px solid #e2e8f0;color:var(--text-mid);">EE 50%/mo</th>
                                <th style="padding:6px 10px;text-align:right;border-bottom:1px solid #e2e8f0;color:var(--text-mid);">Per Cut-off</th>
                            </tr>
                        </thead>
                        <tbody id="philhealthTableBody"></tbody>
                    </table>
                </div>
                @endif

                {{-- Bracket table for GSIS --}}
                @if ($isGsis)
                <div style="margin-top:14px;">
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:8px;">
                        Quick Reference — Common Salary Brackets
                    </div>
                    <table style="width:100%;font-size:0.78rem;border-collapse:collapse;">
                        <thead>
                            <tr style="background:var(--bg);">
                                <th style="padding:6px 10px;text-align:left;border-bottom:1px solid #e2e8f0;color:var(--text-mid);">Basic Salary</th>
                                <th style="padding:6px 10px;text-align:right;border-bottom:1px solid #e2e8f0;color:var(--text-mid);">Monthly PS</th>
                                <th style="padding:6px 10px;text-align:right;border-bottom:1px solid #e2e8f0;color:var(--text-mid);">Per Cut-off</th>
                            </tr>
                        </thead>
                        <tbody id="gsisTableBody"></tbody>
                    </table>
                </div>
                @endif

            </div>
        </div>
        @endif {{-- !$isWht --}}

    @endif {{-- $deductionType->is_computed --}}

    {{-- ══════════════════════════════════════════════════════════════════
         MAIN EDIT FORM
         ══════════════════════════════════════════════════════════════════ --}}
    <div class="card">
        <div class="card-header"><h3>Deduction Details</h3></div>
        <div class="card-body">

            <form method="POST"
                  action="{{ route('deduction-types.update', $deductionType) }}"
                  id="editTypeForm">
            @csrf
            @method('PUT')

                {{-- Code (read-only) --}}
                <div style="margin-bottom:18px;">
                    <label style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Code <span style="font-weight:400;color:var(--text-light);">(permanent)</span>
                    </label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-family:monospace;background:var(--bg);border:1px solid var(--border);
                                     padding:8px 14px;border-radius:6px;font-size:0.9rem;
                                     color:var(--navy);letter-spacing:.04em;">
                            {{ $deductionType->code }}
                        </span>
                        <span style="font-size:0.72rem;color:var(--text-light);">🔒 Locked</span>
                    </div>
                </div>

                {{-- Name --}}
                <div style="margin-bottom:18px;">
                    <label for="name" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Name <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="text" id="name" name="name"
                           value="{{ old('name', $deductionType->name) }}"
                           maxlength="200" required>
                    @error('name')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                </div>

                {{-- Category --}}
                <div style="margin-bottom:18px;">
                    <label for="deduction_type_category_id"
                           style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Category <span style="color:#dc2626;">*</span>
                    </label>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <select id="deduction_type_category_id"
                                name="deduction_type_category_id"
                                required style="flex:1;">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}"
                                        data-code="{{ $cat->code }}"
                                        {{ (string) old('deduction_type_category_id', $deductionType->deduction_type_category_id) === (string) $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                            @if (! $categories->contains('id', $deductionType->deduction_type_category_id) && $deductionType->deduction_type_category_id)
                                @php $orphanCat = \Modules\Payroll\Models\DeductionTypeCategory::withTrashed()->find($deductionType->deduction_type_category_id); @endphp
                                @if ($orphanCat)
                                <option value="{{ $orphanCat->id }}" selected disabled>
                                    {{ $orphanCat->name }} (inactive)
                                </option>
                                @endif
                            @endif
                        </select>
                        <a href="{{ route('deduction-type-categories.index') }}"
                           target="_blank"
                           style="font-size:0.78rem;white-space:nowrap;color:var(--navy);text-decoration:underline;"
                           title="Manage categories (opens in new tab)">
                            Manage
                        </a>
                    </div>
                    @error('deduction_type_category_id')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                </div>

                {{-- Display Order --}}
                <div style="margin-bottom:18px;">
                    <label for="display_order" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Display Order <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="number" id="display_order" name="display_order"
                           value="{{ old('display_order', $deductionType->display_order) }}"
                           min="0" max="999" required style="max-width:120px;">
                    <span id="orderConflictWarning"
                          style="display:none;color:#dc2626;font-size:0.78rem;margin-left:10px;">
                        ⚠ This order number is already used in the selected category.
                    </span>
                    @error('display_order')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                </div>

                <hr style="border:none;border-top:1px solid var(--border);margin:24px 0;">

                {{-- ── Global Amount & Lock ─────────────────────────────── --}}
                <div style="margin-bottom:18px;">
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:12px;">
                        Global Amount &amp; Enrollment Mode
                    </div>

                    @if ($deductionType->is_computed)
                    <div style="padding:12px 16px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;margin-bottom:14px;font-size:0.82rem;">
                        <strong style="color:#1e40af;">Formula type:</strong>
                        Setting an amount below and enabling <strong>Lock</strong> bypasses the formula
                        and applies the fixed amount to all employees. Leave blank (or unlock) to use
                        the formula as normal.
                    </div>

                    <div style="margin-bottom:14px;">
                        <label for="override_amount" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                            Global Override Amount (₱ per cut-off)
                        </label>
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                            <input type="number" id="override_amount" name="override_amount"
                                   value="{{ old('override_amount', $deductionType->override_amount) }}"
                                   min="0" step="0.01" placeholder="Leave blank = use formula"
                                   style="max-width:200px;">
                            @if ($deductionType->isOverridden())
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.82rem;color:#dc2626;cursor:pointer;">
                                    <input type="checkbox" name="clear_override" value="1">
                                    Clear override (restore formula)
                                </label>
                            @endif
                        </div>
                        @error('override_amount')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                    </div>

                    @if ($deductionType->isOverridden())
                    <div style="margin-bottom:14px;">
                        <label for="override_note" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                            Override Reason (for audit trail)
                        </label>
                        <input type="text" id="override_note" name="override_note"
                               value="{{ old('override_note', $deductionType->override_note) }}"
                               maxlength="300" placeholder="e.g. Adjusted per GSIS Circular 2026-01">
                        @error('override_note')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                    </div>
                    @endif

                    @else
                    {{-- Manual type: default_amount + percentage --}}
                    <div style="margin-bottom:14px;">
                        <label for="default_amount" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                            Global / Default Amount (₱ per cut-off)
                        </label>
                        <input type="number" id="default_amount" name="default_amount"
                               value="{{ old('default_amount', $deductionType->default_amount) }}"
                               min="0" step="0.01" placeholder="0.00" style="max-width:180px;">
                        @error('default_amount')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                        <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                            When <strong>Locked</strong>, this is applied to all employees.
                            When <strong>Unlocked</strong>, it pre-fills the per-employee enrollment form.
                        </div>
                    </div>

                    <div style="margin-bottom:14px;">
                        <label for="percentage" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                            Percentage of Basic Salary (%)
                        </label>
                        <input type="number" id="percentage" name="percentage"
                               value="{{ old('percentage', $deductionType->percentage) }}"
                               min="0" max="100" step="0.01" placeholder="e.g. 5.00"
                               style="max-width:180px;">
                        @error('percentage')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                        <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                            If set, deduction is calculated as this percentage of the employee's basic salary.
                            Overrides the fixed amount above. Leave blank to use fixed amount instead.
                        </div>
                    </div>
                    @endif

                    {{-- Lock toggle —— all types --}}
                    <div id="lockToggleWrapper">
                        <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;padding:14px 16px;background:var(--bg);border:1px solid var(--border);border-radius:8px;margin-bottom:0;">
                            <input type="checkbox" id="is_locked" name="is_locked" value="1"
                                   {{ old('is_locked', $deductionType->is_locked) ? 'checked' : '' }}
                                   style="width:16px;height:16px;margin-top:2px;accent-color:var(--navy);flex-shrink:0;">
                            <div>
                                <div style="font-weight:700;font-size:0.875rem;color:var(--navy);">
                                    🔒 Lock this deduction type
                                </div>
                                <div style="font-size:0.78rem;color:var(--text-mid);margin-top:3px;">
                                    @if ($deductionType->is_computed)
                                        <strong>Locked:</strong> The Override Amount above is applied to
                                        <strong>all employees</strong> — formula is bypassed entirely.<br>
                                        <strong>Unlocked:</strong> The formula runs normally per employee.
                                    @else
                                        <strong>Locked:</strong> The Global Amount above is applied to
                                        <strong>all employees</strong> automatically. HR cannot edit
                                        the amount per-employee — only the Payroll Officer can change it here.<br>
                                        <strong>Unlocked:</strong> Employees are enrolled individually.
                                        The amount above pre-fills the form but HR may override per employee.
                                    @endif
                                </div>
                                @if (in_array($deductionType->category, $loanCategories))
                                <div style="margin-top:8px;padding:8px 10px;background:#fef9c3;border:1px solid #fbbf24;border-radius:6px;font-size:0.78rem;color:#854d0e;">
                                    ⚠ <strong>Loan category:</strong> This type is always treated as
                                    per-employee even when locked, because loan amortization amounts
                                    differ per employee.
                                </div>
                                @endif
                            </div>
                        </label>
                    </div>

                </div>

                <hr style="border:none;border-top:1px solid var(--border);margin:24px 0;">

                {{-- Notes --}}
                <div style="margin-bottom:24px;">
                    <label for="notes" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Notes <span style="font-weight:400;">(optional)</span>
                    </label>
                    <textarea id="notes" name="notes" maxlength="500" rows="2">{{ old('notes', $deductionType->notes) }}</textarea>
                    @error('notes')<div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>@enderror
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit" id="submitBtn" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('deduction-types.index') }}" class="btn btn-outline">Cancel</a>
                </div>

            </form>
        </div>
    </div>

    {{-- Meta info --}}
    <div class="card" style="background:var(--bg);margin-top:16px;">
        <div class="card-body" style="font-size:0.78rem;color:var(--text-light);">
            <strong style="color:var(--text-mid);">Record created:</strong>
            {{ $deductionType->created_at->format('M d, Y g:i A') }}<br>
            <strong style="color:var(--text-mid);">Last updated:</strong>
            {{ $deductionType->updated_at->format('M d, Y g:i A') }}
        </div>
    </div>

</div>

@endsection

@section('scripts')
<script>
// ══════════════════════════════════════════════════════════════════════════
//  Live Formula Preview
// ══════════════════════════════════════════════════════════════════════════
@if ($deductionType->is_computed && !in_array($deductionType->code, ['WITHHOLDING_TAX', 'WHT']))
(function () {
    const code        = '{{ $deductionType->code }}';
    const isPagibig   = ['PAG_IBIG_1', 'PAGIBIG_1'].includes(code);
    const isPhilhealth= code === 'PHILHEALTH';
    const isGsis      = ['GSIS_LIFE_RETIREMENT', 'GSIS_LIFE_RET'].includes(code);

    // Bracket salaries to render in the quick-reference table
    const BRACKETS = [10000, 15000, 20000, 25000, 30000, 40000, 50000, 70000, 100000];

    function getField(id, fallback) {
        const el = document.getElementById(id);
        if (!el) return fallback;
        const v = parseFloat(el.value);
        return isNaN(v) ? fallback : v;
    }

    function fmt(n) {
        return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ── Computation functions (mirrors DeductionService PHP logic) ────────

    function computePagibig(basic) {
        const rate      = getField('formula_rate', 2.00) / 100;
        const rateLow   = getField('formula_rate_low', 1.00) / 100;
        const threshold = getField('formula_rate_threshold', 1500);
        const cap       = getField('formula_monthly_cap', 100);
        const applied   = basic <= threshold ? rateLow : rate;
        const monthly   = Math.min(Math.round(basic * applied * 100) / 100, cap);
        const cutoff    = Math.round(monthly / 2 * 100) / 100;
        const pctUsed   = (basic <= threshold ? getField('formula_rate_low', 1.00) : getField('formula_rate', 2.00)).toFixed(2);
        return {
            monthly, cutoff,
            breakdown: `${pctUsed}% × ${fmt(basic)} = ${fmt(basic * (basic <= threshold ? rateLow : rate))}` +
                       ` → capped at ${fmt(cap)}/mo → monthly EE: ${fmt(monthly)} → ÷2 cut-offs`,
            rateLabel: pctUsed + '%',
        };
    }

    function computePhilhealth(basic) {
        const rate    = getField('formula_rate', 5.00) / 100;
        const floor   = getField('formula_monthly_floor', 500);
        const ceiling = getField('formula_monthly_ceiling', 5000);
        const raw     = Math.round(basic * rate * 100) / 100;
        const total   = Math.max(floor, Math.min(raw, ceiling));
        const monthly = Math.round(total / 2 * 100) / 100; // EE 50%
        const cutoff  = Math.round(monthly / 2 * 100) / 100;
        const clamped = raw < floor ? `floored to ${fmt(floor)}` : raw > ceiling ? `capped at ${fmt(ceiling)}` : 'within range';
        return {
            monthly, cutoff,
            breakdown: `${(rate*100).toFixed(2)}% × ${fmt(basic)} = ${fmt(raw)} (${clamped})` +
                       ` → total premium: ${fmt(total)} → EE 50%: ${fmt(monthly)}/mo → ÷2 cut-offs`,
            total,
        };
    }

    function computeGsis(basic) {
        const rate    = getField('formula_rate', 9.00) / 100;
        const monthly = Math.round(basic * rate * 100) / 100;
        const cutoff  = Math.round(monthly / 2 * 100) / 100;
        return {
            monthly, cutoff,
            breakdown: `${(rate*100).toFixed(2)}% × ${fmt(basic)} = ${fmt(monthly)}/mo → ÷2 cut-offs`,
        };
    }

    function compute(basic) {
        if (isPagibig)    return computePagibig(basic);
        if (isPhilhealth) return computePhilhealth(basic);
        if (isGsis)       return computeGsis(basic);
        return { monthly: 0, cutoff: 0, breakdown: '' };
    }

    // ── Update main result panel ──────────────────────────────────────────

    function updatePreview() {
        const basic  = parseFloat(document.getElementById('previewSalary').value) || 0;
        const result = compute(basic);
        document.getElementById('previewMonthly').textContent = fmt(result.monthly);
        document.getElementById('previewCutoff').textContent  = fmt(result.cutoff);
        document.getElementById('previewBreakdown').textContent = result.breakdown;
        updateTable();
    }

    // ── Update quick-reference bracket table ─────────────────────────────

    function updateTable() {
        if (isPagibig) {
            const tbody = document.getElementById('pagibigTableBody');
            if (!tbody) return;
            tbody.innerHTML = BRACKETS.map(s => {
                const r = computePagibig(s);
                return `<tr>
                    <td style="padding:5px 10px;border-bottom:0.5px solid #e2e8f0;">${fmt(s)}</td>
                    <td style="padding:5px 10px;border-bottom:0.5px solid #e2e8f0;text-align:right;">${r.rateLabel}</td>
                    <td style="padding:5px 10px;border-bottom:0.5px solid #e2e8f0;text-align:right;">${fmt(r.monthly)}</td>
                    <td style="padding:5px 10px;border-bottom:0.5px solid #e2e8f0;text-align:right;font-weight:700;color:#3b0764;">${fmt(r.cutoff)}</td>
                </tr>`;
            }).join('');
        }

        if (isPhilhealth) {
            const tbody = document.getElementById('philhealthTableBody');
            if (!tbody) return;
            tbody.innerHTML = BRACKETS.map(s => {
                const r = computePhilhealth(s);
                return `<tr>
                    <td style="padding:5px 10px;border-bottom:0.5px solid #e2e8f0;">${fmt(s)}</td>
                    <td style="padding:5px 10px;border-bottom:0.5px solid #e2e8f0;text-align:right;">${fmt(r.total)}</td>
                    <td style="padding:5px 10px;border-bottom:0.5px solid #e2e8f0;text-align:right;">${fmt(r.monthly)}</td>
                    <td style="padding:5px 10px;border-bottom:0.5px solid #e2e8f0;text-align:right;font-weight:700;color:#3b0764;">${fmt(r.cutoff)}</td>
                </tr>`;
            }).join('');
        }

        if (isGsis) {
            const tbody = document.getElementById('gsisTableBody');
            if (!tbody) return;
            tbody.innerHTML = BRACKETS.map(s => {
                const r = computeGsis(s);
                return `<tr>
                    <td style="padding:5px 10px;border-bottom:0.5px solid #e2e8f0;">${fmt(s)}</td>
                    <td style="padding:5px 10px;border-bottom:0.5px solid #e2e8f0;text-align:right;">${fmt(r.monthly)}</td>
                    <td style="padding:5px 10px;border-bottom:0.5px solid #e2e8f0;text-align:right;font-weight:700;color:#3b0764;">${fmt(r.cutoff)}</td>
                </tr>`;
            }).join('');
        }
    }

    // ── Attach listeners ──────────────────────────────────────────────────
    const watchIds = [
        'previewSalary',
        'formula_rate', 'formula_rate_low', 'formula_rate_threshold', 'formula_monthly_cap',
        'formula_monthly_floor', 'formula_monthly_ceiling',
    ];
    watchIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', updatePreview);
    });

    // Run on load
    document.addEventListener('DOMContentLoaded', updatePreview);
    updatePreview();
})();
@endif

// ══════════════════════════════════════════════════════════════════════════
//  Order conflict detection
// ══════════════════════════════════════════════════════════════════════════
const existingOrders = @json($existingOrders);
const loanCategories = @json($loanCategories);

function selectedCategoryCode() {
    const sel = document.getElementById('deduction_type_category_id');
    const opt = sel ? sel.options[sel.selectedIndex] : null;
    return opt ? (opt.dataset.code || '') : '';
}

function checkOrderConflict() {
    const code  = selectedCategoryCode();
    const order = parseInt(document.getElementById('display_order').value, 10);
    const orders= existingOrders[code] || [];
    const warn  = document.getElementById('orderConflictWarning');
    if (warn) warn.style.display = (!isNaN(order) && orders.includes(order)) ? 'inline' : 'none';
}

const catEl = document.getElementById('deduction_type_category_id');
const ordEl = document.getElementById('display_order');
if (catEl) catEl.addEventListener('change', checkOrderConflict);
if (ordEl) ordEl.addEventListener('input', checkOrderConflict);
checkOrderConflict();

// ══════════════════════════════════════════════════════════════════════════
//  Save confirm
// ══════════════════════════════════════════════════════════════════════════
let __deductionTypeSubmitting = false;
document.getElementById('editTypeForm').addEventListener('submit', function (e) {
    if (__deductionTypeSubmitting) return;
    e.preventDefault();

    const form = this;
    const btn  = document.getElementById('submitBtn');

    Swal.fire({
        title: 'Save changes?',
        html: `<div style="text-align:left;">
            <div style="font-weight:700;color:#0F1B4C;margin-bottom:6px;">{{ addslashes($deductionType->name) }}</div>
            <div style="font-size:0.9rem;color:#6b7280;">This will update the deduction type settings.</div>
        </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0F1B4C',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true,
    }).then(result => {
        if (!result.isConfirmed) return;

        __deductionTypeSubmitting = true;
        btn.disabled = true;
        btn.textContent = 'Saving…';

        Swal.fire({
            title: '<span style="color:#0F1B4C;">Saving…</span>',
            html: '<div style="color:#6b7280;font-size:0.9rem;">Please wait.</div>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });

        form.submit();
    });
});
</script>
@endsection
