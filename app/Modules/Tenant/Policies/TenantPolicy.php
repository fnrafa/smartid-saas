<?php

namespace App\Modules\Tenant\Policies;

use App\Models\User;
use App\Modules\Tenant\Models\Tenant;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageTenants() || $user->isHead();
    }

    public function view(User $user, Tenant $tenant): bool
    {
        if ($user->canManageTenants()) {
            return true;
        }

        return $user->tenant_id === $tenant->id;
    }

    public function create(User $user): bool
    {
        return $user->canManageTenants();
    }

    public function update(User $user, Tenant $tenant): bool
    {
        if ($user->canManageTenants()) {
            return true;
        }

        if ($user->isHead() && $user->tenant_id === $tenant->id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        if ($tenant->isSystemTenant()) {
            return false;
        }

        return $user->canManageTenants();
    }

    public function restore(User $user, Tenant $tenant): bool
    {
        return $user->canManageTenants();
    }

    public function forceDelete(User $user, Tenant $tenant): bool
    {
        return $user->canManageTenants();
    }

    public function manageSubscription(User $user, Tenant $tenant): bool
    {
        return $user->canManageTenants();
    }
}
