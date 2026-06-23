@extends('layouts.app')

@section('title', 'Edit Allowance Batch')
@section('page-title', 'Allowances')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Allowance Batch</h1>
    </div>
    <a href="{{ route('payroll.allowances.batches.show', $batch) }}" class="btn btn-outline">← Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('payroll.allowances.batches.update', $batch) }}" id="batchForm">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px;">
                <div><label>Year</label><input type="number" name="period_year" value="{{ old('period_year', $batch->period_year) }}" required></div>
                <div>
                    <label>Month</label>
                    <select name="period_month" required>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected(old('period_month', $batch->period_month) == $m)>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label>Cutoff</label>
                    <select name="cutoff" required>
                        @foreach (['monthly','1st','2nd'] as $cutoff)
                            <option value="{{ $cutoff }}" @selected(old('cutoff', $batch->cutoff) === $cutoff)>{{ ucfirst($cutoff) }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label>Period Start</label><input type="date" name="period_start" value="{{ old('period_start', $batch->period_start->toDateString()) }}" required></div>
                <div><label>Period End</label><input type="date" name="period_end" value="{{ old('period_end', $batch->period_end->toDateString()) }}" required></div>
            </div>
            <div style="margin-bottom:20px;"><label>Remarks</label><textarea name="remarks" rows="2">{{ old('remarks', $batch->remarks) }}</textarea></div>

            <h3 style="margin-bottom:12px;">Entries</h3>
            <div id="entryRows">
                @foreach ($batch->entries as $i => $entry)
                <div class="entry-row" style="display:grid;grid-template-columns:2fr 1fr 120px 1fr auto;gap:10px;margin-bottom:10px;align-items:end;">
                    <div>
                        <label>Employee</label>
                        <select name="entries[{{ $i }}][employee_id]" required>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}" @selected($entry->employee_id == $emp->id)>{{ $emp->last_name }}, {{ $emp->first_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Allowance Type</label>
                        <select name="entries[{{ $i }}][allowance_type_id]" required>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}" @selected($entry->allowance_type_id == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label>Amount</label><input type="number" step="0.01" min="0" name="entries[{{ $i }}][amount]" value="{{ old("entries.$i.amount", $entry->amount) }}" required></div>
                    <div><label>Remarks</label><input type="text" name="entries[{{ $i }}][remarks]" value="{{ old("entries.$i.remarks", $entry->remarks) }}"></div>
                    <button type="button" class="btn btn-outline btn-sm remove-row">Remove</button>
                </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-outline btn-sm" id="addRow" style="margin-bottom:20px;">+ Add Row</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
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
        if (el.type === 'number' || el.type === 'text') el.value = '';
    });
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
