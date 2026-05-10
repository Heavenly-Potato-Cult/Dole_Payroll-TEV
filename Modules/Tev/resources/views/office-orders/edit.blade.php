{{-- resources/views/office-orders/edit.blade.php --}}
{{--
    Expects from OfficeOrderController@edit:
      $order     — OfficeOrder (draft only)
      $employees — collection of active Employee models
--}}

@extends('layouts.tev')

@section('title', 'Edit Office Order — ' . $order->office_order_no)
@section('page-title', 'Travel (TEV)')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Office Order</h1>
        <p>{{ $order->office_order_no }} — Draft</p>
    </div>
    <a href="{{ route('tev.office-orders.show', $order->id) }}" class="btn btn-outline btn-sm">
        ← Cancel
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-error mb-3">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card" style="max-width:760px;">
    <div class="card-header">
        <h3>📝 Office Order Details</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('tev.office-orders.update', $order->id) }}">
            @csrf
            @method('PUT')

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">

                <div class="form-group">
                    <label for="office_order_no">
                        Office Order No. <span style="color:var(--red);">*</span>
                    </label>
                    <input type="text" id="office_order_no" name="office_order_no"
                           value="{{ old('office_order_no', $order->office_order_no) }}"
                           class="{{ $errors->has('office_order_no') ? 'is-invalid' : '' }}"
                           required>
                    @error('office_order_no')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="employee_id">
                        Employee (Traveler) <span style="color:var(--red);">*</span>
                    </label>
                    <select name="employee_id" id="employee_id"
                            class="{{ $errors->has('employee_id') ? 'is-invalid' : '' }}"
                            required>
                        <option value="">— Select Employee —</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}"
                                {{ old('employee_id', $order->employee_id) == $emp->id ? 'selected' : '' }}>
                                {{ $emp->last_name }}, {{ $emp->first_name }}
                                @if ($emp->middle_name) {{ substr($emp->middle_name, 0, 1) }}. @endif
                                — {{ $emp->position_title ?? 'N/A' }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            <div class="form-group">
                <label for="purpose">Purpose <span style="color:var(--red);">*</span></label>
                <textarea id="purpose" name="purpose" rows="3"
                          class="{{ $errors->has('purpose') ? 'is-invalid' : '' }}"
                          required>{{ old('purpose', $order->purpose) }}</textarea>
                @error('purpose')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="destination">Destination <span style="color:var(--red);">*</span></label>
                <input type="text" id="destination" name="destination"
                       value="{{ old('destination', $order->destination) }}"
                       class="{{ $errors->has('destination') ? 'is-invalid' : '' }}"
                       required>
                @error('destination')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Travel Type <span style="color:var(--red);">*</span></label>
                <div style="display:flex; gap:24px; margin-top:6px;">
                    @foreach (['local' => ['Local', '#E8F5E9', '#1B5E20'], 'regional' => ['Regional', '#FFF8E1', '#F57F17'], 'national' => ['National', '#E8EAF6', '#1A237E']] as $val => $meta)
                        @php $checked = old('travel_type', $order->travel_type) === $val; @endphp
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;
                                      padding:10px 16px; border-radius:8px;
                                      border:2px solid {{ $checked ? $meta[2] : 'var(--border)' }};
                                      background:{{ $checked ? $meta[1] : 'transparent' }};
                                      font-weight:600; font-size:0.88rem; color:{{ $meta[2] }};">
                            <input type="radio" name="travel_type" value="{{ $val }}"
                                   {{ $checked ? 'checked' : '' }}
                                   style="accent-color:{{ $meta[2] }};">
                            {{ $meta[0] }}
                        </label>
                    @endforeach
                </div>
                @error('travel_type')
                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                @enderror
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="travel_date_start">
                        Travel Date — Start <span style="color:var(--red);">*</span>
                    </label>
                    <input type="date" id="travel_date_start" name="travel_date_start"
                           value="{{ old('travel_date_start', $order->travel_date_start->toDateString()) }}"
                           class="{{ $errors->has('travel_date_start') ? 'is-invalid' : '' }}"
                           required>
                    @error('travel_date_start')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="travel_date_end">
                        Travel Date — End <span style="color:var(--red);">*</span>
                    </label>
                    <input type="date" id="travel_date_end" name="travel_date_end"
                           value="{{ old('travel_date_end', $order->travel_date_end->toDateString()) }}"
                           class="{{ $errors->has('travel_date_end') ? 'is-invalid' : '' }}"
                           required>
                    @error('travel_date_end')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="remarks">Remarks (optional)</label>
                <textarea id="remarks" name="remarks" rows="2">{{ old('remarks', $order->remarks) }}</textarea>
            </div>

            <div style="display:flex; gap:12px; margin-top:8px;">
                <button type="submit" class="btn btn-primary">Update Office Order</button>
                <a href="{{ route('tev.office-orders.show', $order->id) }}" class="btn btn-outline">Cancel</a>
            </div>

        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
(function () {
    var radios = document.querySelectorAll('input[name="travel_type"]');
    var colors = {
        local:    { bg: '#E8F5E9', border: '#1B5E20', text: '#1B5E20' },
        regional: { bg: '#FFF8E1', border: '#F57F17', text: '#F57F17' },
        national: { bg: '#E8EAF6', border: '#1A237E', text: '#1A237E' }
    };
    radios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            radios.forEach(function (r) {
                var lbl = r.closest('label');
                var c   = colors[r.value];
                if (r.checked) {
                    lbl.style.borderColor = c.border;
                    lbl.style.background  = c.bg;
                    lbl.style.color       = c.text;
                } else {
                    lbl.style.borderColor = 'var(--border)';
                    lbl.style.background  = 'transparent';
                    lbl.style.color       = colors[r.value].text;
                }
            });
        });
    });
})();
</script>
@endsection