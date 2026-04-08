<?php

namespace App\Modules\Document\Filament\Resources\DocumentResource\Pages;

use App\Modules\Document\Filament\Resources\DocumentResource\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
