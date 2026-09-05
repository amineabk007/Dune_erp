<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view') || $user->can('users.manage');
    }

    public function view(User $user, User $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.manage');
    }

    /**
     * Users are never hard-deleted, only deactivated (kept for order/payment/audit
     * history integrity). Deactivation goes through update(), not delete().
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * A user may not deactivate or edit their own account to avoid self-lockout.
     */
    public function deactivate(User $user, User $model): bool
    {
        return $user->can('users.manage') && $user->isNot($model);
    }
}
