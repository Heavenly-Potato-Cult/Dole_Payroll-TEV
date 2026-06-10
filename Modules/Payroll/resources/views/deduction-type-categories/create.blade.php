@extends('layouts.app')

@section('title', 'New Deduction Category')
@section('page-title', 'Deduction Types')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>New Deduction Category</h1>
        <p>Add a new category for grouping deduction types.</p>
    </div>
    <div>
        <a href="{{ route('deduction-type-categories.index') }}" class="btn btn-outline">← Back to Categories</a>
    </div>
</div>

<div style="max-width:700px;">
    <div class="card">
        <div class="card-header"><h3>Category Details</h3></div>
        <div class="card-body">

            <form method="POST" action="{{ route('deduction-type-categories.store') }}" id="createCategoryForm">
            @csrf

                {{-- Code --}}
                <div style="margin-bottom:18px;">
                    <label for="code"
                           style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Code <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="text"
                           id="code"
                           name="code"
                           value="{{ old('code') }}"
                           placeholder="e.g. cooperative_loan"
                           maxlength="50"
                           required
                           autocomplete="off"
                           style="font-family:monospace;"
                           oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g,'')">
                    @error('code')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        ⚠ The code is <strong>permanent</strong> — it cannot be changed after saving.
                        Lowercase letters, numbers, and underscores only (e.g. <code>cooperative_loan</code>).
                        This becomes the <code>category</code> value stored on deduction types.
                    </div>
                </div>

                {{-- Name --}}
                <div style="margin-bottom:18px;">
                    <label for="name"
                           style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Name <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           placeholder="e.g. Cooperative Loans"
                           maxlength="150"
                           required>
                    @error('name')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Description --}}
                <div style="margin-bottom:18px;">
                    <label for="description"
                           style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Description <span style="font-weight:400;">(optional)</span>
                    </label>
                    <textarea id="description"
                              name="description"
                              maxlength="500"
                              rows="2"
                              placeholder="e.g. Multi-purpose cooperative loan deductions, per-employee amortization amounts.">{{ old('description') }}</textarea>
                    @error('description')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Display Order --}}
                <div style="margin-bottom:18px;">
                    <label for="display_order"
                           style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Display Order <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="number"
                           id="display_order"
                           name="display_order"
                           value="{{ old('display_order', $nextOrder) }}"
                           min="0"
                           max="9999"
                           required
                           style="max-width:120px;">
                    @error('display_order')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        Controls the sort order in dropdowns and the deduction types list.
                        Uses increments of 10 to leave room for insertions.
                    </div>
                </div>

                <hr style="border:none;border-top:1px solid var(--border);margin:24px 0;">

                {{-- Active toggle --}}
                <div style="margin-bottom:24px;">
                    <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;padding:14px 16px;background:var(--bg);border:1px solid var(--border);border-radius:8px;">
                        <input type="checkbox"
                               id="is_active"
                               name="is_active"
                               value="1"
                               {{ old('is_active', '1') ? 'checked' : '' }}
                               style="width:16px;height:16px;margin-top:2px;accent-color:var(--navy);flex-shrink:0;">
                        <div>
                            <div style="font-weight:700;font-size:0.875rem;color:var(--navy);">Active</div>
                            <div style="font-size:0.78rem;color:var(--text-mid);margin-top:3px;">
                                When active, this category appears in the deduction type dropdowns.
                                Inactive categories are hidden but not deleted.
                            </div>
                        </div>
                    </label>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit" id="submitBtn" class="btn btn-primary">Save Category</button>
                    <a href="{{ route('deduction-type-categories.index') }}" class="btn btn-outline">Cancel</a>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.getElementById('createCategoryForm').addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    btn.disabled    = true;
    btn.textContent = 'Saving…';
});
</script>
@endsection