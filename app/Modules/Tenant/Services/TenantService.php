<?php

namespace App\Modules\Tenant\Services;

use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Models\Subscription;
use App\Modules\Tenant\Models\SubscriptionTier;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

class TenantService
{
    public function createTenant(
        string $name,
        string $tierName = 'basic'
    ): Tenant
    {
        $slug = $this->generateUniqueSlug($name);

        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
        ]);

        $tier = SubscriptionTier::where('name', $tierName)->firstOrFail();

        Subscription::create([
            'tenant_id' => $tenant->id,
            'subscription_tier_id' => $tier->id,
            'is_active' => true,
            'start_date' => now(),
            'end_date' => null,
        ]);

        return $tenant->fresh('activeSubscription.tier');
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function updateTenant(Tenant $tenant, array $data): Tenant
    {
        if (isset($data['name'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $tenant->update($data);

        return $tenant->fresh();
    }

    public function getAllTenantsWithSubscription(): Collection
    {
        return Tenant::with(['activeSubscription.tier', 'users'])
            ->withCount('users')
            ->get();
    }

    public function getTenantById(int $tenantId): ?Tenant
    {
        return Tenant::with([
            'activeSubscription.tier',
            'users',
        ])
            ->withCount(['users', 'documents'])
            ->find($tenantId);
    }

    public function changeTier(Tenant $tenant, string $newTierName): Subscription
    {
        $newTier = SubscriptionTier::where('name', $newTierName)->firstOrFail();

        $currentSubscription = $tenant->activeSubscription;
        $currentSubscription?->update([
            'is_active' => false,
            'end_date' => now(),
        ]);

        $newSubscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'subscription_tier_id' => $newTier->id,
            'is_active' => true,
            'start_date' => now(),
            'end_date' => null,
        ]);

        return $newSubscription->fresh('tier');
    }

    public function deleteTenant(Tenant $tenant): bool
    {
        $tenant->subscriptions()->update([
            'is_active' => false,
            'end_date' => now(),
        ]);

        return $tenant->delete();
    }

    public function hasActiveSubscription(Tenant $tenant): bool
    {
        return $tenant->activeSubscription()->exists();
    }

    public function getTenantStats(Tenant $tenant): array
    {
        return [
            'total_users' => $tenant->users()->count(),
            'total_documents' => $tenant->documents()->count(),
            'subscription_tier' => $tenant->activeSubscription?->tier?->name ?? 'none',
            'documents_quota' => $tenant->activeSubscription?->tier?->max_documents ?? 0,
            'has_ai_generate' => $tenant->activeSubscription?->tier?->has_ai_generate ?? false,
        ];
    }
}
