<?php

namespace App\Modules\Document\Filament\Resources\DocumentResource\Pages;

use App\Modules\Document\Exceptions\UnauthorizedActionException;
use App\Modules\Document\Filament\Resources\DocumentResource\DocumentResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        if (!Gate::allows('update', $this->record)) {
            Notification::make()
                ->danger()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki izin untuk mengedit dokumen ini.')
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
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
