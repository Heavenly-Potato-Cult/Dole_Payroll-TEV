@extends('layouts.app')

@section('title', 'Edit — ' . $deductionType->name)
@section('page-title', 'Deduction Types')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Deduction Type</h1>
        <p>
            <span style="font-family:monospace;background:var(--bg);border:1px solid var(--border);
                         padding:1px 8px;border-radius:4px;font-size:0.85rem;">
                {{ $deductionType->code }}
            </span>

            @if ($deductionType->is_computed)
                &nbsp;<span style="background:#eef2ff;color:#4338ca;font-size:0.68rem;font-weight:700;
                                   padding:2px 8px;border-radius:99px;border:1px solid #c7d2fe;">
                    🔒 Auto-computed (Locked)
                </span>
            @else
                &nbsp;<span style="background:#f0fdf4;color:#166534;font-size:0.68rem;font-weight:700;
                                   padding:2px 8px;border-radius:99px;border:1px solid #bbf7d0;">
                    ✏ Manual Enrollment
                </span>
            @endif

            @if ($deductionType->isOverridden())
                &nbsp;<span style="background:#fef3c7;color:#92400e;font-size:0.68rem;font-weight:700;
                                   padding:2px 8px;border-radius:99px;border:1px solid #fcd34d;">
                    ⚠ Override Active
                </span>
            @endif
        </p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('deduction-types.index') }}" class="btn btn-outline">← Back to List</a>
    </div>
</div>

<div style="max-width:680px;">

    {{-- ── Session errors ── --}}
    @if ($errors->any())
        <div class="alert alert-danger" style="margin-bottom:16px;">
            <strong>Please fix the following errors:</strong>
            <ul style="margin:6px 0 0 0;padding-left:20px;">
                @foreach ($errors->all() as $error)
                    <li style="font-size:0.85rem;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════════════
         MAIN EDIT FORM
         NOTE: The Activate/Deactivate toggle is intentionally placed in its
         own separate <form> BELOW this card, outside this form tag.
         HTML does not allow nested <form> elements — a nested form would
         cause the browser to silently break the submit button on the outer
         form, which was the root cause of the "Save button does nothing" bug.
    ════════════════════════════════════════════════════════════════════ --}}
    <div class="card">
        <div class="card-header"><h3>Deduction Type Details</h3></div>
        <div class="card-body">

            <form method="POST"
                  action="{{ route('deduction-types.update', $deductionType) }}"
                  id="editDeductionTypeForm">
            @csrf
            @method('PUT')

                {{-- ── Code (read-only) ────────────────────────────────────── --}}
                <div style="margin-bottom:18px;">
                    <label style="display:block;font-size:0.72rem;font-weight:700;
                                  text-transform:uppercase;letter-spacing:.07em;
                                  color:var(--text-mid);margin-bottom:5px;">
                        Code <span style="font-weight:400;color:var(--text-light);">(permanent — cannot be changed)</span>
                    </label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-family:monospace;background:var(--bg);border:1px solid var(--border);
                                     padding:8px 14px;border-radius:6px;font-size:0.9rem;
                                     color:var(--navy);letter-spacing:.04em;">
                            {{ $deductionType->code }}
                        </span>
                        <span style="font-size:0.72rem;color:var(--text-light);">🔒 Immutable</span>
                    </div>
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        Used by the payroll engine and enrollment system as a contract key.
                    </div>
                </div>

                {{-- ── Name ─────────────────────────────────────────────────── --}}
                <div style="margin-bottom:18px;">
                    <label for="name"
                           style="display:block;font-size:0.72rem;font-weight:700;
                                  text-transform:uppercase;letter-spacing:.07em;
                                  color:var(--text-mid);margin-bottom:5px;">
                        Display Name <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $deductionType->name) }}"
                           placeholder="e.g. HDMF Multi-Purpose Loan"
                           maxlength="200"
                           required>
                    @error('name')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        Shown on payslips, reports, and enrollment forms.
                    </div>
                </div>

                {{-- ── Category ─────────────────────────────────────────────── --}}
                <div style="margin-bottom:18px;">
                    <label for="category"
                           style="display:block;font-size:0.72rem;font-weight:700;
                                  text-transform:uppercase;letter-spacing:.07em;
                                  color:var(--text-mid);margin-bottom:5px;">
                        Category <span style="color:#dc2626;">*</span>
                    </label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <select id="category" name="category" required style="flex:1;"
                                onchange="checkOrderConflict()">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->key }}"
                                    {{ old('category', $deductionType->category) === $cat->key ? 'selected' : '' }}>
                                    {{ $cat->label }}
                                </option>
                            @endforeach
                        </select>
                        <a href="{{ route('deduction-categories.index') }}"
                           title="Manage categories"
                           style="font-size:0.75rem;color:var(--navy);white-space:nowrap;">
                            Manage ↗
                        </a>
                    </div>
                    @error('category')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ── Display Order ────────────────────────────────────────── --}}
                <div style="margin-bottom:18px;">
                    <label for="display_order"
                           style="display:block;font-size:0.72rem;font-weight:700;
                                  text-transform:uppercase;letter-spacing:.07em;
                                  color:var(--text-mid);margin-bottom:5px;">
                        Display Order <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="number"
                           id="display_order"
                           name="display_order"
                           value="{{ old('display_order', $deductionType->display_order) }}"
                           min="0"
                           max="999"
                           required
                           style="max-width:120px;"
                           oninput="checkOrderConflict()">
                    @error('display_order')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div id="orderConflictWarning"
                         style="color:#d97706;font-size:0.78rem;margin-top:4px;display:none;">
                        ⚠ This order number is already used by another type in this category.
                        The server will reject it — please choose a different number.
                    </div>
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        Controls display position on payslips. Must be unique within the selected category.
                    </div>
                </div>

                {{-- ── Notes ────────────────────────────────────────────────── --}}
                <div style="margin-bottom:24px;">
                    <label for="notes"
                           style="display:block;font-size:0.72rem;font-weight:700;
                                  text-transform:uppercase;letter-spacing:.07em;
                                  color:var(--text-mid);margin-bottom:5px;">
                        Notes / Description
                        <span style="color:var(--text-light);font-weight:400;">(optional)</span>
                    </label>
                    <textarea id="notes"
                              name="notes"
                              rows="3"
                              maxlength="500"
                              style="resize:vertical;">{{ old('notes', $deductionType->notes) }}</textarea>
                    @error('notes')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ══════════════════════════════════════════════════════════════
                     COMPUTATION MODE — Lock / Unlock
                ══════════════════════════════════════════════════════════════════ --}}
                <div style="border:2px solid {{ $deductionType->is_computed ? '#c7d2fe' : '#bbf7d0' }};
                            border-radius:10px;padding:18px;margin-bottom:20px;">

                    <div style="display:flex;align-items:center;justify-content:space-between;
                                flex-wrap:wrap;gap:12px;margin-bottom:14px;">
                        <div>
                            <div style="font-size:0.78rem;font-weight:700;text-transform:uppercase;
                                        letter-spacing:.07em;
                                        color:{{ $deductionType->is_computed ? '#4338ca' : '#166534' }};">
                                Computation Mode
                            </div>
                            <div id="modeStatusLabel"
                                 style="font-size:0.9rem;font-weight:600;margin-top:4px;
                                        color:{{ $deductionType->is_computed ? '#3730a3' : '#15803d' }};">
                                @if ($deductionType->is_computed)
                                    🔒 Auto-computed (Locked)
                                @else
                                    ✏ Manual Enrollment (Unlocked)
                                @endif
                            </div>
                        </div>

                        {{-- Toggle button --}}
                        <button type="button"
                                id="toggleModeBtn"
                                class="btn {{ $deductionType->is_computed ? 'btn-outline' : 'btn-primary' }}"
                                style="font-size:0.82rem;padding:7px 16px;"
                                onclick="confirmModeToggle()">
                            @if ($deductionType->is_computed)
                                🔓 Unlock (Switch to Manual)
                            @else
                                🔒 Lock (Switch to Auto-compute)
                            @endif
                        </button>
                    </div>

                    {{-- Mode description --}}
                    <div id="modeDescription" style="font-size:0.82rem;color:#4b5563;margin-bottom:12px;">
                        @if ($deductionType->is_computed)
                            The payroll engine calculates this deduction automatically using a built-in
                            formula. HR cannot manually set amounts for individual employees.
                            Use <em>Unlock</em> to switch to manual enrollment if you need full
                            control over amounts — but note employees will need to be re-enrolled.
                        @else
                            HR manually sets the deduction amount per employee via the Enrollment form.
                            Use <em>Lock</em> to switch to formula-driven computation if a formula
                            exists in <code>DeductionService</code> for this type's code
                            (<code>{{ $deductionType->code }}</code>).
                        @endif
                    </div>

                    {{-- Hidden input carries the is_computed value; JS toggles it on confirm --}}
                    <input type="hidden" name="is_computed" id="is_computed_input"
                           value="{{ old('is_computed', $deductionType->is_computed ? '1' : '0') }}">

                    {{-- Warning banner shown when user has staged a mode change --}}
                    <div id="modeChangeWarning"
                         style="display:none;background:#fef3c7;border:1px solid #fcd34d;
                                border-radius:6px;padding:10px 14px;font-size:0.82rem;color:#92400e;">
                        <strong>⚠ Staged change — not saved yet.</strong>
                        <span id="modeChangeWarningText"></span>
                        Click <strong>Save Changes</strong> to apply.
                        <button type="button"
                                style="margin-left:10px;background:none;border:none;
                                       color:#92400e;text-decoration:underline;cursor:pointer;
                                       font-size:0.82rem;padding:0;"
                                onclick="cancelModeToggle()">
                            Undo
                        </button>
                    </div>

                </div>
                {{-- ── End Computation Mode ─────────────────────────────────── --}}


                {{-- ══════════════════════════════════════════════════════════════
                     FORMULA PANEL — shown for auto-computed types
                ══════════════════════════════════════════════════════════════════ --}}
                @if ($deductionType->is_computed || old('is_computed') === '1')
                @php $fd = $formulaDescription; @endphp
                <div id="formulaPanel"
                     style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;
                            padding:16px;margin-bottom:20px;">
                    <div style="font-size:0.78rem;font-weight:700;color:#5b21b6;text-transform:uppercase;
                                letter-spacing:.07em;margin-bottom:10px;">
                        📐 Formula Reference
                    </div>

                    @if ($fd)
                        <div style="font-size:0.85rem;font-weight:600;color:#3730a3;margin-bottom:6px;">
                            {{ $fd['label'] }}
                        </div>
                        <div style="font-size:0.82rem;color:#374151;margin-bottom:14px;
                                    background:#ede9fe;border-radius:6px;padding:8px 12px;">
                            {{ $fd['formula'] }}
                        </div>

                        @if ($fd['js_formula'])
                        {{-- Interactive preview calculator --}}
                        <div style="border-top:1px solid #ddd6fe;padding-top:14px;">
                            <div style="font-size:0.78rem;font-weight:700;color:#5b21b6;margin-bottom:8px;">
                                Try It — Per-cutoff Preview
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                <div>
                                    <label style="font-size:0.72rem;color:var(--text-mid);display:block;margin-bottom:3px;">
                                        Basic Monthly Salary (₱)
                                    </label>
                                    <input type="number"
                                           id="previewBasic"
                                           placeholder="e.g. 25000"
                                           min="0"
                                           step="100"
                                           style="max-width:160px;margin-bottom:0;"
                                           oninput="runFormulaPreview()">
                                </div>
                                <div style="padding-top:18px;">
                                    <span style="font-size:0.78rem;color:var(--text-mid);">Per-cutoff amount:</span>
                                    <span id="formulaPreviewResult"
                                          style="font-size:1.05rem;font-weight:700;color:#3730a3;
                                                 margin-left:6px;">—</span>
                                </div>
                            </div>
                            <div style="font-size:0.72rem;color:var(--text-light);margin-top:6px;">
                                This is a reference preview only — actual amounts are computed per employee
                                during the payroll run.
                            </div>
                        </div>
                        @else
                        <div style="font-size:0.78rem;color:#6b7280;font-style:italic;">
                            Live preview not available for this formula (depends on YTD gross &amp;
                            employee-specific data). See <code>DeductionService::computeWithholdingTax()</code>.
                        </div>
                        @endif
                    @else
                        <div style="font-size:0.82rem;color:#6b7280;">
                            No formula description registered for code
                            <code>{{ $deductionType->code }}</code> in
                            <code>DeductionTypeController::formulaDescription()</code>.
                        </div>
                    @endif
                </div>
                @endif
                {{-- ── End Formula Panel ──────────────────────────────────────── --}}


                {{-- ══════════════════════════════════════════════════════════════
                     OVERRIDE PANEL — only for auto-computed types
                ══════════════════════════════════════════════════════════════════ --}}
                @if ($deductionType->is_computed)
                <div id="overridePanel"
                     style="background:#f8f7ff;border:1px solid #c7d2fe;border-radius:8px;
                            padding:16px;margin-bottom:20px;">

                    <div style="display:flex;align-items:center;justify-content:space-between;
                                margin-bottom:10px;">
                        <span style="font-size:0.78rem;font-weight:700;color:#4338ca;
                                     text-transform:uppercase;letter-spacing:.07em;">
                            🔧 Manual Override (Optional)
                        </span>
                        @if ($deductionType->isOverridden())
                            <span style="background:#fef3c7;color:#92400e;font-size:0.7rem;
                                         font-weight:700;padding:2px 10px;border-radius:99px;
                                         border:1px solid #fcd34d;">
                                Override is currently active
                            </span>
                        @else
                            <span style="background:#f0fdf4;color:#166534;font-size:0.7rem;
                                         font-weight:700;padding:2px 10px;border-radius:99px;
                                         border:1px solid #bbf7d0;">
                                Using formula
                            </span>
                        @endif
                    </div>

                    <div style="font-size:0.82rem;color:#4b5563;margin-bottom:12px;">
                        Enter a <strong>per-cutoff (semi-monthly)</strong> amount to override the formula
                        for <em>all employees</em>. Leave blank to keep using the formula.
                        Use this for edge cases (e.g. a GSIS correction memo).
                    </div>

                    <div style="display:grid;grid-template-columns:180px 1fr;gap:12px;align-items:start;">
                        <div>
                            <label for="override_amount"
                                   style="display:block;font-size:0.72rem;font-weight:700;
                                          text-transform:uppercase;letter-spacing:.07em;
                                          color:var(--text-mid);margin-bottom:5px;">
                                Override Amount (₱ / cutoff)
                            </label>
                            <input type="number"
                                   id="override_amount"
                                   name="override_amount"
                                   value="{{ old('override_amount', $deductionType->override_amount) }}"
                                   min="0"
                                   step="0.01"
                                   placeholder="Leave blank = formula"
                                   style="margin-bottom:0;">
                            @error('override_amount')
                                <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label for="override_note"
                                   style="display:block;font-size:0.72rem;font-weight:700;
                                          text-transform:uppercase;letter-spacing:.07em;
                                          color:var(--text-mid);margin-bottom:5px;">
                                Reason / Audit Note
                                <span style="color:#dc2626;">*</span>
                                <span style="font-weight:400;color:var(--text-light);">
                                    (required when overriding)
                                </span>
                            </label>
                            <input type="text"
                                   id="override_note"
                                   name="override_note"
                                   value="{{ old('override_note', $deductionType->override_note) }}"
                                   placeholder="e.g. Corrected per GSIS memo dated 2026-05"
                                   maxlength="300"
                                   style="margin-bottom:0;">
                            @error('override_note')
                                <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @if ($deductionType->isOverridden())
                    <div style="margin-top:12px;">
                        <label style="display:flex;align-items:center;gap:8px;
                                      cursor:pointer;font-size:0.82rem;color:#991b1b;">
                            <input type="hidden" name="clear_override" value="0">
                            <input type="checkbox"
                                   name="clear_override"
                                   value="1"
                                   {{ old('clear_override') ? 'checked' : '' }}
                                   style="width:15px;height:15px;">
                            <span><strong>Clear override</strong> — restore formula-based computation.</span>
                        </label>
                    </div>
                    @endif
                </div>
                @endif
                {{-- ── End Override Panel ──────────────────────────────────────── --}}


                {{-- ── Actions ─────────────────────────────────────────────── --}}
                <div style="display:flex;gap:10px;align-items:center;">
                    <button type="submit" id="saveBtn" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('deduction-types.index') }}" class="btn btn-outline">Cancel</a>
                    <span id="savingIndicator"
                          style="display:none;font-size:0.82rem;color:var(--text-mid);">
                        Saving…
                    </span>
                </div>

            </form>
            {{-- ↑ END editDeductionTypeForm — nothing else goes inside this form --}}

        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════════
         ACTIVATE / DEACTIVATE
         This is a SEPARATE card with its own <form> placed OUTSIDE the main
         edit form above. Nesting it inside editDeductionTypeForm was the root
         cause of the "Save button does nothing" bug — HTML ignores nested
         form tags, breaking the outer form's submit entirely.
    ════════════════════════════════════════════════════════════════════ --}}
    <div class="card" style="margin-top:16px;">
        <div class="card-body"
             style="display:flex;align-items:center;justify-content:space-between;
                    flex-wrap:wrap;gap:10px;font-size:0.82rem;">
            <div>
                <strong>Current status:</strong>
                @if ($deductionType->is_active)
                    <span style="color:#166534;font-weight:700;">● Active</span>
                    — visible in enrollment forms and included in payroll computation.
                @else
                    <span style="color:#991b1b;font-weight:700;">● Inactive</span>
                    — hidden from enrollment forms and skipped during payroll.
                @endif
            </div>
            <form id="toggleForm"
                  method="POST"
                  action="{{ route('deduction-types.toggle', $deductionType) }}"
                  style="display:inline;">
                @csrf
                @method('PATCH')
                <button type="button"
                        class="btn {{ $deductionType->is_active ? 'btn-outline' : 'btn-primary' }}"
                        style="font-size:0.8rem;padding:6px 14px;"
                        onclick="confirmToggle('{{ addslashes($deductionType->name) }}', {{ $deductionType->is_active ? 'true' : 'false' }})">
                    {{ $deductionType->is_active ? '⊘ Deactivate' : '✓ Activate' }}
                </button>
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
const existingOrders     = @json($existingOrders);
const originalIsComputed = {{ $deductionType->is_computed ? 'true' : 'false' }};

// Track whether user has staged a mode change
let stagedIsComputed = originalIsComputed;

// ── Formula preview config ──────────────────────────────────────────────────
@if ($formulaDescription && $formulaDescription['js_formula'])
const JS_FORMULA = function(basic) {
    return {{ $formulaDescription['js_formula'] }};
};
@else
const JS_FORMULA = null;
@endif

// ── DOMContentLoaded ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    // Form submit loading state
    const form       = document.getElementById('editDeductionTypeForm');
    const saveBtn    = document.getElementById('saveBtn');
    const savingText = document.getElementById('savingIndicator');

    if (form && saveBtn) {
        form.addEventListener('submit', function () {
            saveBtn.disabled    = true;
            saveBtn.textContent = 'Saving…';
            if (savingText) savingText.style.display = 'inline';
        });
    }

    // Order conflict check on load
    checkOrderConflict();

    // Category change
    const catSelect = document.getElementById('category');
    if (catSelect) catSelect.addEventListener('change', checkOrderConflict);
});

// ── Display order conflict ───────────────────────────────────────────────────
function checkOrderConflict() {
    const cat     = document.getElementById('category')?.value;
    const order   = parseInt(document.getElementById('display_order')?.value);
    const warning = document.getElementById('orderConflictWarning');

    if (! warning) return;

    if (cat && ! isNaN(order) && order >= 0
        && existingOrders[cat]
        && existingOrders[cat].includes(order)) {
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
}

// ── Formula preview calculator ───────────────────────────────────────────────
function runFormulaPreview() {
    const basic   = parseFloat(document.getElementById('previewBasic')?.value) || 0;
    const display = document.getElementById('formulaPreviewResult');
    if (! display || ! JS_FORMULA) return;

    if (basic <= 0) {
        display.textContent = '—';
        return;
    }

    const result = JS_FORMULA(basic);
    display.textContent = '₱' + result.toFixed(2);
}

// ── Computation mode toggle ──────────────────────────────────────────────────

function confirmModeToggle() {
    const switchingToManual = stagedIsComputed; // currently computed → going to manual

    let message = '';
    if (switchingToManual) {
        message = 'Switching to Manual: the formula will be disabled. '
                + 'You will need to enroll employees with amounts manually. '
                + 'Any active override will be cleared.';
    } else {
        message = 'Switching to Auto-computed: the payroll engine will '
                + 'run the formula for this type. Make sure a formula exists in '
                + 'DeductionService for code {{ $deductionType->code }}.';
    }

    const proceed = confirm(
        (switchingToManual
            ? 'Switch to Manual Enrollment?\n\n'
            : 'Switch to Auto-computed (Locked)?\n\n')
        + message
        + '\n\nThis change only takes effect after you click Save Changes.'
    );

    if (proceed) {
        applyModeToggle(! stagedIsComputed);
    }
}

function applyModeToggle(newIsComputed) {
    stagedIsComputed = newIsComputed;

    // Update hidden input
    document.getElementById('is_computed_input').value = newIsComputed ? '1' : '0';

    // Update button label & style
    const btn = document.getElementById('toggleModeBtn');
    if (btn) {
        btn.textContent = newIsComputed
            ? '🔓 Unlock (Switch to Manual)'
            : '🔒 Lock (Switch to Auto-compute)';
        btn.className = 'btn ' + (newIsComputed ? 'btn-outline' : 'btn-primary');
    }

    // Update status label
    const label = document.getElementById('modeStatusLabel');
    if (label) {
        label.textContent = newIsComputed
            ? '🔒 Auto-computed (Locked)'
            : '✏ Manual Enrollment (Unlocked)';
        label.style.color = newIsComputed ? '#3730a3' : '#15803d';
    }

    // Show/hide staged-change warning
    const warning     = document.getElementById('modeChangeWarning');
    const warningText = document.getElementById('modeChangeWarningText');
    const hasChanged  = (newIsComputed !== originalIsComputed);

    if (hasChanged) {
        warningText.innerHTML = newIsComputed
            ? ' Will switch to <strong>Auto-computed</strong> on save.'
            : ' Will switch to <strong>Manual Enrollment</strong> on save. Override will be cleared.';
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
}

function cancelModeToggle() {
    applyModeToggle(originalIsComputed);
}

// ── Activate / Deactivate confirmation ──────────────────────────────────────
function confirmToggle(typeName, isActive) {
    const action = isActive ? 'Deactivate' : 'Activate';

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title:             action + ' Deduction Type?',
            html:              '<div style="text-align:center;">'
                             + '<div style="font-size:1.1rem;font-weight:600;color:#dc3545;margin-bottom:8px;">'
                             + typeName + '</div>'
                             + '<p style="color:#6b7280;font-size:0.95rem;">Are you sure?</p>'
                             + '</div>',
            icon:              'warning',
            showCancelButton:  true,
            confirmButtonText: action,
            cancelButtonText:  'Cancel',
            confirmButtonColor: isActive ? '#dc3545' : '#10B981',
            cancelButtonColor:  '#6B7280',
            reverseButtons:    true,
            focusCancel:       true,
        }).then(result => {
            if (result.isConfirmed) {
                document.getElementById('toggleForm').submit();
            }
        });
    } else {
        if (confirm('Are you sure you want to ' + action.toLowerCase() + ' this deduction type?')) {
            document.getElementById('toggleForm').submit();
        }
    }
}
</script>
@endsection
