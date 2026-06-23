{{--
    Partial: renders one category card of deduction rows.
    Variables:
        $label           — category display name
        $types           — Collection of DeductionType in this category
        $enrollments     — keyed by deduction_type_id (active enrollments for this employee, non-loan types)
        $loanEnrollments — keyed by deduction_type_id => Collection of enrollments (one per account, loan-category types)
        $employee        — Employee model
--}}
@php
    $loanEnrollments = $loanEnrollments ?? collect();
@endphp
<div class="card">
    <div class="card-header">
        <h3>{{ $label }}</h3>
    </div>
    <div class="card-body" style="padding:0;">
        @foreach ($types as $type)
            @php
                $isLoanType = $type->supportsMultipleAccounts();

                $enroll = null;
                $accountsForType = collect();

                if ($isLoanType) {
                    $accountsForType = $loanEnrollments->get($type->id, collect())->values();
                    $isActive        = $accountsForType->isNotEmpty();
                } else {
                    $enroll   = $enrollments->get($type->id);
                    $isActive = $enroll && $enroll->is_active;
                }

                $amount             = (!$isLoanType && $enroll) ? $enroll->amount : '';
                $percentageOverride = (!$isLoanType && $enroll) ? $enroll->percentage_override : '';
                $effFrom            = (!$isLoanType && $enroll) ? $enroll->effective_from?->format('Y-m-d') : now()->startOfMonth()->format('Y-m-d');
                $effTo              = (!$isLoanType && $enroll) ? $enroll->effective_to?->format('Y-m-d') : '';
                $notes              = (!$isLoanType && $enroll) ? $enroll->notes : '';

                // Determine display tier
                $isFormula  = $type->is_computed;
                $isLocked   = $type->isEffectivelyLocked();    // Tier 2 (never true for loan categories)
                $isManual   = !$isFormula && !$isLocked;       // Tier 3 — loan categories always land here

                // For Tier 2, show the global amount from default_amount or percentage
                if ($type->percentage !== null) {
                    // Calculate percentage of employee's basic salary
                    $monthlyAmount = round($employee->basic_monthly_salary * ($type->percentage / 100), 2);
                    $cutoffAmount  = round($monthlyAmount / 2, 2);
                    $globalAmt     = number_format($cutoffAmount, 2) . ' (' . number_format((float)$type->percentage, 2) . '%)';
                } else {
                    $globalAmt = $type->default_amount !== null
                              ? number_format((float)$type->default_amount, 2)
                              : null;
                    $cutoffAmount = null;
                }

                // For Tier 3 (non-loan), use default_amount as pre-fill if no enrollment yet
                $prefillAmt = ($isManual && !$isLoanType && !$isActive && $type->default_amount !== null)
                              ? (float)$type->default_amount
                              : $amount;

                // For Tier 3 (non-loan), use type percentage as pre-fill if no enrollment override
                $prefillPct = ($isManual && !$isLoanType && !$isActive && $type->percentage !== null)
                              ? (float)$type->percentage
                              : $percentageOverride;
            @endphp

            <div class="deduction-row"
                 style="padding:12px 20px;border-bottom:1px solid var(--border);
                        {{ ($isFormula || $isLocked) ? 'background:var(--navy-light);opacity:0.9;' : '' }}">

                {{-- ── Tier 1: Formula-computed ───────────────────────────────── --}}
                @if ($isFormula)
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="width:18px;text-align:center;color:var(--text-light);"
                              title="Auto-computed by formula">⚙️</span>
                        <div style="flex:1;">
                            <span style="font-weight:600;font-size:0.875rem;color:var(--navy);">
                                {{ $type->name }}
                            </span>
                            <span class="badge"
                                  style="background:#dbeafe;color:#1e40af;margin-left:8px;font-size:0.66rem;">
                                Auto-computed
                            </span>
                            @if ($type->isOverridden())
                                <span class="badge"
                                      style="background:#fef9c3;color:#854d0e;margin-left:4px;font-size:0.66rem;"
                                      title="{{ $type->override_note }}">
                                    ★ Overridden: ₱{{ number_format((float)$type->override_amount, 2) }}/cut-off
                                </span>
                            @endif
                            @if ($type->notes)
                                <div style="font-size:0.76rem;color:var(--text-light);margin-top:2px;">
                                    {{ $type->notes }}
                                </div>
                            @endif
                        </div>
                    </div>

                {{-- ── Tier 2: Locked — global fixed amount ──────────────────── --}}
                @elseif ($isLocked)
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="width:18px;text-align:center;color:var(--text-light);"
                              title="Locked — global amount applies to all employees">🔒</span>
                        <div style="flex:1;">
                            <span style="font-weight:600;font-size:0.875rem;color:var(--navy);">
                                {{ $type->name }}
                            </span>
                            <span class="badge"
                                  style="background:#dcfce7;color:#166534;margin-left:8px;font-size:0.66rem;">
                                Global Fixed
                            </span>
                            <div style="font-size:0.82rem;color:var(--navy);margin-top:4px;font-weight:600;">
                                @if ($globalAmt !== null)
                                    ₱{{ $globalAmt }}<span style="font-weight:400;color:var(--text-mid);font-size:0.76rem;"> / cut-off &nbsp;·&nbsp; applied to all employees</span>
                                @else
                                    <span style="color:#dc2626;font-size:0.76rem;">
                                        ⚠ No global amount configured — contact the Payroll Officer.
                                    </span>
                                @endif
                            </div>
                            @if ($type->notes)
                                <div style="font-size:0.76rem;color:var(--text-light);margin-top:2px;">
                                    {{ $type->notes }}
                                </div>
                            @endif
                        </div>
                    </div>

                {{-- ── Tier 3: Manual / per-employee ─────────────────────────── --}}
                @else
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input type="checkbox"
                               class="deduction-checkbox"
                               id="enroll_{{ $type->id }}"
                               name="deductions[{{ $type->id }}][enrolled]"
                               value="1"
                               {{ $isActive ? 'checked' : '' }}
                               style="width:16px;height:16px;accent-color:var(--navy);flex-shrink:0;">

                        <label for="enroll_{{ $type->id }}"
                               style="flex:1;cursor:pointer;margin:0;
                                      font-weight:600;font-size:0.875rem;
                                      text-transform:none;letter-spacing:0;
                                      color:var(--navy);">
                            {{ $type->name }}
                        </label>

                        @if ($isLoanType)
                            <span class="badge"
                                  style="background:#f3f4f6;color:var(--text-mid);font-size:0.66rem;"
                                  title="Supports multiple accounts of this loan type">
                                Multi-account
                            </span>
                        @endif

                        {{-- Show default badge if a pre-fill exists but no active enrollment --}}
                        @if (!$isLoanType && !$isActive && $type->default_amount !== null)
                            <span class="badge"
                                  style="background:#f3f4f6;color:var(--text-mid);font-size:0.66rem;"
                                  title="Default amount from Deduction Type settings">
                                Default: ₱{{ number_format((float)$type->default_amount, 2) }}
                            </span>
                        @endif

                        {{-- Show percentage badge if percentage is set but no active enrollment --}}
                        @if (!$isLoanType && !$isActive && $type->percentage !== null)
                            @php
                                $monthlyAmount = round($employee->basic_monthly_salary * ($type->percentage / 100), 2);
                                $cutoffAmount  = round($monthlyAmount / 2, 2);
                            @endphp
                            <span class="badge"
                                  style="background:#dbeafe;color:#1e40af;font-size:0.66rem;"
                                  title="Calculated as {{ number_format((float)$type->percentage, 2) }}% of basic salary">
                                {{ number_format((float)$type->percentage, 2) }}% = ₱{{ number_format($cutoffAmount, 2) }}/cut-off
                            </span>
                        @endif
                    </div>

                    @if ($isLoanType)
                        {{-- Loan-category types: one or more repeatable account slots (max 3) --}}
                        <div class="deduction-amount-row loan-accounts-wrapper"
                             data-type-id="{{ $type->id }}"
                             data-max-accounts="3"
                             style="margin-top:10px;padding-left:28px;
                                    display:{{ $isActive ? 'block' : 'none' }};">

                            <div class="loan-account-slots">
                                @php
                                    $slotCount = max($accountsForType->count(), 1);
                                @endphp
                                @for ($i = 0; $i < $slotCount; $i++)
                                    @php $acct = $accountsForType->get($i); @endphp
                                    <div class="loan-account-slot"
                                         style="margin-bottom:10px;padding:10px;border:1px dashed var(--border);border-radius:6px;">
                                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                            <span class="loan-account-slot-label"
                                                  style="font-size:0.72rem;font-weight:600;color:var(--text-mid);">
                                                Account {{ $i + 1 }}
                                            </span>
                                            <button type="button" class="btn-remove-account"
                                                    style="display:{{ $i === 0 ? 'none' : 'inline-block' }};
                                                           border:none;background:none;color:#dc2626;
                                                           font-weight:700;cursor:pointer;font-size:0.9rem;line-height:1;">
                                                ×
                                            </button>
                                        </div>
                                        <div style="display:grid;grid-template-columns:150px 150px 130px 130px;gap:10px;align-items:end;">
                                            <div>
                                                <label style="font-size:0.72rem;margin-bottom:3px;">Monthly Amortization (₱)</label>
                                                <input type="number"
                                                       class="deduction-amount"
                                                       name="deductions[{{ $type->id }}][accounts][{{ $i }}][amount]"
                                                       value="{{ $acct ? $acct->amount : '' }}"
                                                       min="0" step="0.01" placeholder="0.00"
                                                       style="margin-bottom:0;">
                                            </div>
                                            <div>
                                                <label style="font-size:0.72rem;margin-bottom:3px;">Account Number</label>
                                                <input type="text"
                                                       class="deduction-account-number"
                                                       name="deductions[{{ $type->id }}][accounts][{{ $i }}][account_number]"
                                                       value="{{ $acct ? $acct->account_number : '' }}"
                                                       maxlength="100" placeholder="e.g. CL-2026-00123"
                                                       style="margin-bottom:0;">
                                            </div>
                                            <div>
                                                <label style="font-size:0.72rem;margin-bottom:3px;">Effective From</label>
                                                <input type="date"
                                                       name="deductions[{{ $type->id }}][accounts][{{ $i }}][effective_from]"
                                                       value="{{ $acct ? $acct->effective_from?->format('Y-m-d') : now()->startOfMonth()->format('Y-m-d') }}"
                                                       style="margin-bottom:0;">
                                            </div>
                                            <div>
                                                <label style="font-size:0.72rem;margin-bottom:3px;">Effective To (blank = ongoing)</label>
                                                <input type="date"
                                                       name="deductions[{{ $type->id }}][accounts][{{ $i }}][effective_to]"
                                                       value="{{ $acct ? $acct->effective_to?->format('Y-m-d') : '' }}"
                                                       style="margin-bottom:0;">
                                            </div>
                                        </div>
                                        <div style="margin-top:8px;">
                                            <label style="font-size:0.72rem;margin-bottom:3px;">Notes (optional)</label>
                                            <input type="text"
                                                   name="deductions[{{ $type->id }}][accounts][{{ $i }}][notes]"
                                                   value="{{ $acct ? $acct->notes : '' }}"
                                                   placeholder="e.g. Loan period, purpose…"
                                                   maxlength="200" style="margin-bottom:0;">
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <button type="button" class="btn btn-outline btn-add-account"
                                    style="font-size:0.78rem;padding:4px 10px;">
                                + Add another account
                            </button>
                        </div>
                    @else
                        {{-- Non-loan Tier 3: single set of amount + date fields --}}
                        <div class="deduction-amount-row"
                             style="margin-top:10px;padding-left:28px;
                                    display:{{ $isActive ? 'block' : 'none' }};">

                            <div style="display:grid;grid-template-columns:150px 100px 140px 140px;gap:10px;align-items:end;">
                                <div>
                                    <label style="font-size:0.72rem;margin-bottom:3px;">Monthly Amortization (₱)</label>
                                    <input type="number"
                                           class="deduction-amount"
                                           name="deductions[{{ $type->id }}][amount]"
                                           value="{{ $prefillAmt }}"
                                           min="0" step="0.01"
                                           placeholder="{{ $type->percentage !== null ? number_format($cutoffAmount ?? 0, 2) : ($type->default_amount !== null ? number_format((float)$type->default_amount, 2) : '0.00') }}"
                                           style="margin-bottom:0;">
                                    @if ($type->percentage !== null)
                                        <div style="font-size:0.70rem;color:#1e40af;margin-top:2px;">
                                            {{ number_format((float)$type->percentage, 2) }}% of salary = ₱{{ number_format($cutoffAmount ?? 0, 2) }}
                                        </div>
                                    @elseif ($type->default_amount !== null)
                                        <div style="font-size:0.70rem;color:var(--text-light);margin-top:2px;">
                                            Default: ₱{{ number_format((float)$type->default_amount, 2) }}
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    <label style="font-size:0.72rem;margin-bottom:3px;">Override % (optional)</label>
                                    <input type="number"
                                           class="deduction-percentage"
                                           name="deductions[{{ $type->id }}][percentage_override]"
                                           value="{{ $prefillPct }}"
                                           min="0" max="100" step="0.01"
                                           placeholder="{{ $type->percentage !== null ? number_format((float)$type->percentage, 2) : '' }}"
                                           style="margin-bottom:0;">
                                    @if ($type->percentage !== null)
                                        <div style="font-size:0.70rem;color:var(--text-light);margin-top:2px;">
                                            Type: {{ number_format((float)$type->percentage, 2) }}%
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    <label style="font-size:0.72rem;margin-bottom:3px;">Effective From</label>
                                    <input type="date"
                                           name="deductions[{{ $type->id }}][effective_from]"
                                           value="{{ $effFrom }}"
                                           style="margin-bottom:0;">
                                </div>

                                <div>
                                    <label style="font-size:0.72rem;margin-bottom:3px;">Effective To (blank = ongoing)</label>
                                    <input type="date"
                                           name="deductions[{{ $type->id }}][effective_to]"
                                           value="{{ $effTo }}"
                                           style="margin-bottom:0;">
                                </div>
                            </div>

                            <div style="margin-top:8px;">
                                <label style="font-size:0.72rem;margin-bottom:3px;">Notes (optional)</label>
                                <input type="text"
                                       name="deductions[{{ $type->id }}][notes]"
                                       value="{{ $notes }}"
                                       placeholder="e.g. Loan account no., loan period…"
                                       maxlength="200"
                                       style="margin-bottom:0;">
                            </div>
                        </div>
                    @endif
                @endif

            </div>
        @endforeach
    </div>
</div>
