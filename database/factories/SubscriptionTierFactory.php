<?php

namespace Database\Factories;

use App\Modules\Tenant\Models\SubscriptionTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionTierFactory extends Factory
{
    protected $model = SubscriptionTier::class;

    public function definition(): array
    {
        return [
            'name' => 'basic',
            'max_documents' => 5,
            'has_ai_generate' => false,
        ];
    }

    public function basic(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'basic',
            'max_documents' => 5,
            'has_ai_generate' => false,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'premium',
            'max_documents' => null,
            'has_ai_generate' => true,
        ]);
    }
}
