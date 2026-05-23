<?php

namespace Modules\Tev\Policies;

use App\Models\User;
use Modules\Tev\Models\TevRequest;
use App\SharedKernel\Enums\Permission;

class TevPolicy
{
    /**
     * Determine if the user can view TEV requests.
     */
    public function view(User $user, TevRequest $tev): bool
    {
        return $user->can(Permission::TEV_VOUCHERS_VIEW->value);
    }

    /**
     * Determine if the user can view any TEV requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::TEV_VOUCHERS_VIEW->value);
    }

    /**
     * Determine if the user can create TEV requests.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::TEV_VOUCHERS_CREATE->value);
    }

    /**
     * Determine if the user can update TEV requests.
     */
    public function update(User $user, TevRequest $tev): bool
    {
        return $user->can(Permission::TEV_VOUCHERS_CREATE->value);
    }

    /**
     * Determine if the user can approve TEV requests.
     */
    public function approve(User $user, TevRequest $tev): bool
    {
        return $user->can(Permission::TEV_VOUCHERS_APPROVE->value);
    }

    /**
     * Determine if the user can certify TEV requests.
     */
    public function certify(User $user, TevRequest $tev): bool
    {
        return $user->can(Permission::TEV_VOUCHERS_CERTIFY->value);
    }

    /**
     * Determine if the user can disburse (release cash advances) for TEV requests.
     */
    public function disburse(User $user, TevRequest $tev): bool
    {
        return $user->can(Permission::TEV_VOUCHERS_DISBURSE->value);
    }

    /**
     * Determine if the user can delete TEV requests (not permitted).
     */
    public function delete(User $user, TevRequest $tev): bool
    {
        return false;
    }
}
