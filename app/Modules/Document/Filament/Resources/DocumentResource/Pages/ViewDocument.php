<?php

namespace App\Modules\Document\Filament\Resources\DocumentResource\Pages;

use App\Modules\Document\Enums\AccessLevel;
use App\Modules\Document\Exceptions\UnauthorizedActionException;
use App\Modules\Document\Filament\Resources\DocumentResource\DocumentResource;
use App\Modules\Document\Models\DocumentAccess;
use App\Modules\Document\Services\DocumentService;
use Filament\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        if (!Gate::allows('view', $this->record)) {
            Notification::make()
                ->danger()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki izin untuk melihat dokumen ini.')
                ->persistent()
                ->send();

            $this->redirect(static::getResource()::getUrl('index'), navigate: false);
            return;
        }

        $this->authorizeAccess();

        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $documentService = app(DocumentService::class);
        $canShare = Gate::allows('share', $this->record);

        return [
            Actions\EditAction::make()
                ->visible(fn () => $documentService->canEdit($user, $this->record)),

            Actions\Action::make('share')
                ->label('Manage Access')
                ->icon('heroicon-o-users')
                ->color('success')
                ->visible($canShare)
                ->modalHeading('Manage Document Access')
                ->modalDescription('Grant access to users in your tenant')
                ->form([
                    Repeater::make('current_shares')
                        ->label('Currently Shared With')
                        ->schema([
                            Select::make('user_id')
                                ->label('User')
                                ->options(function () {
                                    $record = $this->record;
                                    return \App\Models\User::where('tenant_id', $record->tenant_id)
                                        ->pluck('name', 'id');
                                })
                                ->disabled()
                                ->dehydrated(false)
                                ->required()
                                ->native(false),

                            Select::make('access_level')
                                ->label('Access Level')
                                ->options(function () {
                                    $options = AccessLevel::getOptions();
                                    $result = [];
                                    foreach ($options as $option) {
                                        $result[$option['value']] = $option['label'];
                                    }
                                    return $result;
                                })
                                ->helperText(function ($state) {
                                    if (!$state) {
                                        return null;
                                    }
                                    $level = AccessLevel::from($state);
                                    return $level->description();
                                })
                                ->required()
                                ->native(false)
                                ->live(),
                        ])
                        ->columns(2)
                        ->default(function () use ($documentService) {
                            $shares = $documentService->getDocumentAccessList($this->record);
                            return $shares->map(function ($access) {
                                return [
                                    'id' => $access->id,
                                    'user_id' => $access->user_id,
                                    'access_level' => $access->access_level->value,
                                ];
                            })->toArray();
                        })
                        ->reorderable(false)
                        ->deletable(true)
                        ->addable(false)
                        ->minItems(0)
                        ->defaultItems(0)
                        ->hidden(fn () => $this->record->sharedAccess->isEmpty())
                        ->columnSpanFull(),

                    Repeater::make('shares')
                        ->label('Add Users')
                        ->schema([
                            Select::make('user_id')
                                ->label('User')
                                ->options(function () {
                                    $record = $this->record;
                                    return \App\Models\User::where('tenant_id', $record->tenant_id)
                                        ->where('id', '!=', $record->owner_id)
                                        ->whereNotIn('id', function ($query) use ($record) {
                                            $query->select('user_id')
                                                ->from('document_access')
                                                ->where('document_id', $record->id);
                                        })
                                        ->pluck('name', 'id');
                                })
                                ->required()
                                ->searchable()
                                ->native(false),

                            Select::make('access_level')
                                ->label('Access Level')
                                ->options(function () {
                                    $options = AccessLevel::getOptions();
                                    $result = [];
                                    foreach ($options as $option) {
                                        $result[$option['value']] = $option['label'];
                                    }
                                    return $result;
                                })
                                ->helperText(function ($state) {
                                    if (!$state) {
                                        return null;
                                    }
                                    $level = AccessLevel::from($state);
                                    return $level->description();
                                })
                                ->default(AccessLevel::READ->value)
                                ->required()
                                ->native(false)
                                ->live(),
                        ])
                        ->columns(2)
                        ->minItems(0)
                        ->defaultItems(0)
                        ->addActionLabel('Add Another User')
                        ->reorderable(false),
                ])
                ->action(function (array $data) use ($documentService) {
                    $sharingUser = auth()->user();
                    $hasChanges = false;

                    if (isset($data['current_shares'])) {
                        foreach ($data['current_shares'] as $existingShare) {
                            if (isset($existingShare['id'])) {
                                $access = DocumentAccess::find($existingShare['id']);
                                if ($access) {
                                    $access->update([
                                        'access_level' => AccessLevel::from($existingShare['access_level']),
                                        'granted_by' => $sharingUser->id,
                                    ]);
                                    $hasChanges = true;
                                }
                            }
                        }

                        $existingIds = collect($data['current_shares'])->pluck('id')->filter();
                        $allShares = $this->record->sharedAccess->pluck('id');
                        $deletedIds = $allShares->diff($existingIds);

                        foreach ($deletedIds as $deletedId) {
                            $access = DocumentAccess::find($deletedId);
                            if ($access) {
                                $documentService->revokeAccess($access->document, $access->user);
                                $hasChanges = true;
                            }
                        }
                    } else {
                        if ($this->record->sharedAccess->count() > 0) {
                            foreach ($this->record->sharedAccess as $access) {
                                $documentService->revokeAccess($access->document, $access->user);
                                $hasChanges = true;
                            }
                        }
                    }

                    if (isset($data['shares']) && count($data['shares']) > 0) {
                        foreach ($data['shares'] as $share) {
                            if (isset($share['user_id']) && $share['user_id']) {
                                $targetUser = \App\Models\User::find($share['user_id']);
                                $accessLevel = AccessLevel::from($share['access_level']);

                                $documentService->shareDocument(
                                    $this->record,
                                    $targetUser,
                                    $sharingUser,
                                    $accessLevel
                                );
                                $hasChanges = true;
                            }
                        }
                    }

                    if ($hasChanges) {
                        Notification::make()
                            ->success()
                            ->title('Access Updated')
                            ->body('Document access has been updated successfully.')
                            ->send();
                    }

                    $this->redirect(static::getUrl(['record' => $this->record]));
                }),

            Actions\DeleteAction::make()
                ->visible(fn () => $documentService->canDelete($user, $this->record)),
        ];
    }
}
