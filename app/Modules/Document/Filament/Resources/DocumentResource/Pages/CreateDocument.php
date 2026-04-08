<?php

namespace App\Modules\Document\Filament\Resources\DocumentResource\Pages;

use App\Modules\Document\Enums\DocumentVisibility;
use App\Modules\Document\Filament\Resources\DocumentResource\DocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['user_id'] = auth()->id();
        $data['owner_id'] = auth()->id();

        if (auth()->user()->isStaff() && (!isset($data['visibility']) || $data['visibility'] === DocumentVisibility::PRIVATE->value)) {
            $data['visibility'] = DocumentVisibility::PUBLIC->value;
        }

        if (!isset($data['visibility'])) {
            $data['visibility'] = auth()->user()->isStaff()
                ? DocumentVisibility::PUBLIC->value
                : DocumentVisibility::PRIVATE->value;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
