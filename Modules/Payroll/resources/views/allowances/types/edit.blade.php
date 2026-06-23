@extends('layouts.app')

@section('title', 'Edit Allowance Type')
@section('page-title', 'Configuration')

@section('content')
<div class="page-header">
    <div class="page-header-left"><h1>Edit — {{ $type->name }}</h1></div>
    <a href="{{ route('payroll.allowances.types.index') }}" class="btn btn-outline">← Back</a>
</div>

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form method="POST" action="{{ route('payroll.allowances.types.update', $type) }}">
            @csrf @method('PUT')
            <div style="margin-bottom:16px;">
                <label>Code</label>
                <input type="text" value="{{ $type->code }}" disabled style="font-family:monospace;background:#f5f5f5;">
            </div>
            <div style="margin-bottom:16px;">
                <label>Name *</label>
                <input type="text" name="name" value="{{ old('name', $type->name) }}" required>
            </div>
            <div style="margin-bottom:16px;">
                <label>Description</label>
                <textarea name="description" rows="2">{{ old('description', $type->description) }}</textarea>
            </div>
            <div style="margin-bottom:16px;">
                <label>Display Order</label>
                <input type="number" name="display_order" value="{{ old('display_order', $type->display_order) }}" min="0">
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
@endsection
