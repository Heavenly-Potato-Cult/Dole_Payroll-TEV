<?php

namespace Modules\Payroll\Policies;

use Modules\Payroll\Models\PayrollBatch;
use App\Models\User;

/**
 * PayrollPolicy
 *
 * Governs who may perform each action on a PayrollBatch.
 *
 * Approval chain:
 *   draft / computed  ──(payroll_officer | hrmo)──►  pending_accountant
 *   pending_accountant ──(accountant)──────────────►  pending_rd
 *   pending_rd         ──(ard)────────────────────►   released
 *   released           ──(cashier)─────────────────►  locked
 *
 * Register in AuthServiceProvider (or app/Providers/AppServiceProvider.php
 * if using Laravel 11 bootstrapped app):
 *
 *   Gate::policy(PayrollBatch::class, PayrollPolicy::class);
 *
 * Usage in controllers:
 *   $this->authorize('submit', $batch);
 *   $this->authorize('certify', $batch);
 *   $this->authorize('approve', $batch);
 *   $this->authorize('lock', $batch);
 *   $this->authorize('forceEdit', $batch);
 */
class PayrollPolicy
{
    // ── View ──────────────────────────────────────────────────────────────

    /**
     * Any authenticated user with a payroll-related role can view batches.
     */
    public function viewAny(User $user): bool
    {
        return \App\SharedKernel\Services\RoleService::canAccessPayroll($user);
    }

    public function view(User $user, PayrollBatch $batch): bool
    {
        return $this->viewAny($user);
    }

    // ── Create / Delete ───────────────────────────────────────────────────

    public function create(User $user): bool
    {
        return \App\SharedKernel\Services\RoleService::canCreatePayroll($user);
    }

    /**
     * Only drafts may be deleted, only by Payroll Officer.
     */
    public function delete(User $user, PayrollBatch $batch): bool
    {
        return \App\SharedKernel\Services\RoleService::isPayrollOfficerOrAdmin($user)
            && $batch->status === 'draft';
    }

    // ── Compute ───────────────────────────────────────────────────────────

    /**
     * HR / Payroll Officer may (re-)compute as long as the batch is not locked.
     */
    public function compute(User $user, PayrollBatch $batch): bool
    {
        return (\App\SharedKernel\Services\RoleService::isPayrollOfficer($user)
                || \App\SharedKernel\Services\RoleService::isHrmo($user)
                || \App\SharedKernel\Services\RoleService::isSuperAdmin($user))
            && $batch->status !== 'locked';
    }

    // ── Step 1: HR submits to Accountant ─────────────────────────────────

    /**
     * Payroll Officer or HRMO may submit a draft/computed batch to the Accountant.
     *
     * Triggered by: POST /payroll/{id}/submit
     * Transitions:  draft | computed  →  pending_accountant
     */
    public function submit(User $user, PayrollBatch $batch): bool
    {
        return (\App\SharedKernel\Services\RoleService::isPayrollOfficer($user)
                || \App\SharedKernel\Services\RoleService::isHrmo($user)
                || \App\SharedKernel\Services\RoleService::isSuperAdmin($user))
            && in_array($batch->status, ['draft', 'computed'], true);
    }

    // ── Step 2: Accountant certifies funds and forwards to RD ────────────

    /**
     * Accountant certifies funds available and advances to RD review.
     *
     * Triggered by: POST /payroll/{id}/certify   (or reusing approve route)
     * Transitions:  pending_accountant  →  pending_rd
     */
    public function certify(User $user, PayrollBatch $batch): bool
    {
        return \App\SharedKernel\Services\RoleService::isAccountant($user)
            && $batch->status === 'pending_accountant';
    }

    // ── Step 3: ARD / RD approves and releases ────────────────────────────

    /**
     * ARD (or RD) approves the payroll for release.
     *
     * Triggered by: POST /payroll/{id}/approve
     * Transitions:  pending_rd  →  released
     */
    public function approve(User $user, PayrollBatch $batch): bool
    {
        return \App\SharedKernel\Services\RoleService::isApprovalRole($user)
            && $batch->status === 'pending_rd';
    }

    // ── Step 4: Cashier locks after disbursement ──────────────────────────

    /**
     * Cashier locks the payroll once disbursement is complete.
     * A locked payroll is immutable except via forceEdit (admin only).
     *
     * Triggered by: POST /payroll/{id}/lock
     * Transitions:  released  →  locked
     */
    public function lock(User $user, PayrollBatch $batch): bool
    {
        return \App\SharedKernel\Services\RoleService::isCashier($user)
            && $batch->status === 'released';
    }

    // ── Force edit on locked batch (admin only, audit-logged) ────────────

    /**
     * Only Payroll Officer may force-edit a locked batch.
     * Every such edit MUST be audit-logged with a reason by the caller.
     */
    public function forceEdit(User $user, PayrollBatch $batch): bool
    {
        return \App\SharedKernel\Services\RoleService::isPayrollOfficerOrAdmin($user)
            && $batch->status === 'locked';
    }


    
}