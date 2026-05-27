@extends('layouts.app')

@section('title', 'New Deduction Type')
@section('page-title', 'Deduction Types')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>New Deduction Type</h1>
        <p>Add a new deduction line item to the payroll system.</p>
    </div>
    <div>
        <a href="{{ route('deduction-types.index') }}" class="btn btn-outline">← Back to List</a>
    </div>
</div>

<div style="max-width:700px;">

    <div class="card">
        <div class="card-header"><h3>Deduction Details</h3></div>
        <div class="card-body">

            <form method="POST" action="{{ route('deduction-types.store') }}" id="createTypeForm">
            @csrf

                {{-- Code --}}
                <div style="margin-bottom:18px;">
                    <label for="code" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Code <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="text"
                           id="code"
                           name="code"
                           value="{{ old('code') }}"
                           placeholder="e.g. COOP_LOAN"
                           maxlength="50"
                           required
                           autocomplete="off"
                           style="font-family:monospace;text-transform:uppercase;"
                           oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9_]/g,'')">
                    @error('code')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        ⚠ The code is <strong>permanent</strong> — it cannot be changed after saving.
                        Uppercase letters, numbers, and underscores only (e.g. <code>COOP_LOAN</code>).
                    </div>
                </div>

                {{-- Name --}}
                <div style="margin-bottom:18px;">
                    <label for="name" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Name <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           placeholder="e.g. Cooperative Loan"
                           maxlength="200"
                           required>
                    @error('name')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Category --}}
                <div style="margin-bottom:18px;">
                    <label for="category" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Category <span style="color:#dc2626;">*</span>
                    </label>
                    <select id="category" name="category" required>
                        <option value="">— Select a category —</option>
                        @foreach ($categoryLabels as $key => $label)
                            <option value="{{ $key }}"
                                    {{ old('category') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('category')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Display Order --}}
                <div style="margin-bottom:18px;">
                    <label for="display_order" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Display Order <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="number"
                           id="display_order"
                           name="display_order"
                           value="{{ old('display_order', $nextOrder) }}"
                           min="0"
                           max="999"
                           required
                           style="max-width:120px;">
                    <span id="orderConflictWarning"
                          style="display:none;color:#dc2626;font-size:0.78rem;margin-left:10px;">
                        ⚠ This order number is already used in the selected category.
                    </span>
                    @error('display_order')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        Order numbers must be unique within a category.
                    </div>
                </div>

                <hr style="border:none;border-top:1px solid var(--border);margin:24px 0;">

                {{-- ── Global Amount & Lock section ────────────────────────── --}}
                <div style="margin-bottom:18px;">
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:12px;">
                        Global Amount &amp; Enrollment Mode
                    </div>

                    {{-- Default Amount --}}
                    <div style="margin-bottom:16px;">
                        <label for="default_amount" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                            Global / Default Amount (₱ per cut-off)
                        </label>
                        <input type="number"
                               id="default_amount"
                               name="default_amount"
                               value="{{ old('default_amount') }}"
                               min="0"
                               step="0.01"
                               placeholder="0.00"
                               style="max-width:180px;">
                        @error('default_amount')
                            <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                        @enderror
                        <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                            When <strong>Locked</strong>, this amount is applied to all employees automatically.
                            When <strong>Unlocked</strong>, it pre-fills the per-employee enrollment form as a default.
                            Leave blank if no default applies.
                        </div>
                    </div>

                    {{-- Percentage --}}
                    <div style="margin-bottom:16px;">
                        <label for="percentage" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                            Percentage of Basic Salary (%)
                        </label>
                        <input type="number"
                               id="percentage"
                               name="percentage"
                               value="{{ old('percentage') }}"
                               min="0"
                               max="100"
                               step="0.01"
                               placeholder="e.g. 5.00"
                               style="max-width:180px;">
                        @error('percentage')
                            <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                        @enderror
                        <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                            If set, deduction is calculated as this percentage of the employee's basic salary.
                            Overrides the fixed amount above. Leave blank to use fixed amount instead.
                        </div>
                    </div>

                    {{-- Lock toggle --}}
                    <div id="lockToggleWrapper">
                        <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;padding:14px 16px;background:var(--bg);border:1px solid var(--border);border-radius:8px;margin-bottom:0;">
                            <input type="checkbox"
                                   id="is_locked"
                                   name="is_locked"
                                   value="1"
                                   {{ old('is_locked') ? 'checked' : '' }}
                                   style="width:16px;height:16px;margin-top:2px;accent-color:var(--navy);flex-shrink:0;">
                            <div>
                                <div style="font-weight:700;font-size:0.875rem;color:var(--navy);">
                                    🔒 Lock this deduction type
                                </div>
                                <div style="font-size:0.78rem;color:var(--text-mid);margin-top:3px;">
                                    When locked, the Global Amount above is applied to <strong>all employees</strong>
                                    automatically. HR cannot edit the amount per-employee — only the Payroll Officer
                                    can change it here in the CMS.
                                    <br><br>
                                    When unlocked, employees can be enrolled individually and the amount
                                    can be set per-employee (e.g. for loans with different amortization amounts).
                                </div>
                                <div id="loanLockWarning"
                                     style="display:none;margin-top:8px;padding:8px 10px;
                                            background:#fef9c3;border:1px solid #fbbf24;border-radius:6px;
                                            font-size:0.78rem;color:#854d0e;">
                                    ⚠ <strong>Note:</strong> Loan-category deductions (Bank Loans, CARESS IX) are
                                    always treated as per-employee regardless of this setting, since loan
                                    amortization amounts differ per employee.
                                </div>
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
                    <textarea id="notes"
                              name="notes"
                              maxlength="500"
                              rows="2"
                              placeholder="e.g. Fixed monthly amortization. Enrolled manually per employee.">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="alert alert-info" style="margin-bottom:20px;font-size:0.82rem;">
                    <strong>Note:</strong> New types are always <strong>manual</strong> (not auto-computed).
                    Auto-computed types (PAG-IBIG I, PhilHealth, GSIS, WHT) are built into the payroll engine
                    and cannot be added through this form.
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit" id="submitBtn" class="btn btn-primary">Save Deduction Type</button>
                    <a href="{{ route('deduction-types.index') }}" class="btn btn-outline">Cancel</a>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// ── Order conflict detection ──────────────────────────────────────────────
const existingOrders = @json($existingOrders);
const loanCategories = @json($loanCategories);

function checkOrderConflict() {
    const cat    = document.getElementById('category').value;
    const order  = parseInt(document.getElementById('display_order').value, 10);
    const orders = existingOrders[cat] || [];
    const warn   = document.getElementById('orderConflictWarning');
    warn.style.display = (orders.includes(order)) ? 'inline' : 'none';
}

document.getElementById('category').addEventListener('change', function () {
    checkOrderConflict();
    // Show/hide loan lock warning
    const loanWarn = document.getElementById('loanLockWarning');
    if (loanWarn) {
        loanWarn.style.display = loanCategories.includes(this.value) ? 'block' : 'none';
    }
});
document.getElementById('display_order').addEventListener('input', checkOrderConflict);

// ── Disable save while submitting ────────────────────────────────────────
document.getElementById('createTypeForm').addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    btn.disabled    = true;
    btn.textContent = 'Saving…';
});
</script>
@endsection
