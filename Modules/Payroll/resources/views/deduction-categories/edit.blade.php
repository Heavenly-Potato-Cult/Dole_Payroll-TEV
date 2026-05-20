@extends('layouts.app')

@section('title', 'Edit — ' . $deductionCategory->label)
@section('page-title', 'Deduction Categories')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Category</h1>
        <p>
            <span style="font-family:monospace;background:var(--bg);border:1px solid var(--border);padding:1px 8px;border-radius:4px;font-size:0.85rem;">{{ $deductionCategory->key }}</span>
            &nbsp;<span style="font-size:0.72rem;color:var(--text-light);">🔒 Key is permanent</span>
        </p>
    </div>
    <div>
        <a href="{{ route('deduction-categories.index') }}" class="btn btn-outline">← Back to List</a>
    </div>
</div>

<div style="max-width:640px;">

    <div class="card">
        <div class="card-header"><h3>Category Details</h3></div>
        <div class="card-body">

            <form method="POST" action="{{ route('deduction-categories.update', $deductionCategory) }}">
            @csrf
            @method('PUT')

                {{-- Key (read-only) --}}
                <div style="margin-bottom:18px;">
                    <label style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Key (slug) <span style="font-weight:400;color:var(--text-light);">(permanent — cannot be changed)</span>
                    </label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-family:monospace;background:var(--bg);border:1px solid var(--border);
                                     padding:8px 14px;border-radius:6px;font-size:0.9rem;
                                     color:var(--navy);letter-spacing:.04em;">
                            {{ $deductionCategory->key }}
                        </span>
                        <span style="font-size:0.72rem;color:var(--text-light);">🔒 Locked</span>
                    </div>
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        This key is stored in every deduction type that belongs to this category.
                        Changing it would orphan all associated deduction types.
                    </div>
                </div>

                {{-- Label --}}
                <div style="margin-bottom:18px;">
                    <label for="label" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Label <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="text"
                           id="label"
                           name="label"
                           value="{{ old('label', $deductionCategory->label) }}"
                           placeholder="e.g. Cooperative Loans"
                           maxlength="100"
                           required>
                    @error('label')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        The human-readable name shown in the UI, payslips, and enrollment forms.
                    </div>
                </div>

                {{-- Display Order --}}
                <div style="margin-bottom:18px;">
                    <label for="display_order" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Display Order <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="number"
                           id="display_order"
                           name="display_order"
                           value="{{ old('display_order', $deductionCategory->display_order) }}"
                           min="0"
                           max="999"
                           required
                           style="max-width:120px;">
                    @error('display_order')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        Controls the order category groups appear on the index page and enrollment form.
                    </div>
                </div>

                {{-- Current status --}}
                <div style="padding:12px 16px;background:var(--bg);border:1px solid var(--border);border-radius:8px;margin-bottom:20px;font-size:0.82rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <div>
                        <strong>Current status:</strong>
                        @if ($deductionCategory->is_active)
                            <span style="color:#166534;font-weight:700;">● Active</span>
                            — visible in deduction type forms.
                        @else
                            <span style="color:#991b1b;font-weight:700;">● Inactive</span>
                            — hidden from deduction type forms.
                        @endif
                    </div>
                    <form id="toggleForm" method="POST"
                          action="{{ route('deduction-categories.toggle', $deductionCategory) }}"
                          style="display:inline;">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="btn {{ $deductionCategory->is_active ? 'btn-outline' : 'btn-primary' }}"
                                style="font-size:0.8rem;padding:6px 14px;">
                            {{ $deductionCategory->is_active ? '⊘ Deactivate' : '✓ Activate' }}
                        </button>
                    </form>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('deduction-categories.index') }}" class="btn btn-outline">Cancel</a>
                </div>

            </form>

        </div>
    </div>

    {{-- Meta info --}}
    <div class="card" style="background:var(--bg);margin-top:16px;">
        <div class="card-body" style="font-size:0.78rem;color:var(--text-light);">
            <strong style="color:var(--text-mid);">Record created:</strong>
            {{ $deductionCategory->created_at->format('M d, Y g:i A') }}<br>
            <strong style="color:var(--text-mid);">Last updated:</strong>
            {{ $deductionCategory->updated_at->format('M d, Y g:i A') }}
        </div>
    </div>

</div>

@endsection
