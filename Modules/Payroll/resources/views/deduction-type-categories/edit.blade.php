@extends('layouts.app')

@section('title', 'Edit Category — ' . $deductionTypeCategory->name)
@section('page-title', 'Deduction Types')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Category</h1>
        <p>Update the display name, description, or order for this category.</p>
    </div>
    <div>
        <a href="{{ route('deduction-type-categories.index') }}" class="btn btn-outline">← Back to Categories</a>
    </div>
</div>

<div style="max-width:700px;">

    {{-- Immutable code notice --}}
    <div style="padding:14px 18px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;margin-bottom:20px;font-size:0.82rem;color:#0369a1;">
        <strong>Code:</strong>
        <code style="font-size:0.85rem;background:#e0f2fe;padding:2px 7px;border-radius:4px;margin-left:6px;">{{ $deductionTypeCategory->code }}</code>
        <span style="margin-left:8px;color:#0284c7;">— permanent, cannot be changed.</span>
        @if($typeCount > 0)
            <span style="margin-left:8px;">
                Used by <strong>{{ $typeCount }}</strong> deduction type{{ $typeCount !== 1 ? 's' : '' }}.
            </span>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><h3>Category Details</h3></div>
        <div class="card-body">

            <form method="POST"
                  action="{{ route('deduction-type-categories.update', $deductionTypeCategory) }}"
                  id="editCategoryForm">
            @csrf
            @method('PUT')

                {{-- Name --}}
                <div style="margin-bottom:18px;">
                    <label for="name"
                           style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);margin-bottom:5px;">
                        Name <span style="color:#dc2626;">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $deductionTypeCategory->name) }}"
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
                              rows="2">{{ old('description', $deductionTypeCategory->description) }}</textarea>
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
                           value="{{ old('display_order', $deductionTypeCategory->display_order) }}"
                           min="0"
                           max="9999"
                           required
                           style="max-width:120px;">
                    @error('display_order')
                        <div style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                <hr style="border:none;border-top:1px solid var(--border);margin:24px 0;">

                {{-- Active toggle --}}
                <div style="margin-bottom:24px;">
                    <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;padding:14px 16px;background:var(--bg);border:1px solid var(--border);border-radius:8px;">
                        <input type="checkbox"
                               id="is_active"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $deductionTypeCategory->is_active) ? 'checked' : '' }}
                               style="width:16px;height:16px;margin-top:2px;accent-color:var(--navy);flex-shrink:0;">
                        <div>
                            <div style="font-weight:700;font-size:0.875rem;color:var(--navy);">Active</div>
                            <div style="font-size:0.78rem;color:var(--text-mid);margin-top:3px;">
                                When active, this category appears in the deduction type dropdowns.
                                @if($typeCount > 0)
                                    <br>
                                    <span style="color:#92400e;">
                                        ⚠ Deactivating this will hide it from dropdowns, but
                                        <strong>{{ $typeCount }}</strong> existing deduction type{{ $typeCount !== 1 ? 's' : '' }}
                                        will continue to function normally.
                                    </span>
                                @endif
                            </div>
                        </div>
                    </label>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit" id="submitBtn" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('deduction-type-categories.index') }}" class="btn btn-outline">Cancel</a>
                </div>

            </form>
        </div>
    </div>

    {{-- Danger zone --}}
    <div class="card" style="margin-top:24px;border-color:#fca5a5;">
        <div class="card-header" style="background:#fef2f2;border-bottom-color:#fca5a5;">
            <h3 style="color:#dc2626;margin:0;">Danger Zone</h3>
        </div>
        <div class="card-body">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <div>
                    <div style="font-weight:600;font-size:0.875rem;margin-bottom:4px;">Remove this category</div>
                    <div style="font-size:0.78rem;color:var(--text-mid);">
                        @if($typeCount > 0)
                            This category has <strong>{{ $typeCount }}</strong> attached deduction type{{ $typeCount !== 1 ? 's' : '' }}.
                            It will be <strong>deactivated and soft-deleted</strong> — existing types keep their category value.
                        @else
                            No deduction types are using this category. It will be soft-deleted.
                        @endif
                    </div>
                </div>
                <form id="catDestroyForm"
                      method="POST"
                      action="{{ route('deduction-type-categories.destroy', $deductionTypeCategory) }}"
                      style="flex-shrink:0;">
                    @csrf
                    @method('DELETE')
                    <button type="button"
                            class="btn"
                            style="background:#dc2626;color:#fff;border-color:#dc2626;"
                            onclick="confirmCategoryDestroy('{{ addslashes($deductionTypeCategory->name) }}', {{ $typeCount }})">
                        Remove Category
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection

@section('scripts')
<script>
document.getElementById('editCategoryForm').addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    btn.disabled    = true;
    btn.textContent = 'Saving…';
});

function confirmCategoryDestroy(name, typeCount) {
    let html = `<div style="text-align:center;">
        <div style="font-size:1.1rem;font-weight:600;color:var(--navy);margin-bottom:8px;">${name}</div>`;

    if (typeCount > 0) {
        html += `<p style="color:#92400e;background:#fef9c3;border:1px solid #fbbf24;border-radius:6px;padding:10px;font-size:0.88rem;margin-bottom:8px;">
            ⚠ This category has <strong>${typeCount}</strong> deduction type(s) attached.<br>
            It will be <strong>deactivated</strong> and hidden from dropdowns.<br>
            Existing deduction types will continue to work.
        </p>`;
    } else {
        html += `<p style="color:#6b7280;font-size:0.88rem;">This category has no attached deduction types and will be removed.</p>`;
    }

    html += `</div>`;

    Swal.fire({
        title: 'Remove Category?',
        html,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: typeCount > 0 ? 'Deactivate & Remove' : 'Remove',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true,
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('catDestroyForm').submit();
        }
    });
}
</script>
@endsection