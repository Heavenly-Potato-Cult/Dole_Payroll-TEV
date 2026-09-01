@extends('layouts.app')

@section('title', 'PSIPOP Offices')
@section('page-title', 'PSIPOP Offices')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>PSIPOP Offices</h1>
        <p>DBM Personal Services Itemization &amp; Plantilla of Personnel — fixed section list, in official order</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>All PSIPOP Offices</h3>
        <span class="text-muted" style="font-size:0.82rem;">
            {{ $psipopOffices->count() }} {{ Str::plural('office', $psipopOffices->count()) }}
        </span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:70px;">Order</th>
                    <th>Office Name</th>
                    <th style="width:100px;text-align:center;">Employees</th>
                    <th style="width:100px;text-align:center;">Status</th>
                    <th style="width:110px;text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($psipopOffices as $office)
                <tr>
                    <td class="text-muted" style="font-size:0.80rem;">
                        {{ $office->sort_order }}
                    </td>
                    <td class="fw-bold" style="color:var(--navy);">
                        {{ $office->name }}
                    </td>
                    <td style="text-align:center;">
                        <span class="badge" style="background:var(--navy-light);color:var(--navy);">
                            {{ $office->employees_count }}
                        </span>
                    </td>
                    <td style="text-align:center;">
                        @if ($office->is_active)
                            <span class="badge badge-active">Active</span>
                        @else
                            <span class="badge badge-inactive">Inactive</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        <form method="POST" action="{{ route('psipop-offices.toggle', $office) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="btn btn-sm {{ $office->is_active ? 'btn-outline' : 'btn-primary' }}">
                                {{ $office->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:40px;color:var(--text-light);">
                        No PSIPOP offices found. Run <code>PsipopOfficeSeeder</code> to seed the fixed 7-row list.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:14px;font-size:0.80rem;color:var(--text-light);max-width:640px;">
    This list is fixed by DBM policy — names, order, and count don't change here.
    Deactivating an office (e.g. "NEW PLANTILLA" when unused this cycle) hides it
    from the Employee PSIPOP dropdown without affecting anyone already assigned to it.
</div>

@endsection

@section('scripts')
{{-- No scripts needed — read-only page besides the toggle form above. --}}
@endsection
