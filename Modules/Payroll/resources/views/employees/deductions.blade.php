@extends('layouts.app')

@section('title', 'Deductions — ' . $employee->full_name)
@section('page-title', 'Employee Deductions')

@section('styles')
<style>
.deductions-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
.deductions-col    { display: flex; flex-direction: column; gap: 20px; }

@media (max-width: 800px) {
    .deductions-layout { grid-template-columns: 1fr; }
}
</style>
@endsection

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Deductions</h1>
        <p>
            {{ $employee->full_name }} &mdash;
            {{ $employee->position_title }}
            @if($employee->division)
                &mdash; <strong>{{ $employee->division->code }}</strong>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline">← Profile</a>
        <a href="{{ route('employees.index') }}" class="btn btn-outline">← All Employees</a>
    </div>
</div>

<div class="alert alert-info" style="margin-bottom:20px;">
    <div style="display:flex;flex-direction:column;gap:6px;">
        <div>
            <strong>Three types of deductions:</strong>
        </div>
        <div style="display:flex;gap:10px;align-items:flex-start;font-size:0.85rem;">
            <span>⚙️</span>
            <span><strong>Auto-computed</strong> — PhilHealth, GSIS Life/Ret, PAG-IBIG I, W/Holding Tax.
            Calculated automatically based on the employee's salary. You cannot set amounts here.</span>
        </div>
        <div style="display:flex;gap:10px;align-items:flex-start;font-size:0.85rem;">
            <span>🔒</span>
            <span><strong>Global Fixed</strong> — A single amount is set at the deduction type level and
            applied uniformly to all employees. Edit the amount under
            <a href="{{ route('deduction-types.index') }}">Deduction Types</a>.</span>
        </div>
        <div style="display:flex;gap:10px;align-items:flex-start;font-size:0.85rem;">
            <span>☑</span>
            <span><strong>Per-employee</strong> — Tick the checkbox to enroll this employee and enter
            the amount per cut-off. Loans and variable deductions fall here. Loan-category types
            (PAG-IBIG Calamity Loan, MPL, CARESS IX, etc.) support up to 3 separate accounts each.
            All amounts shown are <strong>per cut-off</strong> (already halved).</span>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('employees.deductions.update', $employee) }}">
@csrf

<div class="deductions-layout">

    @php
        $grouped = $deductionTypes->groupBy('category');
        $categoryLabels = [
            'pagibig'    => 'PAG-IBIG / HDMF',
            'philhealth' => 'PhilHealth',
            'gsis'       => 'GSIS',
            'other_gov'  => 'Government / Tax',
            'other'      => 'Other',
            'loan'       => 'Bank Loans',
            'caress'     => 'CARESS IX',
            'misc'       => 'Miscellaneous',
        ];
        $leftCategories  = ['pagibig', 'philhealth', 'gsis'];
        $rightCategories = ['other_gov', 'other', 'loan', 'caress', 'misc'];
    @endphp

    {{-- Left column --}}
    <div class="deductions-col">
        @foreach ($leftCategories as $cat)
            @if (isset($grouped[$cat]))
                @include('payroll::employees._deduction_category', [
                    'label'           => $categoryLabels[$cat] ?? $cat,
                    'types'           => $grouped[$cat],
                    'enrollments'     => $enrollments,
                    'loanEnrollments' => $loanEnrollments,
                    'employee'        => $employee,
                ])
            @endif
        @endforeach
    </div>

    {{-- Right column --}}
    <div class="deductions-col">
        @foreach ($rightCategories as $cat)
            @if (isset($grouped[$cat]))
                @include('payroll::employees._deduction_category', [
                    'label'           => $categoryLabels[$cat] ?? $cat,
                    'types'           => $grouped[$cat],
                    'enrollments'     => $enrollments,
                    'loanEnrollments' => $loanEnrollments,
                    'employee'        => $employee,
                ])
            @endif
        @endforeach

        {{-- Summary card — manual/per-employee totals only --}}
        <div class="card" id="deductionSummaryCard">
            <div class="card-header"><h3>Per-Employee Deduction Summary</h3></div>
            <div class="card-body">
                <div style="font-size:0.85rem;color:var(--text-mid);margin-bottom:12px;">
                    Shows only <strong>per-employee (Tier 3)</strong> deductions that are currently checked,
                    including all accounts for multi-account loan types.
                    Auto-computed and Global Fixed deductions are excluded here — they are resolved
                    automatically during the payroll run.
                </div>
                <div style="display:flex;justify-content:space-between;
                            padding:10px 0;border-top:2px solid var(--navy);
                            font-weight:700;font-size:1.05rem;color:var(--navy);">
                    <span>Total (Per-Employee)</span>
                    <span id="totalDeductionsDisplay">₱0.00</span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div style="display:flex;flex-direction:column;gap:10px;">
            <button type="submit" class="btn btn-primary btn-lg w-100">✓ Save Deductions</button>
            <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline w-100">Cancel</a>
        </div>
    </div>

</div>
</form>

@endsection

@section('scripts')
<script>
// Sums every checked deduction row, including all account slots within
// multi-account loan rows (each slot's amount input shares the
// .deduction-amount class).
function recalcTotal() {
    let total = 0;
    document.querySelectorAll('.deduction-row').forEach(function (row) {
        const cb = row.querySelector('.deduction-checkbox');
        if (cb && cb.checked) {
            row.querySelectorAll('.deduction-amount').forEach(function (amt) {
                const val = parseFloat(amt.value.replace(/,/g, '')) || 0;
                total += val;
            });
        }
    });
    document.getElementById('totalDeductionsDisplay').textContent =
        '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Renumbers "Account N" labels, shows/hides the × remove button on the
// first slot, and shows/hides the + add button once the max is reached.
function renumberAccountSlots(wrapper) {
    const slots = wrapper.querySelectorAll('.loan-account-slot');
    const maxAccounts = parseInt(wrapper.dataset.maxAccounts || '3', 10);

    slots.forEach(function (slot, i) {
        const label = slot.querySelector('.loan-account-slot-label');
        if (label) label.textContent = 'Account ' + (i + 1);

        const removeBtn = slot.querySelector('.btn-remove-account');
        if (removeBtn) removeBtn.style.display = (i === 0) ? 'none' : 'inline-block';
    });

    const addBtn = wrapper.querySelector('.btn-add-account');
    if (addBtn) {
        addBtn.style.display = slots.length >= maxAccounts ? 'none' : 'inline-block';
    }
}

// Clones the first slot in a loan-accounts wrapper, clears its values,
// and rewrites its field names with a fresh unique index.
function addAccountSlot(wrapper) {
    const slotsContainer = wrapper.querySelector('.loan-account-slots');
    const slots = slotsContainer.querySelectorAll('.loan-account-slot');
    const maxAccounts = parseInt(wrapper.dataset.maxAccounts || '3', 10);

    if (slots.length >= maxAccounts) return;

    const template = slots[0];
    const clone = template.cloneNode(true);
    const newIndex = Date.now();

    clone.querySelectorAll('input').forEach(function (input) {
        input.name = input.name.replace(/\[accounts\]\[[^\]]+\]/, '[accounts][' + newIndex + ']');

        if (input.type === 'date' && input.name.endsWith('[effective_from]')) {
            // Default new slots to the start of the current month, same as a fresh enrollment.
            const today = new Date();
            input.value = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-01';
        } else {
            input.value = '';
        }
    });

    slotsContainer.appendChild(clone);
    renumberAccountSlots(wrapper);
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.deduction-checkbox').forEach(function (cb) {
        cb.addEventListener('change', function () {
            const row    = this.closest('.deduction-row');
            const amtRow = row.querySelector('.deduction-amount-row');
            if (amtRow) {
                amtRow.style.display = this.checked ? 'block' : 'none';

                // Pre-fill with the default if the (first) amount field is empty.
                // Only meaningful for non-loan Tier 3 rows, which carry a real placeholder.
                if (this.checked) {
                    const amtInput = amtRow.querySelector('.deduction-amount');
                    if (amtInput && (!amtInput.value || amtInput.value === '0')) {
                        const placeholder = amtInput.getAttribute('placeholder');
                        if (placeholder && placeholder !== '0.00') {
                            amtInput.value = placeholder;
                        }
                    }
                }
            }
            recalcTotal();
        });
    });

    // Note: no per-element 'input' listener here — the delegated listener
    // below covers both these existing inputs and any cloned account slots,
    // since cloneNode() does not carry JS event listeners with it.

    // Initial pass to correctly hide/show + buttons for types already at the max.
    document.querySelectorAll('.loan-accounts-wrapper').forEach(function (wrapper) {
        renumberAccountSlots(wrapper);
    });

    // Event delegation: account slots are added/removed dynamically, so these
    // listeners live on the document rather than on the buttons themselves.
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-add-account')) {
            const wrapper = e.target.closest('.loan-accounts-wrapper');
            addAccountSlot(wrapper);
        }

        if (e.target.classList.contains('btn-remove-account')) {
            const wrapper = e.target.closest('.loan-accounts-wrapper');
            const slot = e.target.closest('.loan-account-slot');
            slot.remove();
            renumberAccountSlots(wrapper);
            recalcTotal();
        }
    });

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('deduction-amount')) {
            recalcTotal();
        }
    });

    recalcTotal();
});
</script>
@endsection
