<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isHead();
    }

    public function view(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $model->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isHead();
    }

    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->canManageUser($model);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->canManageUser($model);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->canManageUser($model);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }
}
