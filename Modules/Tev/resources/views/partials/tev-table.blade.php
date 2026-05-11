{{-- TEV Table Partial --}}
<div class="table-wrap">
    <table class="sd-table">
        <thead>
            <tr>
                <th>TEV No.</th>
                <th>Employee</th>
                <th>Track</th>
                <th>Office Order</th>
                <th>Travel Dates</th>
                <th class="text-right">Grand Total</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tevRequests as $tev)
                @php
                    $emp = $tev->employee;

                    $trackLabel = $tev->track === 'cash_advance' ? 'Cash Advance' : 'Reimbursement';
                    $trackStyle = $tev->track === 'cash_advance'
                        ? 'background:#E8F5E9; color:#1B5E20; border:1px solid #43A047;'
                        : 'background:#E8EAF6; color:#1A237E; border:1px solid #3949AB;';

                    $statusClass = match ($tev->status) {
                        'submitted'            => 'badge-pending',
                        'accountant_certified' => 'badge-computed',
                        'rd_approved'          => 'badge-released',
                        'cashier_released'     => 'badge-locked',
                        'reimbursed'           => 'badge-locked',
                        'liquidated'           => 'badge-locked',
                        'liquidation_filed'    => 'badge-pending',
                        'rejected'             => 'badge-inactive',
                        default                => 'badge-draft',
                    };
                    $statusLabel = ucwords(str_replace('_', ' ', $tev->status));

                    $isOwner  = $emp && ($emp->user_id === auth()->id() || $emp->employee_id === session('hris_employee_id'));
                    $canSubmit = $tev->status === 'draft'
                        && ($isOwner || auth()->user()->hasAnyRole(['payroll_officer', 'hrmo']));
                @endphp

                {{-- ── Main visible row ── --}}
                <tr class="sd-main-row" data-id="{{ $tev->id }}" onclick="toggleSdRow(this)">

                    <td class="col-tev fw-bold" style="color:var(--navy); white-space:nowrap;">
                        {{ $tev->tev_no }}
                    </td>

                    <td class="col-employee">
                        <span class="sd-name-label">
                            {{ optional($emp)->last_name }},
                            {{ optional($emp)->first_name }}
                            @if (optional($emp)->middle_name)
                                {{ substr($emp->middle_name, 0, 1) }}.
                            @endif
                        </span>
                        <span class="sd-name-sub">
                            {{ optional($tev->officeOrder)->office_order_no ?? '—' }}
                        </span>
                    </td>

                    <td class="col-track">
                        <span style="font-size:0.72rem; font-weight:700; padding:3px 8px;
                                     border-radius:12px; {{ $trackStyle }}">
                            {{ $trackLabel }}
                        </span>
                    </td>

                    <td class="col-oo" style="font-size:0.82rem;">
                        {{ optional($tev->officeOrder)->office_order_no ?? '—' }}
                    </td>

                    <td class="col-dates text-muted" style="font-size:0.82rem; white-space:nowrap;">
                        {{ $tev->travel_date_start->format('M d') }}
                        –
                        {{ $tev->travel_date_end->format('M d, Y') }}
                    </td>

                    <td class="col-total text-right fw-bold">
                        ₱{{ number_format($tev->grand_total, 2) }}
                    </td>

                    <td class="col-status">
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>

                    <td class="col-actions">
                        <div class="d-flex gap-2" style="justify-content:center;">
                            <a href="{{ route('tev.requests.show', $tev->id) }}"
                               class="btn btn-outline btn-sm"
                               onclick="event.stopPropagation();">View</a>

                            @if ($canSubmit)
                                <form method="POST"
                                      action="{{ route('tev.requests.submit', $tev->id) }}"
                                      onsubmit="event.stopPropagation(); return confirm('Submit this TEV for approval?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary"
                                            onclick="event.stopPropagation();">Submit</button>
                                </form>
                            @endif
                        </div>

                        {{-- Mobile expand chevron --}}
                        <span class="sd-expand-btn" aria-label="Expand">▼</span>
                    </td>

                </tr>

                {{-- ── Expandable detail row (mobile only) ── --}}
                <tr class="sd-detail-row" id="sd-detail-{{ $tev->id }}">
                    <td colspan="8">
                        <div class="sd-detail-grid">
                            <div class="sd-detail-item">
                                <label>TEV No.</label>
                                <span style="color:var(--navy); font-weight:700;">{{ $tev->tev_no }}</span>
                            </div>
                            <div class="sd-detail-item">
                                <label>Track</label>
                                <span>
                                    <span style="font-size:0.72rem; font-weight:700;
                                                 padding:2px 8px; border-radius:10px;
                                                 {{ $trackStyle }}">{{ $trackLabel }}</span>
                                </span>
                            </div>
                            <div class="sd-detail-item">
                                <label>Office Order</label>
                                <span>{{ optional($tev->officeOrder)->office_order_no ?? '—' }}</span>
                            </div>
                            <div class="sd-detail-item">
                                <label>Grand Total</label>
                                <span class="mono" style="color:var(--navy); font-weight:700;">
                                    ₱{{ number_format($tev->grand_total, 2) }}
                                </span>
                            </div>
                            <div class="sd-detail-item">
                                <label>Travel Start</label>
                                <span>{{ $tev->travel_date_start->format('M d, Y') }}</span>
                            </div>
                            <div class="sd-detail-item">
                                <label>Travel End</label>
                                <span>{{ $tev->travel_date_end->format('M d, Y') }}</span>
                            </div>
                            <div class="sd-detail-item">
                                <label>Status</label>
                                <span><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></span>
                            </div>
                        </div>
                        <div class="sd-detail-actions">
                            <a href="{{ route('tev.requests.show', $tev->id) }}"
                               class="btn btn-outline btn-sm">View</a>

                            @if ($canSubmit)
                                <form method="POST"
                                      action="{{ route('tev.requests.submit', $tev->id) }}"
                                      style="flex:1;"
                                      onsubmit="return confirm('Submit this TEV for approval?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary"
                                            style="width:100%;">Submit</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:40px; color:var(--text-light);">
                        No TEV requests found.
                        <a href="{{ route('tev.requests.create') }}">Create one now →</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($tevRequests->hasPages())
<div style="padding:4px 20px 8px;">
    {{ $tevRequests->links('pagination::custom', ['pageName' => $pageName ?? 'page']) }}
</div>
@endif
