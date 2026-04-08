<?php

namespace App\Livewire;

use App\Modules\Document\Enums\AccessLevel;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Models\DocumentAccess;
use App\Modules\Document\Services\DocumentService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class DocumentAccessManager extends Component
{
    public Document $document;
    public ?int $editingAccessId = null;
    public ?string $editAccessLevel = null;

    public ?int $newUserId = null;
    public string $newAccessLevel = 'read';

    public function mount(Document $document)
    {
        $this->document = $document->load(['sharedAccess.user', 'sharedAccess.grantedBy', 'owner']);
        $this->newAccessLevel = AccessLevel::READ->value;
    }

    public function canManageAccess(): bool
    {
        return Gate::allows('share', $this->document);
    }

    public function getAvailableUsers()
    {
        return \App\Models\User::where('tenant_id', $this->document->tenant_id)
            ->where('id', '!=', $this->document->owner_id)
            ->whereNotIn('id', function ($query) {
                $query->select('user_id')
                    ->from('document_access')
                    ->where('document_id', $this->document->id);
            })
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();
    }

    public function addUser()
    {
        if (!$this->canManageAccess()) {
            Notification::make()
                ->danger()
                ->title('Unauthorized')
                ->body('You do not have permission to manage access.')
                ->send();
            return;
        }

        if (!$this->newUserId) {
            Notification::make()
                ->warning()
                ->title('Validation Error')
                ->body('Please select a user.')
                ->send();
            return;
        }

        $documentService = app(DocumentService::class);
        $targetUser = \App\Models\User::find($this->newUserId);
        $accessLevel = AccessLevel::from($this->newAccessLevel);

        $documentService->shareDocument(
            $this->document,
            $targetUser,
            auth()->user(),
            $accessLevel
        );

        Notification::make()
            ->success()
            ->title('User Added')
            ->body($targetUser->name . ' now has ' . $accessLevel->label() . ' access.')
            ->send();

        $this->newUserId = null;
        $this->newAccessLevel = AccessLevel::READ->value;

        $this->document->refresh()->load(['sharedAccess.user', 'sharedAccess.grantedBy', 'owner']);
    }

    public function startEdit(int $accessId, string $currentLevel)
    {
        $this->editingAccessId = $accessId;
        $this->editAccessLevel = $currentLevel;
    }

    public function cancelEdit()
    {
        $this->editingAccessId = null;
        $this->editAccessLevel = null;
    }

    public function updateAccess(int $accessId)
    {
        $access = DocumentAccess::findOrFail($accessId);

        if (!Gate::allows('share', $access->document)) {
            Notification::make()
                ->danger()
                ->title('Unauthorized')
                ->body('You do not have permission to manage access.')
                ->send();
            return;
        }

        $access->update([
            'access_level' => AccessLevel::from($this->editAccessLevel),
            'granted_by' => auth()->id(),
        ]);

        $this->editingAccessId = null;
        $this->editAccessLevel = null;

        Notification::make()
            ->success()
            ->title('Access Updated')
            ->body('User access level has been updated.')
            ->send();

        $this->document->refresh()->load(['sharedAccess.user', 'sharedAccess.grantedBy']);
    }

    public function deleteAccess(int $accessId)
    {
        $access = DocumentAccess::findOrFail($accessId);

        if (!Gate::allows('share', $access->document)) {
            Notification::make()
                ->danger()
                ->title('Unauthorized')
                ->body('You do not have permission to manage access.')
                ->send();
            return;
        }

        $documentService = app(DocumentService::class);
        $documentService->revokeAccess($access->document, $access->user);

        Notification::make()
            ->success()
            ->title('Access Removed')
            ->body('User access has been removed.')
            ->send();

        $this->document->refresh()->load(['sharedAccess.user', 'sharedAccess.grantedBy']);
    }

    public function render()
    {
        return view('livewire.document-access-manager');
    }
}
