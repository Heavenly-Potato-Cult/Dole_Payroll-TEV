{{--
    Partial: renders one category card of deduction rows.
    Variables:
        $label       — category display name
        $types       — Collection of DeductionType in this category
        $enrollments — keyed by deduction_type_id (active enrollments for this employee)
        $employee    — Employee model
--}}
<div class="card">
    <div class="card-header">
        <h3>{{ $label }}</h3>
    </div>
    <div class="card-body" style="padding:0;">
        @foreach ($types as $type)
            @php
                $enroll   = $enrollments->get($type->id);
                $isActive = $enroll && $enroll->is_active;
                $amount   = $enroll ? $enroll->amount : '';
                $effFrom  = $enroll ? $enroll->effective_from?->format('Y-m-d') : now()->startOfMonth()->format('Y-m-d');
                $effTo    = $enroll ? $enroll->effective_to?->format('Y-m-d') : '';
                $notes    = $enroll ? $enroll->notes : '';

                // Determine display tier
                $isFormula  = $type->is_computed;
                $isLocked   = $type->isEffectivelyLocked();    // Tier 2
                $isManual   = !$isFormula && !$isLocked;       // Tier 3

                // For Tier 2, show the global amount from default_amount
                $globalAmt  = $type->default_amount !== null
                              ? number_format((float)$type->default_amount, 2)
                              : null;

                // For Tier 3, use default_amount as pre-fill if no enrollment yet
                $prefillAmt = ($isManual && !$isActive && $type->default_amount !== null)
                              ? (float)$type->default_amount
                              : $amount;
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

                        {{-- Show default badge if a pre-fill exists but no active enrollment --}}
                        @if (!$isActive && $type->default_amount !== null)
                            <span class="badge"
                                  style="background:#f3f4f6;color:var(--text-mid);font-size:0.66rem;"
                                  title="Default amount from Deduction Type settings">
                                Default: ₱{{ number_format((float)$type->default_amount, 2) }}
                            </span>
                        @endif
                    </div>

                    {{-- Amount + dates — shown only when checkbox is checked --}}
                    <div class="deduction-amount-row"
                         style="margin-top:10px;padding-left:28px;
                                display:{{ $isActive ? 'block' : 'none' }};">

                        <div style="display:grid;grid-template-columns:150px 140px 140px;gap:10px;align-items:end;">
                            <div>
                                <label style="font-size:0.72rem;margin-bottom:3px;">Amount per Cut-off (₱)</label>
                                <input type="number"
                                       class="deduction-amount"
                                       name="deductions[{{ $type->id }}][amount]"
                                       value="{{ $prefillAmt }}"
                                       min="0" step="0.01"
                                       placeholder="{{ $type->default_amount !== null ? number_format((float)$type->default_amount, 2) : '0.00' }}"
                                       style="margin-bottom:0;">
                                @if ($type->default_amount !== null)
                                    <div style="font-size:0.70rem;color:var(--text-light);margin-top:2px;">
                                        Default: ₱{{ number_format((float)$type->default_amount, 2) }}
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

            </div>
        @endforeach
    </div>
</div>
