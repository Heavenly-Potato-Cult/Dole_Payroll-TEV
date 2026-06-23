@extends('layouts.app')

@section('title', 'New Allowance Batch')
@section('page-title', 'Allowances')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>New Allowance Batch</h1>
        <p>Assign one or more allowance lines to employees for a specific period.</p>
    </div>
    <a href="{{ route('payroll.allowances.index') }}" class="btn btn-outline">← Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('payroll.allowances.batches.store') }}" id="batchForm">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px;">
                <div>
                    <label>Year</label>
                    <input type="number" name="period_year" value="{{ old('period_year', now()->year) }}" required>
                </div>
                <div>
                    <label>Month</label>
                    <select name="period_month" required>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected(old('period_month', now()->month) == $m)>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label>Cutoff</label>
                    <select name="cutoff" required>
                        @foreach (['monthly','1st','2nd'] as $cutoff)
                            <option value="{{ $cutoff }}" @selected(old('cutoff') === $cutoff)>{{ ucfirst($cutoff) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Period Start</label>
                    <input type="date" name="period_start" value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}" required>
                </div>
                <div>
                    <label>Period End</label>
                    <input type="date" name="period_end" value="{{ old('period_end', now()->endOfMonth()->toDateString()) }}" required>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label>Remarks</label>
                <textarea name="remarks" rows="2">{{ old('remarks') }}</textarea>
            </div>

            <h3 style="margin-bottom:12px;">Entries</h3>
            <div id="entryRows">
                <div class="entry-row" style="display:grid;grid-template-columns:2fr 1fr 120px 1fr auto;gap:10px;margin-bottom:10px;align-items:end;">
                    <div>
                        <label>Employee</label>
                        <select name="entries[0][employee_id]" required>
                            <option value="">Select employee</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->last_name }}, {{ $emp->first_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Allowance Type</label>
                        <select name="entries[0][allowance_type_id]" required>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Amount</label>
                        <input type="number" step="0.01" min="0" name="entries[0][amount]" required>
                    </div>
                    <div>
                        <label>Remarks</label>
                        <input type="text" name="entries[0][remarks]">
                    </div>
                    <button type="button" class="btn btn-outline btn-sm remove-row" disabled>Remove</button>
                </div>
            </div>

            <button type="button" class="btn btn-outline btn-sm" id="addRow" style="margin-bottom:20px;">+ Add Row</button>

            <button type="submit" class="btn btn-primary">Create Batch</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('addRow').addEventListener('click', function () {
    const container = document.getElementById('entryRows');
    const index = container.querySelectorAll('.entry-row').length;
    const first = container.querySelector('.entry-row');
    const clone = first.cloneNode(true);
    clone.querySelectorAll('select, input').forEach(el => {
        el.name = el.name.replace(/\[\d+\]/, `[${index}]`);
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
    });
    clone.querySelector('.remove-row').disabled = false;
    container.appendChild(clone);
});

document.getElementById('entryRows').addEventListener('click', function (e) {
    if (!e.target.classList.contains('remove-row')) return;
    const rows = this.querySelectorAll('.entry-row');
    if (rows.length <= 1) return;
    e.target.closest('.entry-row').remove();
});
</script>
@endsection
