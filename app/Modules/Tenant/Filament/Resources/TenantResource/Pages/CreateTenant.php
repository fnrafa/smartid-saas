<?php

namespace App\Modules\Tenant\Filament\Resources\TenantResource\Pages;

use App\Modules\Tenant\Filament\Resources\TenantResource\TenantResource;
use App\Modules\Tenant\Models\Subscription;
use App\Modules\Tenant\Models\SubscriptionTier;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function afterCreate(): void
    {
        $basicTier = SubscriptionTier::where('name', 'basic')->first();

        if ($basicTier) {
            Subscription::create([
                'tenant_id' => $this->record->id,
                'subscription_tier_id' => $basicTier->id,
                'is_active' => true,
                'start_date' => now(),
                'end_date' => null,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
