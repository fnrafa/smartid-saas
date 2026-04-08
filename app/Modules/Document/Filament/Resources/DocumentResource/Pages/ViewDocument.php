<?php

namespace App\Modules\Document\Filament\Resources\DocumentResource\Pages;

use App\Modules\Document\Enums\AccessLevel;
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

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $documentService = app(DocumentService::class);
        $canShare = Gate::allows('share', $this->record);

        return [
            Actions\EditAction::make()
                ->visible(fn () => $documentService->canEdit($user, $this->record)),

            Actions\Action::make('share')
                ->label('Share Document')
                ->icon('heroicon-o-share')
                ->color('success')
                ->visible($canShare)
                ->modalHeading('Share Document')
                ->modalDescription('Grant access to users in your tenant')
                ->form([
                    Placeholder::make('current_shares')
                        ->label('Currently Shared With')
                        ->content(function () use ($documentService) {
                            $shares = $documentService->getDocumentAccessList($this->record);
                            if ($shares->isEmpty()) {
                                return 'Not shared with anyone yet.';
                            }
                            return $shares->pluck('user.name')->implode(', ');
                        })
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
                        ->minItems(1)
                        ->defaultItems(1)
                        ->addActionLabel('Add Another User')
                        ->reorderable(false),
                ])
                ->action(function (array $data) use ($documentService) {
                    $sharingUser = auth()->user();

                    foreach ($data['shares'] as $share) {
                        $targetUser = \App\Models\User::find($share['user_id']);
                        $accessLevel = AccessLevel::from($share['access_level']);

                        $documentService->shareDocument(
                            $this->record,
                            $targetUser,
                            $sharingUser,
                            $accessLevel
                        );
                    }

                    Notification::make()
                        ->success()
                        ->title('Document Shared')
                        ->body(count($data['shares']) . ' user(s) now have access to this document.')
                        ->send();

                    $this->redirect(static::getUrl(['record' => $this->record]));
                }),

            Actions\Action::make('manage_access')
                ->label('Manage Access')
                ->icon('heroicon-o-users')
                ->color('gray')
                ->visible(fn () => $canShare && $this->record->sharedAccess->isNotEmpty())
                ->modalHeading('Manage Document Access')
                ->modalDescription('View and manage who can access this document')
                ->modalContent(fn () => view('filament.modals.document-access-table', [
                    'record' => $this->record,
                    'documentService' => $documentService,
                ]))
                ->modalWidth('3xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            Actions\DeleteAction::make()
                ->visible(fn () => $documentService->canDelete($user, $this->record)),
        ];
    }
}
