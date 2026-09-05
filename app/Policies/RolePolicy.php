<?php

namespace App\Policies;

use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function delete(User $user): bool
    {
        return $user->can('roles.manage');
    }
}
