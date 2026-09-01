<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\SharedKernel\Models\PsipopOffice;

class PsipopOfficeController extends Controller
{
    /**
     * List all PSIPOP offices — a fixed, DBM-mandated list of 7 rows,
     * seeded once by PsipopOfficeSeeder and confirmed against an actual
     * DOLE PSIPOP export. No search/pagination needed at this size.
     *
     * PsipopOffice's own global scope already orders by sort_order (the
     * DBM-mandated section order), so no explicit orderBy is needed here.
     */
    public function index()
    {
        $psipopOffices = PsipopOffice::withCount('employees')->get();

        return view('payroll::psipop-offices.index', compact('psipopOffices'));
    }

    /**
     * Flip is_active. The ONLY mutation this table allows — no create,
     * edit, or delete. Names/order are DBM-mandated (not ours to rename),
     * and deleting a row would orphan employee FKs or break the
     * "Unassigned" fallback Employee::booted() relies on. Deactivating
     * a section (e.g. "NEW PLANTILLA" when unused this cycle) hides it
     * from future assignment without touching employees already on it.
     */
    public function toggle(PsipopOffice $psipopOffice)
    {
        $psipopOffice->update(['is_active' => ! $psipopOffice->is_active]);

        $status = $psipopOffice->is_active ? 'activated' : 'deactivated';

        return redirect()->route('psipop-offices.index')
            ->with('success', "\"{$psipopOffice->name}\" {$status}.");
    }
}
