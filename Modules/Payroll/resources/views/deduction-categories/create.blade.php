@extends('layouts.app')

@section('title', 'New Deduction Category')
@section('page-title', 'Deduction Categories')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>New Deduction Category</h1>
        <p>Add a new category group for organising deduction types.</p>
    </div>
    <div>
        <a href="{{ route('deduction-categories.index') }}" class="btn btn-outline">← Back to List</a>
    </div>
</div>

<div style="max-width:640px;">

    <div class="card">
        <div class="card-header"><h3>Category Details</h3></div>
        <div class="card-body">

            <form method="POST" action="{{ route('deduction-categories.store') }}" id="createCategoryForm">
            @csrf

                {{-- Key --}}
                <div style="margin-bottom:18px;">
                    <label for="key" style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Key (slug) <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="text"
                           id="key"
                           name="key"
                           value="{{ old('key') }}"
                           placeholder="e.g. coop_loan"
                           maxlength="50"
                           required
                           autocomplete="off"
                           style="font-family:monospace;"
                           oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g,'')">
                    @error('key')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        ⚠ The key is <strong>permanent</strong> — it cannot be changed after saving.
                        Use lowercase letters, numbers, and underscores only (e.g. <code>coop_loan</code>).
                        This key is stored in every deduction type that belongs to this category.
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
                           value="{{ old('label') }}"
                           placeholder="e.g. Cooperative Loans"
                           maxlength="100"
                           required>
                    @error('label')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        The human-readable name shown in the UI, payslips, and enrollment forms.
                        This can be changed at any time.
                    </div>
                </div>

                {{-- Display Order --}}
                <div style="margin-bottom:24px;">
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
                    @error('display_order')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div style="font-size:0.72rem;color:var(--text-light);margin-top:4px;">
                        Controls the order category groups appear on the index page and enrollment form.
                        Current highest: {{ $nextOrder - 1 }}.
                    </div>
                </div>

                <div class="alert alert-info" style="margin-bottom:20px;font-size:0.82rem;">
                    <strong>Note:</strong> Once saved, the <strong>Key</strong> cannot be changed.
                    Make sure it accurately describes the category before saving.
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit"
                            id="submitBtn"
                            class="btn btn-primary">
                        Save Category
                    </button>
                    <a href="{{ route('deduction-categories.index') }}" class="btn btn-outline">Cancel</a>
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
