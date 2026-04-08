<?php



namespace App\Modules\Document\Policies;

use App\Models\User;
use App\Modules\Document\Models\Document;
use App\Modules\Tenant\Enums\UserRole;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Document $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        if ($user->isHead()) {
            return true;
        }

        if ($document->owner_id === $user->id) {
            return true;
        }

        if ($document->isPublic()) {
            return true;
        }

        return $document->accessPermissions()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->tenant && $user->tenant->canCreateDocument();
    }

    public function createPrivate(User $user): bool
    {
        return $user->role !== UserRole::STAFF;
    }

    public function update(User $user, Document $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        if ($user->isHead()) {
            return true;
        }

        if ($document->owner_id === $user->id) {
            return true;
        }

        $access = $document->accessPermissions()
            ->where('user_id', $user->id)
            ->first();

        return $access && $access->canEdit();
    }

    public function delete(User $user, Document $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        if ($user->isHead()) {
            return true;
        }

        if ($document->owner_id === $user->id) {
            return true;
        }

        $access = $document->accessPermissions()
            ->where('user_id', $user->id)
            ->first();

        return $access && $access->canDelete();
    }

    public function restore(User $user, Document $document): bool
    {
        if ($user->isHead() && $document->tenant_id === $user->tenant_id) {
            return true;
        }

        return $document->owner_id === $user->id;
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $user->isHead() && $document->tenant_id === $user->tenant_id;
    }

    public function share(User $user, Document $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        if ($user->isHead()) {
            return true;
        }

        if ($document->owner_id === $user->id) {
            return true;
        }

        $access = $document->accessPermissions()
            ->where('user_id', $user->id)
            ->first();

        return $access && $access->canShare();
    }

    public function viewVersions(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function restoreVersion(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }
}
