{{-- deduction-categories/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Deduction Categories')
@section('page-title', 'Deduction Categories')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Deduction Categories</h1>
        <p>Manage the category groups used to organise deduction types on payslips and enrollment forms.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('deduction-categories.create') }}" class="btn btn-primary">+ New Category</a>
        <a href="{{ route('deduction-types.index') }}" class="btn btn-outline">← Deduction Types</a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-body" style="padding:0;">
        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
            <thead>
                <tr style="background:var(--bg);">
                    <th style="padding:12px 16px;text-align:left;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);border-bottom:2px solid var(--border);">#</th>
                    <th style="padding:12px 16px;text-align:left;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);border-bottom:2px solid var(--border);">Key (slug)</th>
                    <th style="padding:12px 16px;text-align:left;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);border-bottom:2px solid var(--border);">Label</th>
                    <th style="padding:12px 16px;text-align:left;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);border-bottom:2px solid var(--border);">Deduction Types</th>
                    <th style="padding:12px 16px;text-align:left;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);border-bottom:2px solid var(--border);">Status</th>
                    <th style="padding:12px 16px;text-align:right;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-mid);border-bottom:2px solid var(--border);">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $cat)
                @php $typeCount = $typeCounts[$cat->key] ?? 0; @endphp
                <tr style="{{ $cat->is_active ? '' : 'opacity:0.5;' }}">
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border);">
                        <span style="display:inline-block;min-width:28px;text-align:center;font-size:0.75rem;font-weight:700;color:var(--text-light);background:var(--bg);border:1px solid var(--border);border-radius:4px;padding:2px 6px;">
                            {{ $cat->display_order }}
                        </span>
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border);">
                        <span style="font-family:monospace;background:var(--bg);border:1px solid var(--border);padding:3px 8px;border-radius:4px;font-size:0.82rem;color:var(--navy);">
                            {{ $cat->key }}
                        </span>
                        <span style="font-size:0.68rem;color:var(--text-light);margin-left:6px;">🔒</span>
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border);font-weight:600;color:var(--navy);">
                        {{ $cat->label }}
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border);color:var(--text-mid);">
                        {{ $typeCount }} type(s)
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border);">
                        @if ($cat->is_active)
                            <span style="background:#f0fdf4;color:#166534;font-size:0.68rem;font-weight:700;padding:3px 10px;border-radius:99px;">Active</span>
                        @else
                            <span style="background:#fef2f2;color:#991b1b;font-size:0.68rem;font-weight:700;padding:3px 10px;border-radius:99px;">Inactive</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border);text-align:right;">
                        <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                            {{-- Edit --}}
                            <a href="{{ route('deduction-categories.edit', $cat) }}"
                               style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--text-mid);text-decoration:none;font-size:0.85rem;"
                               title="Edit">✎</a>

                            {{-- Toggle active/inactive --}}
                            <form method="POST"
                                  action="{{ route('deduction-categories.toggle', $cat) }}"
                                  style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--text-mid);cursor:pointer;font-size:0.85rem;"
                                        title="{{ $cat->is_active ? 'Deactivate' : 'Activate' }}">
                                    {{ $cat->is_active ? '⊘' : '✓' }}
                                </button>
                            </form>

                            {{-- Delete — only for inactive categories with no types --}}
                            @if (!$cat->is_active && $typeCount === 0)
                            <form method="POST"
                                  action="{{ route('deduction-categories.destroy', $cat) }}"
                                  style="display:inline;"
                                  onsubmit="return confirm('Permanently delete the category \'{{ addslashes($cat->label) }}\'? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:6px;border:1px solid #fca5a5;background:#fff5f5;color:#dc2626;cursor:pointer;font-size:0.85rem;"
                                        title="Delete permanently">🗑</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding:40px;text-align:center;color:var(--text-light);">
                        No categories found. <a href="{{ route('deduction-categories.create') }}">Create the first one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px;padding:14px 18px;background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;font-size:0.82rem;color:#92400e;">
    <strong>Note:</strong> The <strong>Key (slug)</strong> is permanent — it matches the value stored in each deduction type's
    <code>category</code> field. Renaming or deleting a category key would orphan existing deduction types.
    You can only rename the <strong>Label</strong> (the human-readable name displayed in the UI).
    <br>The <strong>🗑 Delete</strong> button only appears for <em>inactive</em> categories that have <em>zero</em> deduction types assigned.
</div>

@endsection
