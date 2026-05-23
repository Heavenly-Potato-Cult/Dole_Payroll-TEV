{{-- resources/views/special-payroll/differential-index.blade.php --}}
{{--
    Expects from SpecialPayrollController@differentialIndex:
      $batches     — paginated SpecialPayrollBatch (with employee), type='salary_differential'
      $currentYear — int
--}}

@extends('layouts.app')

@section('title', 'Salary Differential Records')
@section('page-title', 'Special Payroll')

@section('styles')
<style>
/* ─────────────────────────────────────────────────────
   FILTER FORM — buttons match input/select height
───────────────────────────────────────────────────── */
.filter-form {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.filter-form .ff-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.filter-form .ff-group label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--text-mid);
    line-height: 1;
    margin: 0;
}
.filter-form input,
.filter-form select {
    height: 38px;
    margin-bottom: 0 !important;
    box-sizing: border-box;
}
.filter-form .ff-btns {
    display: flex;
    gap: 8px;
    align-items: center;
    height: 38px;
}
.filter-form .ff-btns .btn,
.filter-form .ff-btns .btn-sm {
    height: 38px;
    padding-top: 0;
    padding-bottom: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    white-space: nowrap;
}

/* ─────────────────────────────────────────────────────
   RESPONSIVE TABLE
───────────────────────────────────────────────────── */
.sd-detail-row { display: none !important; }
.sd-expand-btn { display: none !important; }

/* ── DESKTOP (≥ 769px) ── */
@media (min-width: 769px) {
    .sd-table              { display: table; width: 100%; border-collapse: collapse; }
    .sd-table thead        { display: table-header-group; }
    .sd-table tbody        { display: table-row-group; }
    .sd-table tr           { display: table-row; }
    .sd-table th,
    .sd-table td           { display: table-cell; }
}

/* ── MOBILE (≤ 768px) ── */
@media (max-width: 768px) {

    .filter-form              { flex-direction: column; align-items: stretch; }
    .filter-form .ff-group,
    .filter-form .ff-btns     { width: 100%; }
    .filter-form .ff-btns     { height: auto; }
    .filter-form .ff-btns .btn,
    .filter-form .ff-btns .btn-sm { flex: 1; }

    .table-wrap { overflow: visible; }

    .sd-table        { display: block; }
    .sd-table thead  { display: none; }
    .sd-table tbody  { display: block; }

    /* Card row */
    .sd-table tr.sd-main-row {
        display: flex;
        align-items: center;
        gap: 0;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        cursor: pointer;
        transition: background .15s;
        min-height: 64px;
    }
    .sd-table tr.sd-main-row:active { background: var(--bg); }

    /* Hide columns moved to detail panel */
    .sd-table tr.sd-main-row td.col-position,
    .sd-table tr.sd-main-row td.col-period,
    .sd-table tr.sd-main-row td.col-old-rate,
    .sd-table tr.sd-main-row td.col-new-rate,
    .sd-table tr.sd-main-row td.col-diff,
    .sd-table tr.sd-main-row td.col-year,
    .sd-table tr.sd-main-row td.col-gross,
    .sd-table tr.sd-main-row td.col-deductions,
    .sd-table tr.sd-main-row td.col-net,
    .sd-table tr.sd-main-row td.col-actions {
        display: none;
    }

    /* Employee name — takes all space */
    .sd-table tr.sd-main-row td.col-employee {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 3px;
        padding: 0;
        min-width: 0;
    }
    .sd-table tr.sd-main-row td.col-employee .sd-name-label {
        font-weight: 700;
        font-size: 0.92rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sd-table tr.sd-main-row td.col-employee .sd-name-sub {
        font-size: 0.74rem;
        color: var(--text-mid);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Status badge */
    .sd-table tr.sd-main-row td.col-status {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        padding: 0 10px;
    }

    /* Expand button */
    .sd-expand-btn {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        flex-shrink: 0;
        border-radius: 50%;
        background: transparent;
        border: 1.5px solid var(--border);
        cursor: pointer;
        font-size: 0.65rem;
        color: var(--text-mid);
        transition: transform .2s, background .15s, border-color .15s;
        margin-left: 6px;
    }
    .sd-main-row.open .sd-expand-btn {
        transform: rotate(180deg);
        background: var(--navy-light, #e8ecf4);
        border-color: var(--navy);
        color: var(--navy);
    }

    /* Expanded detail panel */
    tr.sd-detail-row.open {
        display: block !important;
        border-bottom: 1px solid var(--border);
        background: var(--bg, #f8f9fb);
    }
    tr.sd-detail-row.open td {
        display: block;
        padding: 12px 16px 16px;
    }
    .sd-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 20px;
        margin-bottom: 14px;
    }
    .sd-detail-item label {
        display: block;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-light);
        margin-bottom: 3px;
    }
    .sd-detail-item span {
        font-size: 0.85rem;
        color: var(--text);
        font-weight: 500;
    }
    .sd-detail-item span.mono { font-family: monospace; }
    .sd-detail-actions {
        display: flex;
        gap: 8px;
    }
    .sd-detail-actions .btn,
    .sd-detail-actions button {
        flex: 1;
        justify-content: center;
        text-align: center;
    }
}
</style>
@endsection

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Salary Differential</h1>
        <p>Payroll records for promotions, step increments, and salary adjustments.</p>
    </div>
@can(\App\SharedKernel\Enums\Permission::PAYROLL_SPECIAL_MANAGE->value)
    <a href="{{ route('special-payroll.differential.create') }}" class="btn btn-primary">
        + New Entry
    </a>
@endcan
</div>

{{-- ── Filter bar ── --}}
<div class="card mb-3">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" action="{{ route('special-payroll.differential.index') }}" class="filter-form">

            <div class="ff-group" style="min-width:120px;">
                <label for="year">Year</label>
                <select name="year" id="year">
                    <option value="">All Years</option>
                    @foreach (range($currentYear, $currentYear - 3) as $y)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="ff-group" style="min-width:160px;">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">All Statuses</option>
                    <option value="draft"    {{ request('status') === 'draft'    ? 'selected' : '' }}>Draft</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="released" {{ request('status') === 'released' ? 'selected' : '' }}>Released</option>
                </select>
            </div>

            <div class="ff-btns">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('special-payroll.differential.index') }}" class="btn btn-outline btn-sm">Reset</a>
            </div>

        </form>
    </div>
</div>

{{-- ── Table ── --}}
<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table class="sd-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Effectivity Period</th>
                        <th class="text-right">Old Rate</th>
                        <th class="text-right">New Rate</th>
                        <th class="text-right">Differential</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th class="text-right">Total Earned</th>
                        <th class="text-right">Deductions</th>
                        <th class="text-right">Net Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        @php
                            $emp = $batch->employee;

                            $statusClass = match ($batch->status) {
                                'approved' => 'badge-released',
                                'released' => 'badge-locked',
                                default    => 'badge-draft',
                            };
                            $statusLabel = match ($batch->status) {
                                'draft'    => 'Draft',
                                'approved' => 'Approved',
                                'released' => 'Released',
                                default    => ucfirst($batch->status),
                            };
                        @endphp

                        {{-- ── Main visible row ── --}}
                        <tr class="sd-main-row" data-id="{{ $batch->id }}" onclick="toggleSdRow(this)">

                            <td class="col-employee">
                                <span class="sd-name-label">
                                    {{ optional($emp)->last_name }},
                                    {{ optional($emp)->first_name }}
                                    @if (optional($emp)->middle_name)
                                        {{ substr($emp->middle_name, 0, 1) }}.
                                    @endif
                                </span>

                            </td>

                            <td class="col-position text-muted" style="font-size:0.82rem;">
                                {{ optional($emp)->position_title ?? '—' }}
                            </td>

                            <td class="col-period text-muted" style="font-size:0.82rem;">
                                @if ($batch->period_start && $batch->period_end)
                                    {{ $batch->period_start->format('M d, Y') }}
                                    –
                                    {{ $batch->period_end->format('M d, Y') }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="col-old-rate text-right">
                                ₱{{ number_format($batch->old_basic_salary, 2) }}
                            </td>

                            <td class="col-new-rate text-right">
                                ₱{{ number_format($batch->new_basic_salary, 2) }}
                            </td>

                            <td class="col-diff text-right fw-bold" style="color:var(--navy);">
                                ₱{{ number_format($batch->differential_amount, 2) }}
                            </td>

                            <td class="col-year">{{ $batch->year }}</td>

                            <td class="col-status">
                                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>

                            <td class="col-gross text-right">
                                ₱{{ number_format($batch->gross_amount, 2) }}
                            </td>

                            <td class="col-deductions text-right" style="color:#B71C1C;">
                                ₱{{ number_format($batch->deductions_amount, 2) }}
                            </td>

                            <td class="col-net text-right fw-bold" style="color:#1B5E20;">
                                ₱{{ number_format($batch->net_amount, 2) }}
                            </td>

                            <td class="col-actions">
                                <div class="d-flex gap-2" style="justify-content:center;">
                                    <a href="{{ route('special-payroll.differential.show', $batch->id) }}"
                                       class="btn btn-outline btn-sm"
                                       onclick="event.stopPropagation();">View</a>

                                    @if ($batch->status === 'draft' && auth()->user()->can(\App\SharedKernel\Enums\Permission::PAYROLL_DELETE_DRAFT->value))
                                        <form id="deleteForm-{{ $batch->id }}" method="POST"
                                              action="{{ route('special-payroll.differential.destroy', $batch->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm"
                                                    style="background:#B71C1C; color:#fff; border:none; cursor:pointer;"
                                                    onclick="event.stopPropagation(); confirmDeleteDifferential({{ $batch->id }}, '{{ addslashes(optional($emp)->last_name ?? '') }}')">
                                                ✕
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                {{-- Mobile expand chevron --}}
                                <span class="sd-expand-btn" aria-label="Expand">▼</span>
                            </td>

                        </tr>

                        {{-- ── Expandable detail row (mobile only) ── --}}
                        <tr class="sd-detail-row" id="sd-detail-{{ $batch->id }}">
                            <td colspan="12">
                                <div class="sd-detail-grid">
                                    <div class="sd-detail-item">
                                        <label>Position</label>
                                        <span>{{ optional($emp)->position_title ?? '—' }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Year</label>
                                        <span>{{ $batch->year }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Effectivity Period</label>
                                        <span>
                                            @if ($batch->period_start && $batch->period_end)
                                                {{ $batch->period_start->format('M d, Y') }} – {{ $batch->period_end->format('M d, Y') }}
                                            @else
                                                —
                                            @endif
                                        </span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Status</label>
                                        <span><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Old Rate</label>
                                        <span class="mono">₱{{ number_format($batch->old_basic_salary, 2) }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>New Rate</label>
                                        <span class="mono">₱{{ number_format($batch->new_basic_salary, 2) }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Differential</label>
                                        <span class="mono" style="color:var(--navy); font-weight:700;">₱{{ number_format($batch->differential_amount, 2) }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Total Earned</label>
                                        <span class="mono">₱{{ number_format($batch->gross_amount, 2) }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Deductions</label>
                                        <span class="mono" style="color:#B71C1C;">₱{{ number_format($batch->deductions_amount, 2) }}</span>
                                    </div>
                                    <div class="sd-detail-item">
                                        <label>Net Amount</label>
                                        <span class="mono" style="color:#1B5E20; font-weight:700;">₱{{ number_format($batch->net_amount, 2) }}</span>
                                    </div>
                                </div>
                                <div class="sd-detail-actions">
                                    <a href="{{ route('special-payroll.differential.show', $batch->id) }}"
                                       class="btn btn-outline btn-sm">View</a>
                                    @if ($batch->status === 'draft' && auth()->user()->can(\App\SharedKernel\Enums\Permission::PAYROLL_DELETE_DRAFT->value))
                                        <form id="deleteFormMobile-{{ $batch->id }}" method="POST"
                                              action="{{ route('special-payroll.differential.destroy', $batch->id) }}"
                                              style="flex:1;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm"
                                                    style="background:#B71C1C; color:#fff; border:none; cursor:pointer; width:100%;"
                                                    onclick="confirmDeleteDifferential({{ $batch->id }}, '{{ addslashes(optional($emp)->last_name ?? '') }}', true)">
                                                ✕ Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="12" style="text-align:center; padding:40px; color:var(--text-light);">
                                No records found.
@if (auth()->user()->can(\App\SharedKernel\Enums\Permission::PAYROLL_SPECIAL_MANAGE->value))
    <a href="{{ route('special-payroll.differential.create') }}">
        Create one now →
    </a>
@endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div style="margin-top:12px;">{{ $batches->links() }}</div>

@endsection

@section('scripts')
<script>
function toggleSdRow(mainRow) {
    if (window.innerWidth > 768) return;

    const id     = mainRow.dataset.id;
    const detail = document.getElementById('sd-detail-' + id);
    const isOpen = mainRow.classList.contains('open');

    document.querySelectorAll('.sd-main-row.open').forEach(r => r.classList.remove('open'));
    document.querySelectorAll('.sd-detail-row.open').forEach(r => r.classList.remove('open'));

    if (!isOpen) {
        mainRow.classList.add('open');
        detail.classList.add('open');
    }
}

function confirmDeleteDifferential(batchId, employeeName, isMobile = false) {
    const formId = isMobile ? 'deleteFormMobile-' + batchId : 'deleteForm-' + batchId;
    Swal.fire({
        title: 'Delete Salary Differential?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.2rem;font-weight:600;color:#dc3545;margin-bottom:8px;">${employeeName || 'Unknown'}</div>
            <p style="color:#6b7280;font-size:0.95rem;">This will permanently delete this draft salary differential record and cannot be undone.</p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete Record',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById(formId);
            if (form) {
                const buttons = form.querySelectorAll('button');
                buttons.forEach(btn => {
                    btn.disabled = true;
                    btn.textContent = 'Deleting...';
                });
                form.submit();
            }
        }
    });
}
</script>
@endsection
