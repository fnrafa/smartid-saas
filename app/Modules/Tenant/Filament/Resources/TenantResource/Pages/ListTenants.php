<?php

namespace App\Modules\Tenant\Filament\Resources\TenantResource\Pages;

use App\Modules\Tenant\Filament\Resources\TenantResource\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
