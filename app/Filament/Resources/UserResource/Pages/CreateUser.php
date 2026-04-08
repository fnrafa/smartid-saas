<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if ($user && $user->isHead() && !$user->isSuperAdmin()) {
            $data['tenant_id'] = $user->tenant_id;
        }

        return $data;
    }
}
