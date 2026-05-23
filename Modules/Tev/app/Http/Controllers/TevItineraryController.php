<?php

namespace Modules\Tev\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\Tev\Models\TevItineraryLine;
use Modules\Tev\Models\TevRequest;
use Modules\Tev\Services\TevComputationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \Spatie\Permission\Traits\HasRoles
 */
class TevItineraryController extends Controller
{
    public function __construct(private TevComputationService $tevService) {}

    // =====================================================================
    //  STORE / UPDATE / DESTROY
    // =====================================================================

    /**
     * Append a new itinerary line to a draft TEV.
     *
     * Totals are recomputed after every mutation so the TEV header always
     * reflects the current set of lines without a separate save step.
     */
    public function store(Request $request, int $tevRequest)
    {
        $tev = TevRequest::findOrFail($tevRequest);
        $this->assertDraft($tev);
        $this->authorizeEdit($tev);

        $data = $request->validate([
            'travel_date'         => ['required', 'date'],
            'origin'              => ['required', 'string', 'max:255'],
            'destination'         => ['required', 'string', 'max:255'],
            'departure_time'      => ['nullable', 'date_format:H:i'],
            'arrival_time'        => ['nullable', 'date_format:H:i'],
            'mode_of_transport'   => ['required', 'string', 'max:50'],
            'transportation_cost' => ['required', 'numeric', 'min:0'],
            'per_diem_amount'     => ['required', 'numeric', 'min:0'],
            'is_half_day'         => ['nullable', 'boolean'],
            'remarks'             => ['nullable', 'string', 'max:500'],
        ]);

        TevItineraryLine::create(array_merge($data, [
            'tev_request_id' => $tev->id,
            'is_half_day'    => !empty($data['is_half_day']),
        ]));

        $this->tevService->computeTotals($tev);

        return redirect()->route('tev.show', $tev->id)
            ->with('success', 'Itinerary line added.');
    }

    /**
     * Update an existing itinerary line on a draft TEV.
     *
     * The line is scoped to the parent TEV to prevent cross-TEV tampering
     * via a manipulated line ID in the URL.
     */
    public function update(Request $request, int $tevRequest, int $line)
    {
        $tev      = TevRequest::findOrFail($tevRequest);
        $itinLine = TevItineraryLine::where('tev_request_id', $tev->id)->findOrFail($line);

        $this->assertDraft($tev);
        $this->authorizeEdit($tev);

        $data = $request->validate([
            'travel_date'         => ['required', 'date'],
            'origin'              => ['required', 'string', 'max:255'],
            'destination'         => ['required', 'string', 'max:255'],
            'departure_time'      => ['nullable', 'date_format:H:i'],
            'arrival_time'        => ['nullable', 'date_format:H:i'],
            'mode_of_transport'   => ['required', 'string', 'max:50'],
            'transportation_cost' => ['required', 'numeric', 'min:0'],
            'per_diem_amount'     => ['required', 'numeric', 'min:0'],
            'is_half_day'         => ['nullable', 'boolean'],
            'remarks'             => ['nullable', 'string', 'max:500'],
        ]);

        $itinLine->update(array_merge($data, [
            'is_half_day' => !empty($data['is_half_day']),
        ]));

        $this->tevService->computeTotals($tev);

        return redirect()->route('tev.show', $tev->id)
            ->with('success', 'Itinerary line updated.');
    }

    /**
     * Remove an itinerary line from a draft TEV.
     *
     * As with update(), the line is scoped to its parent TEV before deletion.
     * Totals are recomputed so the TEV header stays consistent.
     */
    public function destroy(int $tevRequest, int $line)
    {
        $tev      = TevRequest::findOrFail($tevRequest);
        $itinLine = TevItineraryLine::where('tev_request_id', $tev->id)->findOrFail($line);

        $this->assertDraft($tev);
        $this->authorizeEdit($tev);

        $itinLine->delete();

        $this->tevService->computeTotals($tev);

        return redirect()->route('tev.show', $tev->id)
            ->with('success', 'Itinerary line removed.');
    }

    // =====================================================================
    //  Private helpers
    // =====================================================================

    /**
     * Abort with 403 if the TEV is no longer in draft status.
     *
     * Itinerary lines are locked once a TEV is submitted to prevent
     * modifications to a record already under review.
     */
    private function assertDraft(TevRequest $tev): void
    {
        if ($tev->status !== 'draft') {
            abort(403, 'Itinerary can only be edited while the TEV is in draft status.');
        }
    }

    /**
     * Abort with 403 if the current user is neither HRMO staff nor the
     * employee who owns the TEV.
     *
     * payroll_officer is intentionally excluded — TEV is managed by a
     * separate department and payroll staff have no filing authority here.
     */
    private function authorizeEdit(TevRequest $tev): void
    {
        /** @var User $user */
        $user    = Auth::user();
        $isStaff = \App\SharedKernel\Services\RoleService::isHrmo($user);
        $isOwner = $tev->employee && $tev->employee->user_id === $user->id;

        if (!$isStaff && !$isOwner) {
            abort(403);
        }
    }
}
