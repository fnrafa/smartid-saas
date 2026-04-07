<?php

namespace Database\Factories;

use App\Modules\Tenant\Models\Subscription;
use App\Modules\Tenant\Models\SubscriptionTier;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subscription_tier_id' => SubscriptionTier::factory(),
            'is_active' => true,
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
            'start_date' => now()->subYear(),
            'end_date' => now()->subMonth(),
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn(array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function withTier(SubscriptionTier $tier): static
    {
        return $this->state(fn(array $attributes) => [
            'subscription_tier_id' => $tier->id,
        ]);
    }
}
