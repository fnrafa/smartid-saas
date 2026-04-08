<?php

namespace App\Modules\Document\Filament\Resources\DocumentResource\Pages;

use App\Modules\Document\Filament\Resources\DocumentResource\DocumentResource;
use App\Modules\Tenant\Services\SubscriptionService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->before(function () {
                    $user = auth()->user();
                    $subscriptionService = app(SubscriptionService::class);
                    
                    if (!$subscriptionService->canCreateDocument($user->tenant)) {
                        $quotaInfo = $subscriptionService->getQuotaInfo($user->tenant);
                        
                        Notification::make()
                            ->danger()
                            ->title('Quota Exceeded')
                            ->body(sprintf(
                                'Your %s plan allows maximum %d documents. You have reached this limit. Please upgrade to Premium for unlimited documents.',
                                ucfirst($quotaInfo['tier_name']),
                                $quotaInfo['max']
                            ))
                            ->persistent()
                            ->send();
                            
                        $this->halt();
                    }
                }),
        ];
    }
}
