{{-- resources/views/tev/create.blade.php --}}
@extends('layouts.tev')

@section('title', 'New TEV Request')
@section('page-title', 'Travel (TEV)')

@section('styles')
<style>
/* ── Scoped reset ── */
#tevForm *, #tevForm *::before, #tevForm *::after { box-sizing: border-box; }

/* ── Helpers ── */
.field-hint { font-size: 0.74rem; color: var(--text-light); margin-top: 4px; line-height: 1.4; }

/* ── Track radio cards ── */
.track-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; width: 100%; }
.track-card {
    display: flex; align-items: flex-start; gap: 10px; cursor: pointer;
    padding: 12px 14px; border-radius: 8px; border: 2px solid var(--border);
    background: transparent; transition: all 0.2s ease;
    width: 100%; min-width: 0; overflow: hidden;
}
.track-card input[type="radio"] { margin-top: 2px; flex-shrink: 0; width: 16px; height: 16px; }
.track-card-body { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.track-card-title { font-weight: 700; font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.track-card-desc  { font-size: 0.74rem; color: var(--text-light); line-height: 1.35; word-break: break-word; }
.track-card.cash_advance  .track-card-title { color: #1B5E20; }
.track-card.reimbursement .track-card-title { color: #1A237E; }
.track-card.selected-cash  { border-color: #1B5E20; background: #E8F5E9; }
.track-card.selected-reimb { border-color: #1A237E; background: #E8EAF6; }

/* ── Totals panel ── */
.totals-panel { background: var(--navy); color: #fff; border-radius: 8px; padding: 16px 20px; font-size: 0.83rem; }
.totals-panel .totals-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid rgba(255,255,255,0.12); }
.totals-panel .totals-row:last-child { border-bottom: none; }
.totals-panel .totals-grand { font-size: 1rem; font-weight: 700; color: var(--gold); }

/* ── Itinerary desktop table ── */
.itin-desktop { display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.itin-desktop table { width: 100%; border-collapse: collapse; font-size: 0.81rem; min-width: 820px; }
.itin-desktop thead th {
    background: var(--navy); color: #fff; padding: 8px;
    font-size: 0.70rem; font-weight: 600; letter-spacing: 0.03em;
    border: 1px solid rgba(255,255,255,0.15); text-align: center; white-space: nowrap;
}
.itin-desktop thead th .th-sub { display: block; font-size: 0.62rem; font-weight: 400; opacity: 0.75; margin-top: 1px; font-style: italic; }
.itin-desktop tbody td { padding: 5px; border: 1px solid var(--border); vertical-align: middle; }
.itin-desktop tfoot td { padding: 8px 10px; font-weight: 700; background: #f0f2ff; border: 1px solid var(--border); font-size: 0.83rem; }
.itin-desktop input, .itin-desktop select { width: 100%; padding: 5px 6px; border: 1px solid var(--border); border-radius: 4px; font-size: 0.80rem; background: #fff; }
.itin-desktop input[type="checkbox"] { width: auto; cursor: pointer; }
.itin-desktop input:focus, .itin-desktop select:focus { outline: none; border-color: var(--navy); box-shadow: 0 0 0 2px rgba(15,27,76,0.12); }

/* ── Example hint box ── */
.itin-example {
    background: #f8f9ff; border: 1px dashed #b0b8d8; border-radius: 6px;
    margin-bottom: 14px; font-size: 0.78rem; color: var(--text-mid); line-height: 1.6;
    overflow: hidden;
}
.itin-example-toggle {
    display: none;
    width: 100%; background: none; border: none; cursor: pointer;
    padding: 11px 14px; font-size: 0.80rem; font-weight: 700; color: var(--navy);
    text-align: left; align-items: center; justify-content: space-between; gap: 8px;
    font-family: var(--font);
}
.itin-example-toggle-icon { font-size: 0.68rem; transition: transform 0.22s; flex-shrink: 0; }
.itin-example-toggle[aria-expanded="true"] .itin-example-toggle-icon { transform: rotate(180deg); }
.itin-example-body { padding: 10px 14px 12px; display: block; }
.itin-example-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; margin-top: 8px; }
.itin-example-table { width: 100%; border-collapse: collapse; font-size: 0.76rem; min-width: 400px; }
.itin-example-table th {
    padding: 4px 8px;
    border: 1px solid #c8cfe8;
    background: var(--navy);
    color: #fff;
    text-align: left;
    white-space: nowrap;
}
.itin-example-table td { padding: 4px 8px; border: 1px solid #c8cfe8; }
.itin-tips { margin-top: 8px; font-size: 0.74rem; color: #7B5800; line-height: 1.7; }

/* ── Mobile itinerary cards ── */
.itin-mobile { display: none; }
.itin-card { background: #f8f9ff; border: 1px solid var(--border); border-radius: 8px; padding: 14px; margin-bottom: 12px; }
.itin-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 6px; }
.itin-card-date { font-weight: 700; color: var(--navy); font-size: 0.85rem; }
.itin-card-remove { background: var(--red); color: white; border: none; border-radius: 4px; padding: 4px 10px; cursor: pointer; font-size: 0.75rem; }
.itin-card-field { margin-bottom: 10px; }
.itin-card-field label { display: block; font-size: 0.70rem; font-weight: 700; color: var(--text-mid); margin-bottom: 3px; text-transform: uppercase; letter-spacing: 0.05em; }
.itin-card-field input, .itin-card-field select { width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 0.85rem; }
.itin-card-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
.itin-card-halfday { display: flex; align-items: center; gap: 8px; margin: 10px 0; }
.itin-card-halfday input { width: auto; }
.itin-card-halfday label { font-size: 0.82rem; text-transform: none; letter-spacing: 0; font-weight: 400; color: var(--text-mid); margin: 0; }

/* ── Step indicator ── */
.step-indicator { display: flex; margin-bottom: 24px; border-radius: 8px; border: 1px solid var(--border); overflow: hidden; }
.step-item { flex: 1; padding: 10px 14px; font-size: 0.76rem; font-weight: 600; display: flex; align-items: center; gap: 8px; background: var(--surface); color: var(--text-light); border-right: 1px solid var(--border); min-width: 0; }
.step-item:last-child { border-right: none; }
.step-item.active { background: #EEF1FA; color: var(--navy); }
.step-num { width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0; background: var(--border); color: var(--text-light); display: flex; align-items: center; justify-content: center; font-size: 0.70rem; font-weight: 700; }
.step-item.active .step-num { background: var(--navy); color: #fff; }

/* ── Auto-submit notice ── */
.autosubmit-notice {
    display: flex; align-items: flex-start; gap: 12px;
    background: #EEF3FF; border: 1px solid #4F73D9; border-radius: 8px;
    padding: 12px 16px; font-size: 0.79rem; color: #1A3A8F; margin-bottom: 20px;
    line-height: 1.55;
}
.autosubmit-notice .an-icon { font-size: 1.2rem; flex-shrink: 0; margin-top: 1px; }
@media print { .autosubmit-notice { display: none !important; } }

/* ── Layout grid ── */
.tev-create-grid { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; max-width: 100%; }
.tev-left-col    { display: flex; flex-direction: column; gap: 20px; min-width: 0; }
.tev-right-panel { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 20px; min-width: 0; }
.form-row-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

/* ════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════ */
@media (max-width: 900px) {
    .tev-create-grid { grid-template-columns: 1fr; }
    .tev-right-panel { position: static; order: -1; }
}

@media (max-width: 768px) {
    .track-cards    { grid-template-columns: 1fr; gap: 10px; }
    .form-row-grid  { grid-template-columns: 1fr; gap: 12px; }
    .itin-desktop   { display: none; }
    .itin-mobile    { display: block; }
    .step-indicator { display: none; }
    .tev-right-panel { position: static; }

    .itin-example-toggle { display: flex; }
    .itin-example-body   { display: none; padding: 0 14px; }
    .itin-example-body.open { display: block; padding: 0 14px 12px; }
}

@media (max-width: 600px) {
    body { overflow-x: hidden; }
    .tev-create-grid, .card, .card-body, .card-header,
    .totals-panel, .itin-example, .itin-card, .track-card, .tev-right-panel {
        max-width: 100%; overflow-x: hidden;
    }
    .card-body   { padding: 12px; }
    .card-header { padding: 12px 14px; }
    .track-cards { grid-template-columns: 1fr; gap: 10px; }
    .track-card  { padding: 14px 12px; }
    .track-card-title { white-space: normal; font-size: 0.85rem; }
    .totals-panel { padding: 14px 16px; font-size: 0.80rem; }
    .tev-right-panel { position: static !important; width: 100%; }
    .form-row-grid { grid-template-columns: 1fr; gap: 10px; }
}

@media (max-width: 480px) {
    .itin-card-row { grid-template-columns: 1fr; gap: 8px; }
    .itin-card     { padding: 12px; }
}

@media print {
    .no-print { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
}
</style>
@endsection

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>New TEV Request</h1>
        <p class="text-muted">Travel Expense Voucher — fill in all sections then click <strong>Submit TEV Request</strong>. The TEV will be sent directly to the Accountant for review.</p>
    </div>
    <a href="{{ route('tev.requests.index') }}" class="btn btn-outline btn-sm">← Back to List</a>
</div>

@if ($errors->any())
<div class="alert alert-error" style="margin-bottom:16px;">
    <strong>Please fix the following before saving:</strong>
    <ul style="margin:6px 0 0; padding-left:18px;">
        @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
    </ul>
</div>
@endif

{{-- Auto-submit notice ── --}}
<div class="autosubmit-notice">
    <span class="an-icon">📬</span>
    <div>
        <strong>Heads up:</strong> Once you click <strong>Submit TEV Request</strong>, this TEV will be
        <strong>automatically sent to the Accountant</strong> for review — no separate submission step needed.
        Make sure all itinerary lines are complete before saving.
    </div>
</div>

<div class="step-indicator">
    <div class="step-item active"><div class="step-num">1</div> Select Office Order</div>
    <div class="step-item active"><div class="step-num">2</div> Choose Track</div>
    <div class="step-item active"><div class="step-num">3</div> Travel Details</div>
    <div class="step-item active"><div class="step-num">4</div> Itinerary Lines</div>
    <div class="step-item active"><div class="step-num">5</div> Submit</div>
</div>

@php
    $ratesArr = [];
    foreach ($perDiemRates as $type => $rates) {
        $first = $rates->first();
        if ($first) {
            $ratesArr[$type] = [
                'daily'    => (float) $first->daily_rate,
                'half_day' => $first->half_day_rate
                    ? (float) $first->half_day_rate
                    : round((float) $first->daily_rate / 2, 2),
            ];
        }
    }
    $ratesJson = json_encode($ratesArr);
@endphp

<form method="POST" action="{{ route('tev.requests.store') }}" id="tevForm">
@csrf

{{--
    ══════════════════════════════════════════════════════════════════════
    HIDDEN INPUTS — Single source of truth for itinerary lines.
    These are the ONLY inputs submitted to the server for itinerary data.
    The desktop table and mobile cards are purely visual (no name attrs).
    ══════════════════════════════════════════════════════════════════════
--}}
<div id="hiddenLineInputs" style="display:none;"></div>

<div class="tev-create-grid">

    {{-- ── LEFT COLUMN ── --}}
    <div class="tev-left-col">

        {{-- STEP 1 ── --}}
        <div class="card">
            <div class="card-header"><h3>📝 Step 1 — Office Order</h3></div>
            <div class="card-body">
                @if ($isEmployee)
                    <p class="field-hint" style="margin-bottom:14px;">
                        As an employee, you can file a TEV directly without an Office Order.
                        Fill in your travel details manually in the next steps.
                    </p>
                    <div class="form-group" style="display:none;">
                        <input type="hidden" name="office_order_id" value="">
                    </div>
                @else
                    <p class="field-hint" style="margin-bottom:14px;">
                        Select the <strong>approved Office Order</strong> that authorises this travel.
                        The destination, travel type, and dates will be filled in automatically.
                    </p>
                    <div class="form-group">
                        <label for="office_order_id">Office Order <span style="color:var(--red);">*</span></label>
                        <select name="office_order_id" id="office_order_id"
                                class="{{ $errors->has('office_order_id') ? 'is-invalid' : '' }}" required>
                            <option value="">— Select an approved Office Order —</option>
                            @if ($approvedOrders)
                                @foreach ($approvedOrders as $oo)
                                    @php
                                        $ooEmp   = $oo->employee;
                                        $empName = optional($ooEmp)->last_name . ', ' . optional($ooEmp)->first_name;
                                    @endphp
                                    <option value="{{ $oo->id }}"
                                        data-destination="{{ $oo->destination }}"
                                        data-travel-type="{{ $oo->travel_type }}"
                                        data-purpose="{{ $oo->purpose }}"
                                        data-date-start="{{ $oo->travel_date_start->toDateString() }}"
                                        data-date-end="{{ $oo->travel_date_end->toDateString() }}"
                                        {{ old('office_order_id') == $oo->id ? 'selected' : '' }}>
                                        {{ $oo->office_order_no }} — {{ $empName }}
                                        ({{ $oo->travel_date_start->format('M d') }}–{{ $oo->travel_date_end->format('M d, Y') }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('office_order_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif
                <div id="oo-preview" style="display:none; margin-top:10px; padding:10px 14px;
                     background:#f0f2ff; border-radius:6px; font-size:0.82rem;
                     border-left:3px solid var(--navy); overflow-wrap:break-word;">
                    <div><strong>Destination:</strong> <span id="oo-destination"></span></div>
                    <div><strong>Travel Type:</strong> <span id="oo-travel-type"></span>
                        <span id="oo-perdiem-hint" style="margin-left:6px; font-size:0.76rem; color:#1B5E20; font-weight:600;"></span>
                    </div>
                    <div><strong>Purpose:</strong> <span id="oo-purpose"></span></div>
                    <div><strong>Travel Period:</strong> <span id="oo-dates"></span></div>
                </div>
            </div>
        </div>

        {{-- STEP 2 ── --}}
        <div class="card">
            <div class="card-header"><h3>💳 Step 2 — Track</h3></div>
            <div class="card-body">
                <p class="field-hint" style="margin-bottom:14px;">
                    Choose when the money moves — <strong>before</strong> or <strong>after</strong> travel.
                </p>
                <div class="track-cards">
                    @php $trackChecked = old('track', 'cash_advance'); @endphp
                    <label class="track-card cash_advance {{ $trackChecked === 'cash_advance' ? 'selected-cash' : '' }}">
                        <input type="radio" name="track" value="cash_advance" {{ $trackChecked === 'cash_advance' ? 'checked' : '' }}>
                        <div class="track-card-body">
                            <span class="track-card-title">💵 Cash Advance</span>
                            <span class="track-card-desc">Request funds <em>before</em> you travel. Requires liquidation after you return.</span>
                        </div>
                    </label>
                    <label class="track-card reimbursement {{ $trackChecked === 'reimbursement' ? 'selected-reimb' : '' }}">
                        <input type="radio" name="track" value="reimbursement" {{ $trackChecked === 'reimbursement' ? 'checked' : '' }}>
                        <div class="track-card-body">
                            <span class="track-card-title">🧾 Reimbursement</span>
                            <span class="track-card-desc">Claim expenses <em>after</em> travel. You pay first, DOLE reimburses you.</span>
                        </div>
                    </label>
                </div>
                @error('track')<div class="invalid-feedback" style="display:block; margin-top:6px;">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- STEP 3 ── --}}
        <div class="card">
            <div class="card-header"><h3>✈ Step 3 — Travel Details</h3></div>
            <div class="card-body">
                <p class="field-hint" style="margin-bottom:14px;">
                    These fields are <strong>auto-filled from the Office Order</strong> above. You may adjust them if needed.
                </p>
                <input type="hidden" name="travel_type" id="travel_type" value="{{ old('travel_type', 'local') }}">
                <div class="form-row-grid">
                    <div class="form-group">
                        <label for="travel_date_start">Travel Date — Start <span style="color:var(--red);">*</span></label>
                        <input type="date" id="travel_date_start" name="travel_date_start"
                               value="{{ old('travel_date_start') }}"
                               class="{{ $errors->has('travel_date_start') ? 'is-invalid' : '' }}" required>
                        @error('travel_date_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="travel_date_end">Travel Date — End <span style="color:var(--red);">*</span></label>
                        <input type="date" id="travel_date_end" name="travel_date_end"
                               value="{{ old('travel_date_end') }}"
                               class="{{ $errors->has('travel_date_end') ? 'is-invalid' : '' }}" required>
                        @error('travel_date_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-group">
                    <label for="purpose">Purpose <span style="color:var(--red);">*</span></label>
                    <textarea id="purpose" name="purpose" rows="2"
                              placeholder="e.g. Attendance to the 1st Quarterly DOLE-PESO and JPO Meeting"
                              class="{{ $errors->has('purpose') ? 'is-invalid' : '' }}" required>{{ old('purpose') }}</textarea>
                    @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="destination">Destination <span style="color:var(--red);">*</span></label>
                    <input type="text" id="destination" name="destination"
                           placeholder="e.g. Royal Farm Resort, Dipolog City, Zamboanga del Norte"
                           value="{{ old('destination') }}"
                           class="{{ $errors->has('destination') ? 'is-invalid' : '' }}" required>
                    @error('destination')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="remarks">Remarks (optional)</label>
                    <textarea id="remarks" name="remarks" rows="2" placeholder="Any additional notes...">{{ old('remarks') }}</textarea>
                </div>
            </div>
        </div>

        {{-- STEP 4 ── --}}
        <div class="card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                <h3>🗓 Step 4 — Itinerary Lines</h3>
                <button type="button" id="addRowBtn" class="btn btn-sm btn-gold">+ Add Row</button>
            </div>
            <div class="card-body">

                {{-- Example hint — accordion on mobile ── --}}
                <div class="itin-example">
                    <button type="button" class="itin-example-toggle" id="itinExampleToggle" aria-expanded="false">
                        <span>💡 How to fill this section — tap to expand</span>
                        <span class="itin-example-toggle-icon">▼</span>
                    </button>
                    <div class="itin-example-body" id="itinExampleBody">
                        <strong>How to fill this section:</strong> Each row is one <em>leg</em> of your journey.
                        Enter place names (not dates) in the <strong>From</strong> and <strong>To</strong> columns.<br>
                        <strong>Example rows for a 2-day trip to Dipolog City:</strong>
                        <div class="itin-example-table-wrap">
                            <table class="itin-example-table">
                                <thead>
                                    <tr>
                                        <th>Date</th><th>From</th><th>To</th><th>Mode</th>
                                        <th style="text-align:right;">Transport</th>
                                        <th style="text-align:right;">Per Diem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Feb 18</td><td>Residence</td><td>DOLE RO9 Office</td><td>E-Bike</td>
                                        <td style="text-align:right;">50.00</td><td style="text-align:right;">0.00</td>
                                    </tr>
                                    <tr>
                                        <td>Feb 18</td><td>DOLE RO9 Office</td><td>Dipolog City, ZDN</td><td>Rented Van</td>
                                        <td style="text-align:right;">0.00</td><td style="text-align:right;">1,500.00</td>
                                    </tr>
                                    <tr>
                                        <td>Feb 19</td>
                                        <td colspan="3" style="font-style:italic; color:#666;">Still in Dipolog City (accommodation/meals)</td>
                                        <td style="text-align:right;">0.00</td><td style="text-align:right;">300.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="itin-tips">
                            💡 <strong>From / To</strong> = place names — <em>not</em> dates.<br>
                            💡 <strong>Per Diem</strong> is auto-filled from your travel type. You can adjust it per row.<br>
                            💡 <strong>Half Day</strong> = tick this if you only travelled half a day (reduces per diem to half rate).
                        </div>
                    </div>
                </div>

                {{-- Desktop table — visual only, NO name attributes on inputs ── --}}
                <div class="itin-desktop">
                    <table>
                        <thead>
                            <tr>
                                <th style="min-width:110px;">Date<span class="th-sub">Travel date</span></th>
                                <th style="min-width:130px;">From<span class="th-sub">Origin place</span></th>
                                <th style="min-width:130px;">To<span class="th-sub">Destination place</span></th>
                                <th style="min-width:85px;">Depart<span class="th-sub">Time (optional)</span></th>
                                <th style="min-width:85px;">Arrive<span class="th-sub">Time (optional)</span></th>
                                <th style="min-width:100px;">Mode<span class="th-sub">Transport type</span></th>
                                <th style="min-width:90px;">Transport (₱)<span class="th-sub">Fare paid</span></th>
                                <th style="min-width:50px;">Half<span class="th-sub">Day?</span></th>
                                <th style="min-width:90px;">Per Diem (₱)<span class="th-sub">Auto-filled</span></th>
                                <th style="min-width:36px;"></th>
                            </tr>
                        </thead>
                        <tbody id="itinBodyDesktop"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" style="text-align:right; color:var(--text-mid);">Totals:</td>
                                <td id="foot-transport" style="text-align:right;">₱0.00</td>
                                <td></td>
                                <td id="foot-perdiem" style="text-align:right;">₱0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Mobile cards — visual only, NO name attributes on inputs ── --}}
                <div class="itin-mobile" id="itinMobileContainer"></div>

                <div id="itin-empty" style="text-align:center; padding:24px; color:var(--text-light); font-size:0.83rem; display:none;">
                    Click <strong>+ Add Row</strong> to add itinerary lines.
                </div>

                @error('lines')<div class="invalid-feedback" style="display:block; margin-top:8px;">{{ $message }}</div>@enderror
            </div>
        </div>

    </div>{{-- end left col --}}

    {{-- ── RIGHT PANEL ── --}}
    <div class="tev-right-panel">

        <div class="totals-panel">
            <div style="font-size:0.70rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; opacity:0.65; margin-bottom:10px;">Running Totals</div>
            <div class="totals-row"><span>Transportation</span><span id="tot-transport">₱0.00</span></div>
            <div class="totals-row"><span>Per Diem</span><span id="tot-perdiem">₱0.00</span></div>
            <div class="totals-row totals-grand" style="margin-top:8px; padding-top:8px; border-top:1px solid rgba(255,255,255,0.25);">
                <span>Grand Total</span><span id="tot-grand">₱0.00</span>
            </div>
        </div>

        <div id="perdiem-info" style="display:none; background:#f0f8f2; border:1px solid #a5d6b5; border-radius:8px; padding:12px 14px; font-size:0.78rem; color:#1B5E20;">
            <div style="font-weight:700; margin-bottom:6px;">💡 Per Diem Rates (auto-applied)</div>
            <div id="perdiem-daily"></div>
            <div id="perdiem-half" style="color:#555; margin-top:2px;"></div>
        </div>

        <div class="card">
            <div class="card-body">
                <div style="font-size:0.78rem; color:var(--text-mid); margin-bottom:14px; line-height:1.5;">
                    Review all sections before submitting.<br>
                    This TEV will go <strong>directly to the Accountant</strong> — no extra step required.
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">📤 Submit TEV Request</button>
                <a href="{{ route('tev.requests.index') }}" class="btn btn-outline" style="width:100%; margin-top:8px; display:block; text-align:center;">Cancel</a>
            </div>
        </div>

        <div style="background:#fff; border:1px solid var(--border); border-radius:8px; padding:12px 14px; font-size:0.76rem; line-height:1.7; color:var(--text-mid);">
            <div style="font-weight:700; color:var(--navy); margin-bottom:6px;">✅ Before you submit:</div>
            <div>☐ Office Order selected</div>
            <div>☐ Track chosen (CA or Reimb.)</div>
            <div>☐ Travel dates set</div>
            <div>☐ Purpose &amp; destination filled</div>
            <div>☐ At least one itinerary row added</div>
            <div>☐ From/To are <em>place names</em>, not dates</div>
            <div>☐ Mode of transport selected per row</div>
        </div>

    </div>{{-- end right panel --}}

</div>{{-- end grid --}}
</form>

@endsection

@section('scripts')
<script>
(function () {
    'use strict';

    var PER_DIEM_RATES = {!! $ratesJson !!};
    var currentType    = document.getElementById('travel_type').value || 'local';
    var rowsData       = [];

    // ── Cached OO date start (for auto-filling first itinerary row) ───────
    var ooDateStart = '';

    // ── Example accordion (mobile) ────────────────────────────────────────
    var exToggle = document.getElementById('itinExampleToggle');
    var exBody   = document.getElementById('itinExampleBody');
    if (exToggle && exBody) {
        exToggle.addEventListener('click', function () {
            var isOpen = exBody.classList.toggle('open');
            exToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    // ── Office Order auto-fill ────────────────────────────────────────────
    var ooSelect = document.getElementById('office_order_id');

    function applyOoSelection() {
        if (!ooSelect) return;
        var opt = ooSelect.options[ooSelect.selectedIndex];
        if (!opt || !opt.value) {
            document.getElementById('oo-preview').style.display  = 'none';
            document.getElementById('perdiem-info').style.display = 'none';
            ooDateStart = '';
            return;
        }
        var dest = opt.getAttribute('data-destination') || '';
        var type = opt.getAttribute('data-travel-type') || 'local';
        var purp = opt.getAttribute('data-purpose')     || '';
        var dS   = opt.getAttribute('data-date-start')  || '';
        var dE   = opt.getAttribute('data-date-end')    || '';

        // Store OO start date for itinerary auto-fill
        ooDateStart = dS;

        document.getElementById('oo-destination').textContent  = dest;
        document.getElementById('oo-travel-type').textContent  = type.charAt(0).toUpperCase() + type.slice(1);
        document.getElementById('oo-purpose').textContent      = purp;
        document.getElementById('oo-dates').textContent        = dS + ' → ' + dE;
        document.getElementById('oo-preview').style.display    = 'block';

        document.getElementById('destination').value    = dest;
        document.getElementById('purpose').value        = purp;
        document.getElementById('travel_type').value    = type;
        if (dS) document.getElementById('travel_date_start').value = dS;
        if (dE) document.getElementById('travel_date_end').value   = dE;
        if (dS) document.getElementById('travel_date_end').setAttribute('min', dS);

        currentType = type;

        var rates = PER_DIEM_RATES[type];
        if (rates) {
            document.getElementById('oo-perdiem-hint').textContent =
                '(₱' + rates.daily.toLocaleString() + '/day per diem)';
            document.getElementById('perdiem-daily').textContent =
                'Full day: ₱' + rates.daily.toLocaleString('en-PH', {minimumFractionDigits:2});
            document.getElementById('perdiem-half').textContent  =
                'Half day: ₱' + rates.half_day.toLocaleString('en-PH', {minimumFractionDigits:2});
            document.getElementById('perdiem-info').style.display = 'block';
        }

        // ── Auto-fill travel_date on the FIRST itinerary row from OO start date ──
        if (dS && rowsData.length > 0) {
            rowsData[0].travel_date = dS;
            renderAllRows();
        }

        refreshAllPerDiem();
    }

if (ooSelect) {
        ooSelect.addEventListener('change', applyOoSelection);
        if (ooSelect.value) applyOoSelection();
    }

    // ── Track radio styling ───────────────────────────────────────────────
    document.querySelectorAll('.track-card input[type="radio"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.track-card').forEach(function (c) {
                var r = c.querySelector('input[type="radio"]');
                c.classList.remove('selected-cash', 'selected-reimb');
                if (r.checked) c.classList.add(r.value === 'cash_advance' ? 'selected-cash' : 'selected-reimb');
            });
        });
    });

document.getElementById('travel_date_start').addEventListener('change', function () {
    var endInput = document.getElementById('travel_date_end');
    endInput.setAttribute('min', this.value);
    if (endInput.value && endInput.value < this.value) {
        endInput.value = '';
    }

    // ── Auto-fill the first itinerary row's date ──
    if (this.value && rowsData.length > 0) {
        rowsData[0].travel_date = this.value;
        renderAllRows();
    }
});

    // ── Per diem helpers ──────────────────────────────────────────────────
    function getDailyRate(half) {
        var rates = PER_DIEM_RATES[currentType];
        if (!rates) return 0;
        return half ? rates.half_day : rates.daily;
    }
    function refreshAllPerDiem() {
        rowsData.forEach(function (row) {
            row.per_diem_amount = getDailyRate(row.is_half_day || false);
        });
        renderAllRows();
        updateTotals();
    }

    // ── Totals ────────────────────────────────────────────────────────────
    function fmt(n) {
        return '₱' + n.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
    }
    function updateTotals() {
        var transport = 0, perdiem = 0;
        rowsData.forEach(function (r) {
            transport += parseFloat(r.transportation_cost) || 0;
            perdiem   += parseFloat(r.per_diem_amount)     || 0;
        });
        document.getElementById('tot-transport').textContent  = fmt(transport);
        document.getElementById('tot-perdiem').textContent    = fmt(perdiem);
        document.getElementById('tot-grand').textContent      = fmt(transport + perdiem);
        document.getElementById('foot-transport').textContent = fmt(transport);
        document.getElementById('foot-perdiem').textContent   = fmt(perdiem);
        document.getElementById('itin-empty').style.display   = rowsData.length === 0 ? 'block' : 'none';
    }

    // ── Update a single field in rowsData then sync hidden inputs ─────────
    function updateRowValue(idx, field, value) {
        if (!rowsData[idx]) return;
        rowsData[idx][field] = value;
        if (field === 'is_half_day') {
            rowsData[idx].per_diem_amount = getDailyRate(value);
            syncRowDisplayPerdiem(idx);
        }
        syncHiddenInputs();
        updateTotals();
    }

    // Sync only the per-diem display fields for a given row
    function syncRowDisplayPerdiem(idx) {
        var row = rowsData[idx];
        if (!row) return;
        var dr = document.getElementById('desktop-row-' + idx);
        if (dr) {
            var pd = dr.querySelector('.itin-perdiem');
            if (pd) pd.value = row.per_diem_amount;
        }
        var mc = document.getElementById('mobile-card-' + idx);
        if (mc) {
            var mpd = mc.querySelector('.itin-mobile-perdiem');
            if (mpd) mpd.value = row.per_diem_amount;
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // HIDDEN INPUT SYNC
    // ══════════════════════════════════════════════════════════════════════
    function syncHiddenInputs() {
        var container = document.getElementById('hiddenLineInputs');
        container.innerHTML = '';
        var fields = [
            'travel_date', 'origin', 'destination', 'departure_time',
            'arrival_time', 'mode_of_transport', 'transportation_cost',
            'per_diem_amount', 'is_half_day'
        ];
        rowsData.forEach(function (row, idx) {
            fields.forEach(function (field) {
                var inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = 'lines[' + idx + '][' + field + ']';
                if (field === 'is_half_day') {
                    inp.value = row.is_half_day ? '1' : '';
                } else {
                    inp.value = row[field] !== undefined ? row[field] : '';
                }
                container.appendChild(inp);
            });
        });
    }

    // ── Render all rows (visual only — no name attributes) ───────────────
    function renderAllRows() {
        var dtb  = document.getElementById('itinBodyDesktop');
        var mob  = document.getElementById('itinMobileContainer');
        dtb.innerHTML = ''; mob.innerHTML = '';
        var modes = ['bus','jeepney','rented van','e-bike','motorcycle','boat','plane','vehicle','other'];

        rowsData.forEach(function (row, idx) {
            var modeOpts = modes.map(function (m) {
                var sel = (row.mode_of_transport || '').toLowerCase() === m ? ' selected' : '';
                return '<option value="' + m + '"' + sel + '>' + m.charAt(0).toUpperCase() + m.slice(1) + '</option>';
            }).join('');

            // ── Desktop row (NO name attributes) ──
            var tr = document.createElement('tr');
            tr.id  = 'desktop-row-' + idx;
            tr.innerHTML =
                '<td><input type="date"   data-field="travel_date"         value="'+(row.travel_date||'')+'"></td>'+
                '<td><input type="text"   data-field="origin"              value="'+escHtml(row.origin||'')+'" placeholder="e.g. DOLE RO9 Office"></td>'+
                '<td><input type="text"   data-field="destination"         value="'+escHtml(row.destination||'')+'" placeholder="e.g. Dipolog City"></td>'+
                '<td><input type="time"   data-field="departure_time"      value="'+(row.departure_time||'')+'"></td>'+
                '<td><input type="time"   data-field="arrival_time"        value="'+(row.arrival_time||'')+'"></td>'+
                '<td><select data-field="mode_of_transport"><option value="">— select —</option>'+modeOpts+'</select></td>'+
                '<td><input type="number" data-field="transportation_cost" value="'+(row.transportation_cost||0)+'" step="0.01" min="0" class="itin-transport" placeholder="0.00"></td>'+
                '<td style="text-align:center;"><input type="checkbox" data-field="is_half_day" class="itin-halfday"'+(row.is_half_day?' checked':'')+'></td>'+
                '<td><input type="number" data-field="per_diem_amount"     value="'+(row.per_diem_amount||0)+'" step="0.01" min="0" class="itin-perdiem" placeholder="0.00"></td>'+
                '<td><button type="button" class="btn btn-sm btn-danger" onclick="tevRemoveRow('+idx+')" style="padding:3px 8px;">✕</button></td>';

            tr.querySelectorAll('[data-field]').forEach(function(el) {
                var field = el.getAttribute('data-field');
                var evt   = (el.type === 'checkbox') ? 'change' : 'input';
                el.addEventListener(evt, function() {
                    var val = (el.type === 'checkbox') ? el.checked : el.value;
                    updateRowValue(idx, field, val);
                    var mc = document.getElementById('mobile-card-' + idx);
                    if (mc) {
                        var mEl = mc.querySelector('[data-field="' + field + '"]');
                        if (mEl && mEl !== el) {
                            if (mEl.type === 'checkbox') mEl.checked = el.checked;
                            else mEl.value = el.value;
                        }
                    }
                });
            });
            dtb.appendChild(tr);

            // ── Mobile card (NO name attributes) ──
            var card = document.createElement('div');
            card.className = 'itin-card';
            card.id        = 'mobile-card-' + idx;
            card.innerHTML =
                '<div class="itin-card-header">'+
                    '<span class="itin-card-date">Row '+(idx+1)+(row.travel_date?' · '+row.travel_date:'')+' </span>'+
                    '<button type="button" class="itin-card-remove" onclick="tevRemoveRow('+idx+')">✕ Remove</button>'+
                '</div>'+
                '<div class="itin-card-row">'+
                    '<div class="itin-card-field"><label>Travel Date *</label><input type="date" data-field="travel_date" value="'+(row.travel_date||'')+'"></div>'+
                    '<div class="itin-card-field"><label>Mode *</label><select data-field="mode_of_transport"><option value="">— select —</option>'+modeOpts+'</select></div>'+
                '</div>'+
                '<div class="itin-card-field"><label>From (Origin) *</label><input type="text" data-field="origin" value="'+escHtml(row.origin||'')+'" placeholder="e.g. DOLE RO9 Office"><p class="field-hint">Place name where you departed from</p></div>'+
                '<div class="itin-card-field"><label>To (Destination) *</label><input type="text" data-field="destination" value="'+escHtml(row.destination||'')+'" placeholder="e.g. Dipolog City"><p class="field-hint">Place name where you arrived</p></div>'+
                '<div class="itin-card-row">'+
                    '<div class="itin-card-field"><label>Depart Time</label><input type="time" data-field="departure_time" value="'+(row.departure_time||'')+'"></div>'+
                    '<div class="itin-card-field"><label>Arrive Time</label><input type="time" data-field="arrival_time"   value="'+(row.arrival_time||'')+'"></div>'+
                '</div>'+
                '<div class="itin-card-row">'+
                    '<div class="itin-card-field"><label>Transport (₱) *</label><input type="number" data-field="transportation_cost" value="'+(row.transportation_cost||0)+'" step="0.01" min="0" class="itin-mobile-transport" placeholder="0.00"><p class="field-hint">Fare paid</p></div>'+
                    '<div class="itin-card-field"><label>Per Diem (₱) *</label><input type="number" data-field="per_diem_amount"     value="'+(row.per_diem_amount||0)+'"    step="0.01" min="0" class="itin-mobile-perdiem"   placeholder="0.00"><p class="field-hint">Auto-filled</p></div>'+
                '</div>'+
                '<div class="itin-card-halfday">'+
                    '<input type="checkbox" data-field="is_half_day" class="itin-mobile-halfday"'+(row.is_half_day?' checked':'')+'>'+
                    '<label>Half Day (reduces per diem to half rate)</label>'+
                '</div>';

            card.querySelectorAll('[data-field]').forEach(function(el) {
                var field = el.getAttribute('data-field');
                var evt   = (el.type === 'checkbox') ? 'change' : 'input';
                el.addEventListener(evt, function() {
                    var val = (el.type === 'checkbox') ? el.checked : el.value;
                    updateRowValue(idx, field, val);
                    var dr = document.getElementById('desktop-row-' + idx);
                    if (dr) {
                        var dEl = dr.querySelector('[data-field="' + field + '"]');
                        if (dEl && dEl !== el) {
                            if (dEl.type === 'checkbox') dEl.checked = el.checked;
                            else dEl.value = el.value;
                        }
                    }
                });
            });
            mob.appendChild(card);
        });

        syncHiddenInputs();
        updateTotals();
    }

    // ── Add / Remove ──────────────────────────────────────────────────────
    function addRow(data) {
        data = data || {};
        var autoDate = data.travel_date || '';
        if (!autoDate && rowsData.length === 0 && ooDateStart) {
            autoDate = ooDateStart;
        }
        rowsData.push({
            travel_date:         autoDate,
            origin:              data.origin              || '',
            destination:         data.destination         || '',
            departure_time:      data.departure_time      || '',
            arrival_time:        data.arrival_time        || '',
            mode_of_transport:   data.mode_of_transport   || '',
            transportation_cost: data.transportation_cost || 0,
            per_diem_amount:     data.per_diem_amount !== undefined ? data.per_diem_amount : getDailyRate(false),
            is_half_day:         data.is_half_day         || false,
        });
        renderAllRows();
    }
    window.tevRemoveRow = function(idx) { rowsData.splice(idx, 1); renderAllRows(); };
    document.getElementById('addRowBtn').addEventListener('click', function(){ addRow({}); });

    function escHtml(str) {
        if (!str) return '';
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Repopulate on validation redirect ─────────────────────────────────
    @if (old('lines'))
        @foreach (old('lines') as $i => $line)
            addRow({
                travel_date:         '{{ $line['travel_date']         ?? '' }}',
                origin:              '{!! addslashes($line['origin']          ?? '') !!}',
                destination:         '{!! addslashes($line['destination']     ?? '') !!}',
                departure_time:      '{{ $line['departure_time']      ?? '' }}',
                arrival_time:        '{{ $line['arrival_time']        ?? '' }}',
                mode_of_transport:   '{{ $line['mode_of_transport']   ?? '' }}',
                transportation_cost: '{{ $line['transportation_cost'] ?? 0 }}',
                per_diem_amount:     '{{ $line['per_diem_amount']     ?? 0 }}',
                is_half_day:         {{ !empty($line['is_half_day']) ? 'true' : 'false' }},
            });
        @endforeach
    @else
        addRow({});
    @endif

    updateTotals();
})();
</script>
@endsection
