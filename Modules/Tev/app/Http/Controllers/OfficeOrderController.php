<?php

namespace Modules\Tev\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Http\Requests\StoreOfficeOrderRequest;  // ← stays in Payroll (shared)
use App\SharedKernel\Models\Employee;
use App\SharedKernel\Models\OfficeOrder;
use App\SharedKernel\Services\HrisApiService;
use Modules\Payroll\Models\PayrollAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @mixin \Spatie\Permission\Traits\HasRoles
 */
class OfficeOrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeRole(['hrmo', 'accountant', 'budget_officer', 'ard', 'chief_admin_officer', 'cashier']);

        $query = OfficeOrder::with('employee')->orderByDesc('id');

        if ($request->filled('travel_type')) {
            $query->where('travel_type', $request->travel_type);
        }

        if ($request->filled('year')) {
            $query->whereYear('travel_date_start', $request->year);
        }

        $orders      = $query->paginate(20)->withQueryString();
        $currentYear = now()->year;

        return view('tev::office-orders.index', compact('orders', 'currentYear'));
    }

    public function create()
    {
        $this->authorizeRole(['hrmo']);

        $employees = Employee::where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name', 'position_title']);

        return view('tev::office-orders.create', compact('employees'));
    }

    public function store(StoreOfficeOrderRequest $request)
    {
        $this->authorizeRole(['hrmo']);

        $order = OfficeOrder::create(array_merge(
            $request->validated(),
            ['status' => 'draft']
        ));

        $this->auditLog($request, 'Created Office Order: ' . $order->office_order_no, null, 'draft');

        return redirect()->route('tev.office-orders.show', $order->id)
            ->with('success', 'Office Order ' . $order->office_order_no . ' created successfully.');
    }

    public function show(int $id)
    {
        $this->authorizeRole(['hrmo', 'accountant', 'budget_officer', 'ard', 'chief_admin_officer', 'cashier']);

        $order = OfficeOrder::with(['employee', 'approver', 'tevRequests.employee'])->findOrFail($id);

        return view('tev::office-orders.show', compact('order'));
    }

    public function approve(Request $request, int $id)
    {
        $this->authorizeRole(['hrmo', 'ard', 'chief_admin_officer']);

        $order = OfficeOrder::findOrFail($id);

        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft Office Orders can be approved.');
        }

        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $old = $order->status;

        $order->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'remarks'     => $request->remarks ?? $order->remarks,
        ]);

        $this->auditLog($request, 'Approved Office Order: ' . $order->office_order_no, $old, 'approved');

        return redirect()->route('tev.office-orders.show', $order->id)
            ->with('success', 'Office Order approved successfully.');
    }

    /**
     * Cancel an office order.
     *
     * Cancellation is blocked if any TEV requests are linked to the order —
     * those would be left without a parent and must be resolved first.
     */
    public function cancel(Request $request, int $id)
    {
        $this->authorizeRole(['hrmo', 'ard', 'chief_admin_officer']);

        $order = OfficeOrder::withCount('tevRequests')->findOrFail($id);

        if ($order->tev_requests_count > 0) {
            return back()->with('error', 'Cannot cancel: this Office Order has linked TEV requests.');
        }

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Office Order is already cancelled.');
        }

        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $old = $order->status;

        $order->update([
            'status'  => 'cancelled',
            'remarks' => $request->remarks ?? $order->remarks,
        ]);

        $this->auditLog($request, 'Cancelled Office Order: ' . $order->office_order_no, $old, 'cancelled');

        return redirect()->route('tev.office-orders.show', $order->id)
            ->with('success', 'Office Order cancelled.');
    }

    /**
     * Hard deletion is not permitted. Use cancel() to retire an order.
     */
    public function destroy(int $id)
    {
        abort(405);
    }

    /**
     * Pull approved Office Orders from Employee API.
     * Matches employees by employee_no and creates local Office Order records.
     */
    public function pullFromApi(Request $request)
    {
        $this->authorizeRole(['hrmo']);

        try {
            $orders = app(HrisApiService::class)->fetchOfficeOrders();

            Log::info('Office Orders sync starting', [
                'total_from_api' => count($orders),
                'current_db_count' => OfficeOrder::withTrashed()->count(),
            ]);

            $synced   = 0;
            $updated  = 0;
            $skipped  = 0;
            $processed = 0;

            foreach ($orders as $orderData) {
                $processed++;

                // Match employee by employee_no
                // API returns "EMP-0001", local has "EMP001" - normalize by extracting number and reformatting
                preg_match('/([A-Z]+)-0*(\d+)/', $orderData['employee_no'], $matches);
                $normalizedEmployeeNo = $matches[1] . str_pad($matches[2], 3, '0', STR_PAD_LEFT);
                $employee = Employee::where('employee_no', $normalizedEmployeeNo)->first();

                if (! $employee) {
                    Log::warning('Skipping Office Order: employee not found', [
                        'employee_no' => $orderData['employee_no'],
                        'office_order_no' => $orderData['office_order_no'],
                    ]);
                    $skipped++;
                    continue;
                }

                // Create or update Office Order
                $existing = OfficeOrder::where('office_order_no', $orderData['office_order_no'])->first();

                if ($existing) {
                    Log::info('Updating existing Office Order', [
                        'office_order_no' => $orderData['office_order_no'],
                        'existing_id' => $existing->id,
                    ]);

                    $existing->update([
                        'employee_id' => $employee->id,
                        'purpose' => $orderData['purpose'],
                        'destination' => $orderData['destination'],
                        'travel_type' => $orderData['travel_type'],
                        'travel_date_start' => $orderData['travel_date_start'],
                        'travel_date_end' => $orderData['travel_date_end'],
                        'status' => 'approved',
                        'approved_at' => $orderData['approved_at'],
                        'remarks' => $orderData['remarks'] ?? null,
                    ]);
                    $updated++;
                } else {
                    Log::info('Creating new Office Order', [
                        'office_order_no' => $orderData['office_order_no'],
                        'employee_no' => $orderData['employee_no'],
                    ]);

                    OfficeOrder::create([
                        'office_order_no' => $orderData['office_order_no'],
                        'employee_id' => $employee->id,
                        'purpose' => $orderData['purpose'],
                        'destination' => $orderData['destination'],
                        'travel_type' => $orderData['travel_type'],
                        'travel_date_start' => $orderData['travel_date_start'],
                        'travel_date_end' => $orderData['travel_date_end'],
                        'status' => 'approved',
                        'approved_at' => $orderData['approved_at'],
                        'remarks' => $orderData['remarks'] ?? null,
                    ]);
                    $synced++;
                }
            }

            Log::info('Office Orders sync completed', [
                'processed' => $processed,
                'synced' => $synced,
                'updated' => $updated,
                'skipped' => $skipped,
                'final_db_count' => OfficeOrder::withTrashed()->count(),
            ]);

            return redirect()->route('tev.office-orders.index')
                ->with('success', "Synced {$synced} new and updated {$updated} Office Orders from Employee API ({$skipped} skipped).");

        } catch (\Exception $e) {
            Log::error('Office Orders sync failed', ['error' => $e->getMessage()]);

            return redirect()->route('tev.office-orders.index')
                ->with('error', 'Failed to sync Office Orders: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function authorizeRole(array $roles): void
    {
        // super_admin bypasses all role checks — view access to all modules
        /** @var User $user */
        $user = Auth::user();
        if ($user->hasRole('super_admin')) {
            return;
        }

        if (! $user->hasAnyRole($roles)) {
            abort(403);
        }
    }

    /**
     * Write a standard audit log entry for any office order state change.
     * Centralised here to keep action methods free of repetitive boilerplate.
     */
    private function auditLog(Request $request, string $action, ?string $oldValue, string $newValue): void
    {
        PayrollAuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => $action,
            'old_value'  => $oldValue,
            'new_value'  => $newValue,
            'ip_address' => $request->ip(),
        ]);
    }
}
