<?php

namespace App\Modules\Tenant\Services;

use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Models\Subscription;
use App\Modules\Tenant\Models\SubscriptionTier;
use Illuminate\Support\Collection;

class SubscriptionService
{
    public function getActiveSubscription(Tenant $tenant): ?Subscription
    {
        return $tenant->activeSubscription;
    }

    public function getTier(Tenant $tenant): ?SubscriptionTier
    {
        return $tenant->activeSubscription?->tier;
    }

    public function canCreateDocument(Tenant $tenant): bool
    {
        $subscription = $this->getActiveSubscription($tenant);

        if (!$subscription) {
            return false;
        }

        $tier = $subscription->tier;

        if ($tier->max_documents === null) {
            return true;
        }

        $currentDocumentCount = $tenant->documents()->count();

        return $currentDocumentCount < $tier->max_documents;
    }

    public function getRemainingQuota(Tenant $tenant): ?int
    {
        $subscription = $this->getActiveSubscription($tenant);

        if (!$subscription) {
            return 0;
        }

        $tier = $subscription->tier;

        if ($tier->max_documents === null) {
            return null;
        }

        $currentDocumentCount = $tenant->documents()->count();
        $remaining = $tier->max_documents - $currentDocumentCount;

        return max(0, $remaining);
    }

    public function getQuotaInfo(Tenant $tenant): array
    {
        $subscription = $this->getActiveSubscription($tenant);

        if (!$subscription) {
            return [
                'current' => 0,
                'max' => 0,
                'remaining' => 0,
                'is_unlimited' => false,
                'percentage_used' => 0,
                'tier_name' => 'none',
            ];
        }

        $tier = $subscription->tier;
        $currentCount = $tenant->documents()->count();

        if ($tier->max_documents === null) {
            return [
                'current' => $currentCount,
                'max' => null,
                'remaining' => null,
                'is_unlimited' => true,
                'percentage_used' => 0,
                'tier_name' => $tier->name,
            ];
        }

        $remaining = max(0, $tier->max_documents - $currentCount);
        $percentageUsed = $tier->max_documents > 0
            ? round(($currentCount / $tier->max_documents) * 100, 1)
            : 0;

        return [
            'current' => $currentCount,
            'max' => $tier->max_documents,
            'remaining' => $remaining,
            'is_unlimited' => false,
            'percentage_used' => $percentageUsed,
            'tier_name' => $tier->name,
        ];
    }

    public function hasAIGenerate(Tenant $tenant): bool
    {
        $subscription = $this->getActiveSubscription($tenant);

        if (!$subscription) {
            return false;
        }

        return $subscription->tier->has_ai_generate;
    }

    public function getAllTiers(): Collection
    {
        return SubscriptionTier::orderBy('max_documents')->get();
    }

    public function upgradeToPremium(Tenant $tenant): Subscription
    {
        $premiumTier = SubscriptionTier::where('name', 'premium')->firstOrFail();

        $currentSubscription = $tenant->activeSubscription;
        $currentSubscription?->update([
            'is_active' => false,
            'end_date' => now(),
        ]);

        $newSubscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'subscription_tier_id' => $premiumTier->id,
            'is_active' => true,
            'start_date' => now(),
            'end_date' => null,
        ]);

        return $newSubscription->fresh('tier');
    }

    public function downgradeToBasic(Tenant $tenant): Subscription
    {
        $basicTier = SubscriptionTier::where('name', 'basic')->firstOrFail();

        $currentSubscription = $tenant->activeSubscription;
        $currentSubscription?->update([
            'is_active' => false,
            'end_date' => now(),
        ]);

        $newSubscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'subscription_tier_id' => $basicTier->id,
            'is_active' => true,
            'start_date' => now(),
            'end_date' => null,
        ]);

        return $newSubscription->fresh('tier');
    }

    public function canDowngradeToBasic(Tenant $tenant): bool
    {
        $basicTier = SubscriptionTier::where('name', 'basic')->first();

        if (!$basicTier || $basicTier->max_documents === null) {
            return true;
        }

        $currentDocumentCount = $tenant->documents()->count();

        return $currentDocumentCount <= $basicTier->max_documents;
    }

    public function getTierComparison(): array
    {
        $tiers = $this->getAllTiers();

        return $tiers->map(function (SubscriptionTier $tier) {
            return [
                'name' => $tier->name,
                'max_documents' => $tier->max_documents ?? 'Unlimited',
                'has_ai_generate' => $tier->has_ai_generate,
                'is_premium' => $tier->max_documents === null,
            ];
        })->toArray();
    }

    public function getQuotaPercentage(Tenant $tenant): float
    {
        $info = $this->getQuotaInfo($tenant);

        return $info['is_unlimited'] ? 0 : $info['percentage_used'];
    }

    public function isQuotaLow(Tenant $tenant): bool
    {
        $percentage = $this->getQuotaPercentage($tenant);

        return $percentage >= 80;
    }

    public function isQuotaFull(Tenant $tenant): bool
    {
        return !$this->canCreateDocument($tenant);
    }
}
