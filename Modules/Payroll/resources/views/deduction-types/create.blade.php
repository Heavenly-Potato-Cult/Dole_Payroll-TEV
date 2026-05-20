@extends('layouts.app')

@section('title', 'New Deduction Type')
@section('page-title', 'Deduction Types')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>New Deduction Type</h1>
        <p>Add a new manual deduction or loan type to the payroll system.</p>
    </div>
    <div>
        <a href="{{ route('deduction-types.index') }}" class="btn btn-outline">← Back to List</a>
    </div>
</div>

<div style="max-width:640px;">

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

    <div class="card">
        <div class="card-header"><h3>Deduction Type Details</h3></div>
        <div class="card-body">

            <form method="POST"
                  action="{{ route('deduction-types.store') }}"
                  id="createDeductionTypeForm">
            @csrf

                {{-- ── Code ──────────────────────────────────────────────── --}}
                <div style="margin-bottom:18px;">
                    <label for="code"
                           style="display:block;font-size:0.72rem;font-weight:700;
                                  text-transform:uppercase;letter-spacing:.07em;
                                  color:var(--text-mid);margin-bottom:5px;">
                        Code <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="text"
                           id="code"
                           name="code"
                           value="{{ old('code') }}"
                           placeholder="e.g. GSIS_NEW_LOAN"
                           maxlength="50"
                           required
                           autocomplete="off"
                           style="font-family:monospace;text-transform:uppercase;letter-spacing:.05em;"
                           oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9_]/g,'')">
                    @error('code')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        ⚠ The code is <strong>permanent</strong> — it cannot be changed after saving.
                        Use UPPERCASE letters, numbers, and underscores only (e.g. <code>HDMF_NEW_LOAN</code>).
                    </div>
                </div>

                {{-- ── Name ─────────────────────────────────────────────── --}}
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
                           value="{{ old('name') }}"
                           placeholder="e.g. HDMF New Loan"
                           maxlength="200"
                           required>
                    @error('name')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        Shown on payslips, enrollment forms, and reports. Can be changed at any time.
                    </div>
                </div>

                {{-- ── Category ─────────────────────────────────────────── --}}
                <div style="margin-bottom:18px;">
                    <label for="category"
                           style="display:block;font-size:0.72rem;font-weight:700;
                                  text-transform:uppercase;letter-spacing:.07em;
                                  color:var(--text-mid);margin-bottom:5px;">
                        Category <span style="color:#dc2626;">*</span>
                    </label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <select id="category"
                                name="category"
                                required
                                style="flex:1;"
                                onchange="handleCategoryChange(this)">
                            <option value="">— Select category —</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->key }}"
                                    {{ old('category') === $cat->key ? 'selected' : '' }}>
                                    {{ $cat->label }}
                                </option>
                            @endforeach
                            <option value="__new__">➕ Create new category…</option>
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
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        Groups this deduction on payslips and enrollment forms.
                    </div>

                    {{-- Inline new-category panel --}}
                    <div id="newCategoryPanel"
                         style="display:none;margin-top:12px;padding:14px;
                                background:var(--navy-light);border:1px solid var(--border);
                                border-radius:8px;">
                        <div style="font-size:0.78rem;font-weight:700;color:var(--navy);margin-bottom:10px;">
                            Quick-add a new category
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                            <div>
                                <label style="font-size:0.72rem;font-weight:700;text-transform:uppercase;
                                              letter-spacing:.07em;color:var(--text-mid);display:block;margin-bottom:4px;">
                                    Key (slug) <span style="color:#dc2626;">*</span>
                                </label>
                                <input type="text"
                                       id="new_cat_key"
                                       placeholder="e.g. coop_loan"
                                       maxlength="50"
                                       style="font-family:monospace;"
                                       oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9_]/g,'')">
                            </div>
                            <div>
                                <label style="font-size:0.72rem;font-weight:700;text-transform:uppercase;
                                              letter-spacing:.07em;color:var(--text-mid);display:block;margin-bottom:4px;">
                                    Label <span style="color:#dc2626;">*</span>
                                </label>
                                <input type="text"
                                       id="new_cat_label"
                                       placeholder="e.g. Cooperative Loans"
                                       maxlength="100">
                            </div>
                        </div>
                        <button type="button"
                                class="btn btn-primary"
                                style="font-size:0.8rem;padding:6px 14px;"
                                onclick="submitNewCategory()">
                            Save Category &amp; Select
                        </button>
                        <button type="button"
                                class="btn btn-outline"
                                style="font-size:0.8rem;padding:6px 14px;margin-left:6px;"
                                onclick="cancelNewCategory()">
                            Cancel
                        </button>
                        <div id="newCatError"
                             style="color:#dc2626;font-size:0.78rem;margin-top:6px;display:none;"></div>
                    </div>
                </div>

                {{-- ── Display Order ─────────────────────────────────────── --}}
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
                           value="{{ old('display_order', $nextOrder) }}"
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
                        ⚠ This order number is already used in the selected category.
                        The server will reject it — please choose a different number.
                    </div>
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        Lower numbers appear first on payslips. Must be unique within the selected category.
                        Current highest: {{ $nextOrder - 1 }}.
                    </div>
                </div>

                {{-- ── Notes ────────────────────────────────────────────── --}}
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
                              placeholder="e.g. HDMF new loan program, fixed monthly amortization per employee."
                              style="resize:vertical;">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ── Info box ──────────────────────────────────────────── --}}
                <div class="alert alert-info" style="margin-bottom:20px;font-size:0.82rem;">
                    <strong>Note:</strong> New deduction types are always created as
                    <strong>manual</strong> (amount set per employee via enrollment).
                    To make a type auto-computed by the payroll engine, create it first,
                    then open Edit and switch its <em>Computation Mode</em> to
                    <em>Auto-computed (Locked)</em> — but only after adding its formula
                    to <code>DeductionService</code>.
                </div>

                {{-- ── Actions ──────────────────────────────────────────── --}}
                <div style="display:flex;gap:10px;align-items:center;">
                    <button type="submit"
                            id="submitBtn"
                            class="btn btn-primary">
                        Save Deduction Type
                    </button>
                    <a href="{{ route('deduction-types.index') }}" class="btn btn-outline">Cancel</a>
                    <span id="savingIndicator"
                          style="display:none;font-size:0.82rem;color:var(--text-mid);">
                        Saving…
                    </span>
                </div>

            </form>

        </div>
    </div>

</div>

@endsection

@section('scripts')
<script>
{{--
    FIX: existingOrders is passed from the controller as $existingOrders.
    Previously this data was fetched via a raw Eloquent call inside an @@json()
    Blade directive in this scripts section. Any PHP/DB exception there silently
    killed the entire script block, including the submit-listener setup, which is
    why the Save button appeared dead.

    NOTE: Do NOT write @json() inside JS block comments — Blade still compiles
    any @ directive it finds regardless of surrounding JS syntax.
--}}
const existingOrders = @json($existingOrders);

document.addEventListener('DOMContentLoaded', function () {

    // ── Form submit: loading state ──────────────────────────────────────────
    const form       = document.getElementById('createDeductionTypeForm');
    const submitBtn  = document.getElementById('submitBtn');
    const savingText = document.getElementById('savingIndicator');

    if (form) {
        form.addEventListener('submit', function () {
            submitBtn.disabled    = true;
            submitBtn.textContent = 'Saving…';
            if (savingText) savingText.style.display = 'inline';
        });
    }

    // ── Category change listener ────────────────────────────────────────────
    const categorySelect = document.getElementById('category');
    if (categorySelect) {
        categorySelect.addEventListener('change', checkOrderConflict);
    }

    // ── Order conflict check on load (in case old() repopulates values) ─────
    checkOrderConflict();
});

// ── Category: inline new-category panel ────────────────────────────────────

function handleCategoryChange(select) {
    const panel = document.getElementById('newCategoryPanel');
    if (select.value === '__new__') {
        panel.style.display = 'block';
        document.getElementById('new_cat_key').focus();
        // Reset select so HTML5 validation does not pass "__new__" to the server
        select.value = '';
    } else {
        panel.style.display = 'none';
        checkOrderConflict();
    }
}

function cancelNewCategory() {
    document.getElementById('newCategoryPanel').style.display = 'none';
    document.getElementById('category').value = '';
}

async function submitNewCategory() {
    const key   = document.getElementById('new_cat_key').value.trim();
    const label = document.getElementById('new_cat_label').value.trim();
    const errEl = document.getElementById('newCatError');

    if (! key || ! label) {
        errEl.textContent   = 'Both Key and Label are required.';
        errEl.style.display = 'block';
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                   || document.querySelector('input[name="_token"]')?.value;

    // FIX: use double quotes around the Blade route() call so the single
    // quotes inside route('...') do not terminate the JS string early.
    const storeUrl = "{{ route('deduction-categories.store') }}";

    try {
        const res = await fetch(storeUrl, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept':       'application/json',
            },
            body: JSON.stringify({
                key,
                label,
                display_order: parseInt(document.getElementById('display_order').value) || 99,
            }),
        });

        if (res.ok) {
            const select   = document.getElementById('category');
            const newOpt   = new Option(label, key, true, true);
            const newOptEl = select.querySelector('option[value="__new__"]');
            select.insertBefore(newOpt, newOptEl);
            select.value = key;

            document.getElementById('newCategoryPanel').style.display = 'none';
            document.getElementById('new_cat_key').value   = '';
            document.getElementById('new_cat_label').value = '';
            errEl.style.display = 'none';

            checkOrderConflict();
        } else {
            const body     = await res.json();
            const messages = body.errors
                ? Object.values(body.errors).flat().join(' ')
                : (body.message || 'Failed to create category.');
            errEl.textContent   = messages;
            errEl.style.display = 'block';
        }
    } catch (e) {
        errEl.textContent   = 'Network error. Please try again.';
        errEl.style.display = 'block';
    }
}

// ── Display order: client-side conflict warning ─────────────────────────────

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
</script>
@endsection
