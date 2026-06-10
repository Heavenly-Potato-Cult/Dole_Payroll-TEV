@extends('layouts.app')

@section('title', 'Deduction Type Categories')
@section('page-title', 'Deduction Types')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Deduction Type Categories</h1>
        <p>Manage the categories used to group deduction types in the payroll system.</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
        <a href="{{ route('deduction-types.index') }}" class="btn btn-outline">← Deduction Types</a>
        <a href="{{ route('deduction-type-categories.create') }}" class="btn btn-primary">+ New Category</a>
    </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:18px;">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning" style="margin-bottom:18px;">{{ session('warning') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:18px;">{{ session('error') }}</div>
@endif

{{-- Info banner --}}
<div class="alert alert-info" style="margin-bottom:20px;font-size:0.82rem;">
    <strong>Note:</strong> Category <strong>codes</strong> are permanent and cannot be changed after creation —
    they are the runtime keys used by the payroll engine. To retire a category, deactivate or remove it;
    deduction types already using it will continue to work.
</div>

{{-- Stats row --}}
@php
    $activeCount   = $categories->where('is_active', true)->whereNull('deleted_at')->count();
    $inactiveCount = $categories->where('is_active', false)->whereNull('deleted_at')->count();
    $deletedCount  = $categories->whereNotNull('deleted_at')->count();
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">

    <div style="background:#fff;border:0.5px solid #e2e8f0;border-radius:12px;padding:1.1rem;box-shadow:0 2px 8px rgba(0,0,0,0.06);display:flex;align-items:stretch;gap:0;">
        <div style="flex:1;display:flex;flex-direction:column;justify-content:space-between;min-height:80px;padding-right:12px;">
            <div style="font-size:15px;font-weight:600;color:var(--text);">Active</div>
            <div style="font-size:12px;color:#94a3b8;">Available in dropdowns</div>
        </div>
        <div style="width:0.5px;background:#e2e8f0;flex-shrink:0;"></div>
        <div style="display:flex;align-items:center;justify-content:center;padding-left:12px;min-width:64px;">
            <span style="font-size:2.8rem;font-weight:600;letter-spacing:-2px;line-height:1;color:#534AB7;">{{ $activeCount }}</span>
        </div>
    </div>

    <div style="background:#fff;border:0.5px solid #e2e8f0;border-radius:12px;padding:1.1rem;box-shadow:0 2px 8px rgba(0,0,0,0.06);display:flex;align-items:stretch;gap:0;">
        <div style="flex:1;display:flex;flex-direction:column;justify-content:space-between;min-height:80px;padding-right:12px;">
            <div style="font-size:15px;font-weight:600;color:var(--text);">Inactive</div>
            <div style="font-size:12px;color:#94a3b8;">Hidden from dropdowns</div>
        </div>
        <div style="width:0.5px;background:#e2e8f0;flex-shrink:0;"></div>
        <div style="display:flex;align-items:center;justify-content:center;padding-left:12px;min-width:64px;">
            <span style="font-size:2.8rem;font-weight:600;letter-spacing:-2px;line-height:1;color:#94a3b8;">{{ $inactiveCount }}</span>
        </div>
    </div>

    <div style="background:#fff;border:0.5px solid #e2e8f0;border-radius:12px;padding:1.1rem;box-shadow:0 2px 8px rgba(0,0,0,0.06);display:flex;align-items:stretch;gap:0;">
        <div style="flex:1;display:flex;flex-direction:column;justify-content:space-between;min-height:80px;padding-right:12px;">
            <div style="font-size:15px;font-weight:600;color:var(--text);">Removed</div>
            <div style="font-size:12px;color:#94a3b8;">Soft-deleted, audit only</div>
        </div>
        <div style="width:0.5px;background:#e2e8f0;flex-shrink:0;"></div>
        <div style="display:flex;align-items:center;justify-content:center;padding-left:12px;min-width:64px;">
            <span style="font-size:2.8rem;font-weight:600;letter-spacing:-2px;line-height:1;color:#dc2626;">{{ $deletedCount }}</span>
        </div>
    </div>

    <div style="background:#fff;border:0.5px solid #e2e8f0;border-radius:12px;padding:1.1rem;box-shadow:0 2px 8px rgba(0,0,0,0.06);display:flex;align-items:stretch;gap:0;">
        <div style="flex:1;display:flex;flex-direction:column;justify-content:space-between;min-height:80px;padding-right:12px;">
            <div style="font-size:15px;font-weight:600;color:var(--text);">Total</div>
            <div style="font-size:12px;color:#94a3b8;">All records</div>
        </div>
        <div style="width:0.5px;background:#e2e8f0;flex-shrink:0;"></div>
        <div style="display:flex;align-items:center;justify-content:center;padding-left:12px;min-width:64px;">
            <span style="font-size:2.8rem;font-weight:600;letter-spacing:-2px;line-height:1;color:var(--text);">{{ $categories->count() }}</span>
        </div>
    </div>

</div>

{{-- Table --}}
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h3 style="margin:0;">All Categories</h3>
        <span style="font-size:0.78rem;color:var(--text-light);">
            Sorted by display order · Deleted rows shown for audit
        </span>
    </div>
    <div class="card-body" style="padding:0;">
        @if($categories->isEmpty())
            <div style="padding:48px 20px;text-align:center;color:var(--text-light);">
                <div style="font-size:2rem;margin-bottom:12px;">📂</div>
                <p>No categories yet. <a href="{{ route('deduction-type-categories.create') }}">Create the first one.</a></p>
            </div>
        @else
        <div style="overflow-x:auto;">
            <table class="dt-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="width:44px;text-align:center;">#</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th style="width:80px;text-align:center;">Types</th>
                        <th style="width:90px;text-align:center;">Status</th>
                        <th style="width:220px;text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories->sortBy('display_order') as $cat)
                    <tr style="{{ $cat->trashed() ? 'opacity:0.45;background:#fef2f2;' : '' }}">
                        {{-- Order --}}
                        <td style="text-align:center;font-variant-numeric:tabular-nums;color:var(--text-light);font-size:0.82rem;">
                            {{ $loop->iteration }}
                        </td>

                        {{-- Code --}}
                        <td>
                            <code style="font-size:0.78rem;background:var(--bg);padding:2px 6px;border-radius:4px;border:1px solid var(--border);">
                                {{ $cat->code }}
                            </code>
                            @if($cat->trashed())
                                <span style="margin-left:6px;font-size:0.7rem;background:#fee2e2;color:#dc2626;padding:1px 6px;border-radius:10px;font-weight:600;">DELETED</span>
                            @endif
                        </td>

                        {{-- Name --}}
                        <td style="font-weight:600;color:var(--text);">{{ $cat->name }}</td>

                        {{-- Description --}}
                        <td style="font-size:0.82rem;color:var(--text-mid);max-width:280px;">
                            {{ Str::limit($cat->description, 80) ?? '—' }}
                        </td>

                        {{-- Type count --}}
                        <td style="text-align:center;">
                            @if($cat->deduction_types_count > 0)
                                <span style="background:var(--navy);color:#fff;font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:12px;">
                                    {{ $cat->deduction_types_count }}
                                </span>
                            @else
                                <span style="color:var(--text-light);font-size:0.82rem;">0</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td style="text-align:center;">
                            @if($cat->trashed())
                                <span style="font-size:0.72rem;color:#dc2626;font-weight:600;">Removed</span>
                            @elseif($cat->is_active)
                                <span class="badge-active" style="font-size:0.72rem;">Active</span>
                            @else
                                <span class="badge-inactive" style="font-size:0.72rem;">Inactive</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td style="text-align:right;white-space:nowrap;">
                            <div style="display:inline-flex;gap:6px;align-items:center;">
                            @if($cat->trashed())
                                {{-- Restore --}}
                                <form method="POST"
                                      action="{{ route('deduction-type-categories.restore', $cat->id) }}"
                                      style="display:inline;"
                                      onsubmit="return confirm('Restore category \'{{ addslashes($cat->name) }}\'?')">
                                    @csrf
                                    <button type="submit"
                                            title="Restore"
                                            style="display:inline-flex;align-items:center;gap:5px;height:30px;padding:0 10px;font-size:0.72rem;font-weight:600;border-radius:6px;border:1px solid #6ee7b7;background:#ecfdf5;color:#065f46;cursor:pointer;white-space:nowrap;font-family:var(--font);transition:all 0.15s;"
                                            onmouseover="this.style.background='#d1fae5'"
                                            onmouseout="this.style.background='#ecfdf5'">
                                        ↩ Restore
                                    </button>
                                </form>

                                {{-- Force Delete (permanent) --}}
                                <form id="catForceDeleteForm-{{ $cat->id }}"
                                      method="POST"
                                      action="{{ route('deduction-type-categories.force-delete', $cat->id) }}"
                                      style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            title="Delete Forever"
                                            style="display:inline-flex;align-items:center;gap:5px;height:30px;padding:0 10px;font-size:0.72rem;font-weight:600;border-radius:6px;border:1px solid #dc2626;background:#dc2626;color:#fff;cursor:pointer;white-space:nowrap;font-family:var(--font);transition:all 0.15s;"
                                            onmouseover="this.style.background='#b91c1c';this.style.borderColor='#b91c1c'"
                                            onmouseout="this.style.background='#dc2626';this.style.borderColor='#dc2626'"
                                            onclick="confirmForceDelete({{ $cat->id }}, '{{ addslashes($cat->name) }}')">
                                        🗑 Delete Forever
                                    </button>
                                </form>
                            @else
                                {{-- Edit --}}
                                <a href="{{ route('deduction-type-categories.edit', $cat) }}"
                                   title="Edit"
                                   style="display:inline-flex;align-items:center;gap:5px;height:30px;padding:0 10px;font-size:0.72rem;font-weight:600;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:var(--text-mid);text-decoration:none;white-space:nowrap;font-family:var(--font);transition:all 0.15s;"
                                   onmouseover="this.style.background='var(--navy)';this.style.color='#fff';this.style.borderColor='var(--navy)'"
                                   onmouseout="this.style.background='#fff';this.style.color='var(--text-mid)';this.style.borderColor='#e2e8f0'">
                                    ✎ Edit
                                </a>

                                {{-- Remove (soft delete) --}}
                                <form id="catDestroyForm-{{ $cat->id }}"
                                      method="POST"
                                      action="{{ route('deduction-type-categories.destroy', $cat) }}"
                                      style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            title="Remove"
                                            style="display:inline-flex;align-items:center;gap:5px;height:30px;padding:0 10px;font-size:0.72rem;font-weight:600;border-radius:6px;border:1px solid #fca5a5;background:#fff1f2;color:#dc2626;cursor:pointer;white-space:nowrap;font-family:var(--font);transition:all 0.15s;"
                                            onmouseover="this.style.background='#dc2626';this.style.color='#fff';this.style.borderColor='#dc2626'"
                                            onmouseout="this.style.background='#fff1f2';this.style.color='#dc2626';this.style.borderColor='#fca5a5'"
                                            onclick="confirmCategoryDestroy({{ $cat->id }}, '{{ addslashes($cat->name) }}', {{ $cat->deduction_types_count }})">
                                        🗑 Remove
                                    </button>
                                </form>
                            @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@endsection

@section('scripts')
<script>
function confirmForceDelete(id, name) {
    Swal.fire({
        title: 'Permanently Delete?',
        html: `<div style="text-align:center;">
            <div style="font-size:1.1rem;font-weight:600;color:#dc2626;margin-bottom:10px;">${name}</div>
            <p style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:12px;font-size:0.88rem;color:#7f1d1d;margin-bottom:8px;">
                ⛔ This action is <strong>irreversible</strong>.<br>
                The category record will be wiped from the database<br>and <strong>cannot be recovered</strong>.
            </p>
            <p style="font-size:0.78rem;color:#6b7280;">Deduction types using this category will keep their category string but lose the FK reference.</p>
        </div>`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: '🗑 Yes, delete forever',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true,
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('catForceDeleteForm-' + id).submit();
        }
    });
}

function confirmCategoryDestroy(id, name, typeCount) {
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
            document.getElementById('catDestroyForm-' + id).submit();
        }
    });
}
</script>
@endsection