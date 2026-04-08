<?php



namespace App\Modules\Document\Filament\Widgets;

use App\Modules\Document\Models\Document;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Services\SubscriptionService;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentQuotaWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        if ($user->isSuperAdmin()) {
            return $this->getSuperAdminStats();
        }

        $subscriptionService = app(SubscriptionService::class);
        $quotaInfo = $subscriptionService->getQuotaInfo($tenant);

        if ($quotaInfo['is_unlimited']) {
            return [
                Stat::make('Document Quota', 'Unlimited')
                    ->description('Premium Plan - No Limits')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('success'),

                Stat::make('Documents Created', $quotaInfo['current'])
                    ->description('Total documents in your tenant')
                    ->descriptionIcon('heroicon-m-document-text')
                    ->color('primary'),

                Stat::make('Subscription Tier', ucfirst($quotaInfo['tier_name']))
                    ->description('Premium features enabled')
                    ->descriptionIcon('heroicon-m-star')
                    ->color('success'),
            ];
        }

        $percentage = $quotaInfo['percentage_used'];
        $color = match (true) {
            $percentage >= 90 => 'danger',
            $percentage >= 70 => 'warning',
            default => 'success',
        };

        return [
            Stat::make('Document Quota', "{$quotaInfo['current']} / {$quotaInfo['max']}")
                ->description("{$quotaInfo['remaining']} documents remaining ({$percentage}% used)")
                ->descriptionIcon('heroicon-m-document-text')
                ->color($color)
                ->chart($this->getUsageChart($quotaInfo['current'], $quotaInfo['max'])),

            Stat::make('Quota Usage', "{$percentage}%")
                ->description($this->getQuotaMessage($percentage))
                ->descriptionIcon($this->getQuotaIcon($percentage))
                ->color($color),

            Stat::make('Subscription Tier', ucfirst($quotaInfo['tier_name']))
                ->description('Upgrade to Premium for unlimited documents')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning')
                ->url(route('filament.admin.pages.dashboard')),
        ];
    }

    private function getSuperAdminStats(): array
    {
        $totalDocuments = Document::withoutGlobalScopes()->count();
        $totalTenants = Tenant::clientTenants()->count();
        $totalUsers = User::count();

        return [
            Stat::make('Total Documents', number_format($totalDocuments))
                ->description('Across all tenants')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Client Tenants', number_format($totalTenants))
                ->description('Active client organizations')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make('Total Users', number_format($totalUsers))
                ->description('All users in system')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }

    private function getUsageChart(int $current, int $max): array
    {
        $step = max(1, (int) ceil($max / 10));
        $chart = [];

        for ($i = 0; $i <= $max; $i += $step) {
            $chart[] = min($i, $current);
        }

        return $chart;
    }

    private function getQuotaMessage(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'Critical: Quota almost full!',
            $percentage >= 70 => 'Warning: Approaching limit',
            $percentage >= 50 => 'Half quota used',
            default => 'Plenty of quota available',
        };
    }

    private function getQuotaIcon(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'heroicon-m-exclamation-triangle',
            $percentage >= 70 => 'heroicon-m-exclamation-circle',
            default => 'heroicon-m-check-circle',
        };
    }
}
