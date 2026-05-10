<?php

namespace Modules\Tev\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payroll\Http\Requests\StoreOfficeOrderRequest;  // ← stays in Payroll (shared)
use App\SharedKernel\Models\Employee;
use App\SharedKernel\Models\OfficeOrder;
use Modules\Payroll\Models\PayrollAuditLog;                 // ← stays in Payroll (shared)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficeOrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeRole(['hrmo', 'accountant', 'budget_officer', 'ard', 'chief_admin_officer', 'cashier']);

        $query = OfficeOrder::with('employee')->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

    public function edit(int $id)
    {
        $this->authorizeRole(['hrmo']);

        // Only draft orders are editable — approved/cancelled orders are immutable
        $order = OfficeOrder::where('status', 'draft')->findOrFail($id);

        $employees = Employee::where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name', 'position_title']);

        return view('tev::office-orders.edit', compact('order', 'employees'));
    }

    public function update(StoreOfficeOrderRequest $request, int $id)
    {
        $this->authorizeRole(['hrmo']);

        $order = OfficeOrder::where('status', 'draft')->findOrFail($id);

        // The form request enforces uniqueness on create; on update we re-validate
        // with an ignore clause so the order's own number doesn't trigger a conflict
        $request->validate([
            'office_order_no' => [
                'required', 'string', 'max:50',
                \Illuminate\Validation\Rule::unique('office_orders', 'office_order_no')->ignore($order->id),
            ],
        ]);

        $order->update($request->validated());

        $this->auditLog($request, 'Updated Office Order: ' . $order->office_order_no, null, 'draft');

        return redirect()->route('tev.office-orders.show', $order->id)
            ->with('success', 'Office Order updated successfully.');
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

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function authorizeRole(array $roles): void
    {
        // super_admin bypasses all role checks — view access to all modules
        if (Auth::user()->hasRole('super_admin')) {
            return;
        }

        if (! Auth::user()->hasAnyRole($roles)) {
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
