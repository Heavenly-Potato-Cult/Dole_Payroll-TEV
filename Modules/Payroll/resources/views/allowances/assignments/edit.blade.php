@extends('layouts.app')

@section('title', 'Edit Allowance Assignment')
@section('page-title', 'Allowances')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Allowance Assignment</h1>
    </div>
    <a href="{{ route('payroll.allowances.assignments.show', $assignment) }}" class="btn btn-outline">← Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('payroll.allowances.assignments.update', $assignment) }}" id="assignmentForm">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px;">
                <div>
                    <label>Year</label>
                    <input type="number" name="period_year" value="{{ old('period_year', $assignment->period_year) }}" required>
                </div>
                <div>
                    <label>Month</label>
                    <select name="period_month" required>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected(old('period_month', $assignment->period_month) == $m)>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label>Cutoff</label>
                    <select name="cutoff" required>
                        @foreach (['monthly','1st','2nd'] as $cutoff)
                            <option value="{{ $cutoff }}" @selected(old('cutoff', $assignment->cutoff) === $cutoff)>{{ ucfirst($cutoff) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Period Start</label>
                    <input type="date" name="period_start" value="{{ old('period_start', $assignment->period_start->toDateString()) }}" required>
                </div>
                <div>
                    <label>Period End <span style="color:#6b7280;font-weight:400;font-size:0.85em;">(optional)</span></label>
                    <input type="date" name="period_end" value="{{ old('period_end', $assignment->period_end?->toDateString()) }}">
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label>Remarks</label>
                <textarea name="remarks" rows="2">{{ old('remarks', $assignment->remarks) }}</textarea>
            </div>

            <h3 style="margin-bottom:8px;">Bulk Add</h3>
            <div class="card" style="background:#f8f9fb;border:1px solid #e2e5ea;margin-bottom:20px;">
                <div class="card-body" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end;">
                    <div>
                        <label>Allowance Type</label>
                        <select id="bulkType">
                            <option value="">Select type</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Amount</label>
                        <input type="number" step="0.01" min="0" id="bulkAmount">
                    </div>
                    <div>
                        <label>Remarks (optional)</label>
                        <input type="text" id="bulkRemarks">
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" id="bulkAddBtn">+ Add All Active Employees</button>
                </div>
                <div class="card-body" style="padding-top:0;">
                    <small style="color:#6b7280;">
                        Adds one row per active employee for the selected allowance type.
                        Employees who already have that type in this assignment, or who already have an active
                        standing allowance for that type, are skipped automatically.
                    </small>
                </div>
            </div>

            <h3 style="margin-bottom:12px;">Entries</h3>
            <div id="duplicateWarning" class="alert alert-warning" style="display:none;margin-bottom:12px;">
                Some employees have the same allowance type added more than once. Remove or fix the highlighted rows before saving.
            </div>
            <div id="entryRows"></div>

            <button type="button" class="btn btn-outline btn-sm" id="addRow" style="margin-bottom:20px;">+ Add Row</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

{{-- =====================================================================
     Reusable info modal — triggered by showModal(title, bodyHtml, type)
     type: 'info' | 'warning' | 'error'
     ===================================================================== --}}
<div id="appModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
    {{-- backdrop --}}
    <div id="appModalBackdrop" style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>
    {{-- panel --}}
    <div id="appModalPanel" style="position:relative;background:#fff;border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,.18);width:100%;max-width:480px;margin:16px;overflow:hidden;">
        <div id="appModalHeader" style="display:flex;align-items:center;gap:10px;padding:16px 20px;border-bottom:1px solid #e5e7eb;">
            <span id="appModalIcon" style="font-size:1.25rem;line-height:1;"></span>
            <h4 id="appModalTitle" style="margin:0;font-size:1rem;font-weight:600;color:#111827;flex:1;"></h4>
            <button id="appModalClose" type="button" style="background:none;border:none;cursor:pointer;color:#6b7280;font-size:1.25rem;line-height:1;padding:0;">&times;</button>
        </div>
        <div id="appModalBody" style="padding:16px 20px;max-height:60vh;overflow-y:auto;"></div>
        <div style="padding:12px 20px;border-top:1px solid #e5e7eb;text-align:right;">
            <button id="appModalOk" type="button" class="btn btn-primary btn-sm">OK</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
.duplicate-row { outline: 2px solid #dc2626; border-radius: 6px; padding: 6px; }
.alert.alert-warning { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; padding:10px 14px; border-radius:6px; }

/* Modal type colour tokens */
#appModal[data-type="info"]    #appModalHeader { background:#eff6ff; }
#appModal[data-type="info"]    #appModalIcon::before { content:'ℹ️'; }
#appModal[data-type="warning"] #appModalHeader { background:#fffbeb; }
#appModal[data-type="warning"] #appModalIcon::before { content:'⚠️'; }
#appModal[data-type="error"]   #appModalHeader { background:#fef2f2; }
#appModal[data-type="error"]   #appModalIcon::before { content:'🚫'; }

/* Stat pills inside the modal */
.modal-stat {
    display:flex;align-items:center;gap:10px;
    padding:10px 14px;border-radius:7px;margin-bottom:8px;
    font-size:.9rem;
}
.modal-stat.added    { background:#f0fdf4;border:1px solid #bbf7d0;color:#166534; }
.modal-stat.skipped  { background:#fffbeb;border:1px solid #fde68a;color:#92400e; }
.modal-stat.standing { background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af; }
.modal-stat .stat-num { font-size:1.4rem;font-weight:700;min-width:36px;text-align:center; }
.modal-note { font-size:.8rem;color:#6b7280;margin-top:10px;line-height:1.5; }
</style>
<script>
// ─── Modal helper ─────────────────────────────────────────────────────────────
const modal         = document.getElementById('appModal');
const modalTitle    = document.getElementById('appModalTitle');
const modalBody     = document.getElementById('appModalBody');
const modalClose    = document.getElementById('appModalClose');
const modalOk       = document.getElementById('appModalOk');
const modalBackdrop = document.getElementById('appModalBackdrop');

function showModal(title, bodyHtml, type = 'info') {
    modal.setAttribute('data-type', type);
    modalTitle.textContent = title;
    modalBody.innerHTML    = bodyHtml;
    modal.style.display    = 'flex';
    modalOk.focus();
}

function closeModal() {
    modal.style.display = 'none';
}

modalClose.addEventListener('click', closeModal);
modalOk.addEventListener('click', closeModal);
modalBackdrop.addEventListener('click', closeModal);
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });

// ─── Page data ────────────────────────────────────────────────────────────────
const EMPLOYEES   = @json($employees->map(function ($e) { return ['id' => $e->id, 'name' => $e->last_name . ', ' . $e->first_name]; })->values()->toArray());
const TYPES       = @json($types->map(function ($t) { return ['id' => $t->id, 'name' => $t->name]; })->values()->toArray());
@php
    $oldEntriesData = old('entries', $assignment->entries->map(function ($e) {
        return [
            'employee_id'       => $e->employee_id,
            'allowance_type_id' => $e->allowance_type_id,
            'amount'            => $e->amount,
            'remarks'           => $e->remarks,
        ];
    })->values()->toArray());
@endphp
const OLD_ENTRIES = @json($oldEntriesData);

/**
 * A Set of "employee_id-allowance_type_id" strings for every active standing
 * (recurring) allowance record. The bulk-add button uses this to skip
 * employees already covered by a standing allowance for the chosen type.
 *
 * A manually-added row for such an employee is still allowed — the batch
 * entry will simply override the standing amount for that period.
 */
const STANDING_PAIRS = new Set(@json($standingPairs));

const entryRows = document.getElementById('entryRows');
let rowIndex = 0;

// ─── Row builder ──────────────────────────────────────────────────────────────
function employeeOptions(selectedId) {
    let html = '<option value="">Select employee</option>';
    EMPLOYEES.forEach(emp => {
        html += `<option value="${emp.id}" ${String(selectedId) === String(emp.id) ? 'selected' : ''}>${emp.name}</option>`;
    });
    return html;
}

function typeOptions(selectedId) {
    let html = '<option value="">Select type</option>';
    TYPES.forEach(type => {
        html += `<option value="${type.id}" ${String(selectedId) === String(type.id) ? 'selected' : ''}>${type.name}</option>`;
    });
    return html;
}

function addRow({ employeeId = '', typeId = '', amount = '', remarks = '' } = {}) {
    const index = rowIndex++;
    const row   = document.createElement('div');
    row.className     = 'entry-row';
    row.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 120px 1fr auto;gap:10px;margin-bottom:10px;align-items:end;';
    row.innerHTML = `
        <div>
            <label>Employee</label>
            <select name="entries[${index}][employee_id]" class="emp-select" required>${employeeOptions(employeeId)}</select>
        </div>
        <div>
            <label>Allowance Type</label>
            <select name="entries[${index}][allowance_type_id]" class="type-select" required>${typeOptions(typeId)}</select>
        </div>
        <div>
            <label>Amount</label>
            <input type="number" step="0.01" min="0" name="entries[${index}][amount]" value="${amount}" required>
        </div>
        <div>
            <label>Remarks</label>
            <input type="text" name="entries[${index}][remarks]" value="${remarks ?? ''}">
        </div>
        <button type="button" class="btn btn-outline btn-sm remove-row">Remove</button>
    `;
    entryRows.appendChild(row);
    updateRemoveButtons();
    checkDuplicates();
    return row;
}

function updateRemoveButtons() {
    const rows = entryRows.querySelectorAll('.entry-row');
    rows.forEach(row => {
        row.querySelector('.remove-row').disabled = rows.length <= 1;
    });
}

// ─── Duplicate detection ──────────────────────────────────────────────────────
function checkDuplicates() {
    const rows = Array.from(entryRows.querySelectorAll('.entry-row'));
    const seen = new Map();
    rows.forEach(row => row.classList.remove('duplicate-row'));

    rows.forEach(row => {
        const empVal  = row.querySelector('.emp-select').value;
        const typeVal = row.querySelector('.type-select').value;
        if (!empVal || !typeVal) return;
        const key = empVal + '-' + typeVal;
        if (!seen.has(key)) seen.set(key, []);
        seen.get(key).push(row);
    });

    let hasDuplicates = false;
    seen.forEach(matchingRows => {
        if (matchingRows.length > 1) {
            hasDuplicates = true;
            matchingRows.forEach(row => row.classList.add('duplicate-row'));
        }
    });

    document.getElementById('duplicateWarning').style.display = hasDuplicates ? 'block' : 'none';
    return hasDuplicates;
}

// ─── Row events ───────────────────────────────────────────────────────────────
document.getElementById('addRow').addEventListener('click', () => addRow());

entryRows.addEventListener('click', function (e) {
    if (!e.target.classList.contains('remove-row')) return;
    const rows = this.querySelectorAll('.entry-row');
    if (rows.length <= 1) return;
    e.target.closest('.entry-row').remove();
    checkDuplicates();
});

entryRows.addEventListener('change', function (e) {
    if (e.target.classList.contains('emp-select') || e.target.classList.contains('type-select')) {
        checkDuplicates();
    }
});

// ─── Bulk add ─────────────────────────────────────────────────────────────────
document.getElementById('bulkAddBtn').addEventListener('click', function () {
    const typeId  = document.getElementById('bulkType').value;
    const amount  = document.getElementById('bulkAmount').value;
    const remarks = document.getElementById('bulkRemarks').value;

    if (!typeId || amount === '') {
        showModal(
            'Missing information',
            '<p style="margin:0;color:#374151;">Please select an allowance type and enter an amount before adding all employees.</p>',
            'warning'
        );
        return;
    }

    // Drop the lone unfilled starter row so it doesn't linger as an invalid row.
    const existingRows = Array.from(entryRows.querySelectorAll('.entry-row'));
    if (existingRows.length === 1) {
        const r = existingRows[0];
        if (!r.querySelector('.emp-select').value && !r.querySelector('.type-select').value) {
            r.remove();
        }
    }

    // Build a set of employee-type pairs already present in the entry table.
    const existingPairs = new Set(
        Array.from(entryRows.querySelectorAll('.entry-row')).map(row =>
            row.querySelector('.emp-select').value + '-' + row.querySelector('.type-select').value
        )
    );

    let added           = 0;
    let skippedBatch    = 0;    // already in this batch's entry table
    let skippedStanding = 0;    // covered by an active standing allowance

    EMPLOYEES.forEach(emp => {
        const key = emp.id + '-' + typeId;
        if (existingPairs.has(key))  { skippedBatch++;    return; }
        if (STANDING_PAIRS.has(key)) { skippedStanding++; return; }
        addRow({ employeeId: emp.id, typeId, amount, remarks });
        added++;
    });

    // Build modal body with stat pills
    let bodyHtml = '';

    if (added > 0) {
        bodyHtml += `
            <div class="modal-stat added">
                <span class="stat-num">${added}</span>
                <span>employee(s) added to this assignment.</span>
            </div>`;
    }
    if (skippedBatch > 0) {
        bodyHtml += `
            <div class="modal-stat skipped">
                <span class="stat-num">${skippedBatch}</span>
                <span>skipped — already present in this assignment.</span>
            </div>`;
    }
    if (skippedStanding > 0) {
        bodyHtml += `
            <div class="modal-stat standing">
                <span class="stat-num">${skippedStanding}</span>
                <span>skipped — already have an active standing allowance for this type.</span>
            </div>`;
    }
    if (skippedStanding > 0) {
        bodyHtml += `<p class="modal-note">If you need to override a standing allowance for a specific employee this period, add them manually using the <strong>+ Add Row</strong> button below.</p>`;
    }
    if (added === 0 && skippedBatch === 0 && skippedStanding === 0) {
        bodyHtml = '<p style="margin:0;color:#374151;">No active employees found.</p>';
    }

    const type  = added > 0 ? 'info' : 'warning';
    const title = added > 0 ? 'Bulk Add Complete' : 'Nothing Added';
    showModal(title, bodyHtml, type);
});

// ─── Submit guard ─────────────────────────────────────────────────────────────
document.getElementById('assignmentForm').addEventListener('submit', function (e) {
    if (checkDuplicates()) {
        e.preventDefault();
        showModal(
            'Duplicate entries detected',
            '<p style="margin:0;color:#374151;">Please resolve duplicate employee / allowance type entries (highlighted in red) before submitting.</p>',
            'error'
        );
    }
});

// ─── Seed rows ────────────────────────────────────────────────────────────────
if (OLD_ENTRIES.length) {
    OLD_ENTRIES.forEach(e => addRow({
        employeeId: e.employee_id,
        typeId:     e.allowance_type_id,
        amount:     e.amount,
        remarks:    e.remarks,
    }));
} else {
    addRow();
}
</script>
@endsection
