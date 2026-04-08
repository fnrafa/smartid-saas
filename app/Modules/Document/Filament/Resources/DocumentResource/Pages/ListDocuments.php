<?php

namespace App\Modules\Document\Filament\Resources\DocumentResource\Pages;

use App\Modules\Document\Filament\Resources\DocumentResource\DocumentResource;
use Filament\Resources\Pages\ListRecords;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
