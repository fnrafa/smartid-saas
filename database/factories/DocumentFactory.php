<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Document\Enums\DocumentVisibility;
use App\Modules\Document\Models\Document;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();

        return [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'owner_id' => $user->id,
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'category' => fake()->randomElement(['Administrasi', 'Kebijakan', 'Laporan', 'Proposal', 'Surat']),
            'visibility' => fake()->randomElement(['private', 'public']),
        ];
    }

    public function private(): static
    {
        return $this->state(fn(array $attributes) => [
            'visibility' => DocumentVisibility::PRIVATE,
        ]);
    }

    public function public(): static
    {
        return $this->state(fn(array $attributes) => [
            'visibility' => DocumentVisibility::PUBLIC,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn(array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function ownedBy(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'owner_id' => $user->id,
        ]);
    }
}
