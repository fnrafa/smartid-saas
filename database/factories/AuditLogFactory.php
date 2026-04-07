<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Document\Models\Document;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $events = ['created', 'updated', 'deleted', 'restored'];
        
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'auditable_type' => Document::class,
            'auditable_id' => fake()->numberBetween(1, 100),
            'event' => fake()->randomElement($events),
            'old_values' => null,
            'new_values' => [
                'title' => fake()->sentence(),
                'content' => fake()->paragraph(),
            ],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'created_at' => now(),
        ];
    }

    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'created',
            'old_values' => null,
            'new_values' => [
                'title' => fake()->sentence(),
                'content' => fake()->paragraph(),
            ],
        ]);
    }

    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'updated',
            'old_values' => [
                'title' => fake()->sentence(),
                'content' => fake()->paragraph(),
            ],
            'new_values' => [
                'title' => fake()->sentence(),
                'content' => fake()->paragraph(),
            ],
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'deleted',
            'old_values' => [
                'title' => fake()->sentence(),
                'content' => fake()->paragraph(),
            ],
            'new_values' => null,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
        ]);
    }

    public function forDocument(Document $document): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $document->tenant_id,
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
        ]);
    }
}
