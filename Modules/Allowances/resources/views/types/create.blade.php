@extends('layouts.app')

@section('title', 'New Allowance Type')
@section('page-title', 'Configuration')

@section('content')
<div class="page-header">
    <div class="page-header-left"><h1>New Allowance Type</h1></div>
    <a href="{{ route('allowances.types.index') }}" class="btn btn-outline">← Back</a>
</div>

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form method="POST" action="{{ route('allowances.types.store') }}">
            @csrf
            <div style="margin-bottom:16px;">
                <label>Code *</label>
                <input type="text" name="code" value="{{ old('code') }}" required maxlength="50" style="text-transform:uppercase;font-family:monospace;" oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9_]/g,'')">
                @error('code')<div style="color:#dc2626;font-size:0.8rem;">{{ $message }}</div>@enderror
            </div>
            <div style="margin-bottom:16px;">
                <label>Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required>
            </div>
            <div style="margin-bottom:16px;">
                <label>Description</label>
                <textarea name="description" rows="2">{{ old('description') }}</textarea>
            </div>
            <div style="margin-bottom:16px;">
                <label>Display Order</label>
                <input type="number" name="display_order" value="{{ old('display_order', $nextOrder) }}" min="0">
            </div>
            <label style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Active
            </label>
            <button type="submit" class="btn btn-primary">Create</button>
        </form>
    </div>
</div>
@endsection
