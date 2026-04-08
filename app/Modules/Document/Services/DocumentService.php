<?php



namespace App\Modules\Document\Services;

use App\Models\User;
use App\Modules\Document\Enums\AccessLevel;
use App\Modules\Document\Enums\DocumentVisibility;
use App\Modules\Document\Exceptions\CrossTenantAccessException;
use App\Modules\Document\Exceptions\QuotaExceededException;
use App\Modules\Document\Exceptions\UnauthorizedVisibilityException;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Models\DocumentAccess;
use App\Modules\Tenant\Enums\UserRole;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Collection;

class DocumentService
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * @throws QuotaExceededException
     * @throws UnauthorizedVisibilityException
     */
    public function createDocument(
        User $user,
        string $title,
        string $content,
        ?string $category = null,
        DocumentVisibility $visibility = DocumentVisibility::PRIVATE
    ): Document {
        $tenant = $user->tenant;

        $this->validateQuota($tenant);
        $this->validateVisibilityPermission($user, $visibility);

        $document = Document::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'owner_id' => $user->id,
            'title' => $title,
            'content' => $content,
            'category' => $category,
            'visibility' => $visibility,
        ]);

        return $document->fresh();
    }

    /**
     * @throws UnauthorizedVisibilityException
     */
    public function updateDocument(
        Document $document,
        User $user,
        array $data
    ): Document {
        if (isset($data['visibility'])) {
            $newVisibility = DocumentVisibility::from($data['visibility']);
            $this->validateVisibilityPermission($user, $newVisibility);
            $data['visibility'] = $newVisibility;
        }

        $data['user_id'] = $user->id;

        $document->update($data);

        return $document->fresh();
    }

    /**
     * @throws QuotaExceededException
     */
    private function validateQuota(Tenant $tenant): void
    {
        if (!$this->subscriptionService->canCreateDocument($tenant)) {
            $quotaInfo = $this->subscriptionService->getQuotaInfo($tenant);

            throw new QuotaExceededException(
                sprintf(
                    'Document quota exceeded. Your %s plan allows maximum %d documents. Please upgrade to Premium for unlimited documents.',
                    $quotaInfo['tier_name'],
                    $quotaInfo['max']
                )
            );
        }
    }

    /**
     * @throws UnauthorizedVisibilityException
     */
    private function validateVisibilityPermission(
        User $user,
        DocumentVisibility $visibility
    ): void {
        if ($visibility !== DocumentVisibility::PRIVATE) {
            return;
        }

        if ($user->role === UserRole::STAFF) {
            throw new UnauthorizedVisibilityException(
                'Staff members cannot create private documents. Only Manager, Director, or Head can create private documents.'
            );
        }
    }

    public function getDecryptedContent(Document $document): string
    {
        return $document->content ?? '[No content]';
    }

    /**
     * @throws CrossTenantAccessException
     */
    public function shareDocument(
        Document $document,
        User $targetUser,
        User $sharingUser,
        AccessLevel $accessLevel = AccessLevel::READ
    ): DocumentAccess {
        if ($document->tenant_id !== $targetUser->tenant_id) {
            throw new CrossTenantAccessException('Cannot share document across tenants');
        }

        $existingAccess = DocumentAccess::where('document_id', $document->id)
            ->where('user_id', $targetUser->id)
            ->first();

        if ($existingAccess) {
            $existingAccess->update([
                'access_level' => $accessLevel,
                'granted_by' => $sharingUser->id,
            ]);

            return $existingAccess->fresh();
        }

        return DocumentAccess::create([
            'document_id' => $document->id,
            'tenant_id' => $document->tenant_id,
            'user_id' => $targetUser->id,
            'access_level' => $accessLevel,
            'granted_by' => $sharingUser->id,
        ]);
    }

    public function revokeAccess(Document $document, User $user): bool
    {
        return DocumentAccess::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    public function getAccessibleDocuments(User $user): Collection
    {
        $tenantId = $user->tenant_id;

        return Document::where('tenant_id', $tenantId)
            ->where(function ($query) use ($user) {
                $query
                    ->where('owner_id', $user->id)
                    ->orWhere('visibility', DocumentVisibility::PUBLIC)
                    ->orWhereHas('sharedAccess', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->with(['owner', 'lastEditor', 'sharedAccess'])
            ->latest()
            ->get();
    }

    public function canView(User $user, Document $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        if ($document->owner_id === $user->id) {
            return true;
        }

        if ($document->visibility === DocumentVisibility::PUBLIC) {
            return true;
        }

        return DocumentAccess::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function canEdit(User $user, Document $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        if ($document->owner_id === $user->id) {
            return true;
        }

        $access = DocumentAccess::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$access) {
            return false;
        }

        return in_array($access->access_level, [AccessLevel::EDIT, AccessLevel::FULL], true);
    }

    public function canDelete(User $user, Document $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        if ($document->owner_id === $user->id) {
            return true;
        }

        $access = DocumentAccess::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->first();

        return $access && $access->access_level === AccessLevel::FULL;
    }

    public function deleteDocument(Document $document): bool
    {
        return $document->delete();
    }

    public function getUserDocumentStats(User $user): array
    {
        $tenantId = $user->tenant_id;

        return [
            'owned' => Document::where('tenant_id', $tenantId)
                ->where('owner_id', $user->id)
                ->count(),
            'shared_with_me' => DocumentAccess::where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->count(),
            'public_accessible' => Document::where('tenant_id', $tenantId)
                ->where('visibility', DocumentVisibility::PUBLIC)
                ->where('owner_id', '!=', $user->id)
                ->count(),
            'private_owned' => Document::where('tenant_id', $tenantId)
                ->where('owner_id', $user->id)
                ->where('visibility', DocumentVisibility::PRIVATE)
                ->count(),
        ];
    }

    public function getDocumentAccessList(Document $document): Collection
    {
        return DocumentAccess::where('document_id', $document->id)
            ->with(['user', 'grantedBy'])
            ->get();
    }

    public function bulkShareDocument(
        Document $document,
        array $userIds,
        User $sharingUser,
        AccessLevel $accessLevel = AccessLevel::READ
    ): int {
        $sharedCount = 0;

        foreach ($userIds as $userId) {
            $targetUser = User::find($userId);

            if ($targetUser && $targetUser->tenant_id === $document->tenant_id) {
                $this->shareDocument($document, $targetUser, $sharingUser, $accessLevel);
                $sharedCount++;
            }
        }

        return $sharedCount;
    }

    public function hasAIGenerateAccess(User $user): bool
    {
        return $this->subscriptionService->hasAIGenerate($user->tenant);
    }
}
