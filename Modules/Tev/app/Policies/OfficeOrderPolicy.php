<?php

namespace Modules\Tev\Policies;

use App\Models\User;
use App\SharedKernel\Models\OfficeOrder;
use App\SharedKernel\Enums\Permission;

class OfficeOrderPolicy
{
    /**
     * Determine if the user can view office orders.
     */
    public function view(User $user, OfficeOrder $order): bool
    {
        return $user->can(Permission::TEV_OFFICE_ORDERS_VIEW->value);
    }

    /**
     * Determine if the user can view any office orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::TEV_OFFICE_ORDERS_VIEW->value);
    }

    /**
     * Determine if the user can create office orders.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::TEV_OFFICE_ORDERS_PULL->value);
    }

    /**
     * Determine if the user can update office orders.
     */
    public function update(User $user, OfficeOrder $order): bool
    {
        return $user->can(Permission::TEV_OFFICE_ORDERS_VIEW->value);
    }

    /**
     * Determine if the user can pull office orders from API.
     */
    public function pull(User $user): bool
    {
        return $user->can(Permission::TEV_OFFICE_ORDERS_PULL->value);
    }

    /**
     * Determine if the user can approve office orders.
     */
    public function approve(User $user, OfficeOrder $order): bool
    {
        return $order->status === 'draft' 
            && $user->can(Permission::TEV_OFFICE_ORDERS_APPROVE->value);
    }

    /**
     * Determine if the user can cancel office orders.
     */
    public function cancel(User $user, OfficeOrder $order): bool
    {
        return $order->status === 'approved' 
            && $user->can(Permission::TEV_OFFICE_ORDERS_CANCEL->value);
    }

    /**
     * Determine if the user can delete office orders (not permitted).
     */
    public function delete(User $user, OfficeOrder $order): bool
    {
        return false;
    }
}
