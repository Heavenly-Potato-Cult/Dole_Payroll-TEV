@extends('layouts.app')

@section('title', 'Allowance Types')
@section('page-title', 'Configuration')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Allowance Types</h1>
        <p>Define allowance line items used in employee enrollments, batches, and payslips.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('allowances.index') }}" class="btn btn-outline">Allowance Batches</a>
        <a href="{{ route('allowances.types.create') }}" class="btn btn-primary">+ New Type</a>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($types as $type)
                <tr>
                    <td>{{ $type->display_order }}</td>
                    <td><code>{{ $type->code }}</code></td>
                    <td>{{ $type->name }}</td>
                    <td>{{ $type->is_active ? 'Active' : 'Inactive' }}</td>
                    <td style="white-space:nowrap;">
                        <a href="{{ route('allowances.types.edit', $type) }}" class="btn btn-sm btn-outline">Edit</a>
                        <form method="POST" action="{{ route('allowances.types.toggle', $type) }}" style="display:inline;">@csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline">{{ $type->is_active ? 'Deactivate' : 'Activate' }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:24px;">No allowance types yet. Run the seeder or create one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
