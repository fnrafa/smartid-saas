<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Document\Enums\AccessLevel;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Models\DocumentAccess;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentAccessFactory extends Factory
{
    protected $model = DocumentAccess::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $document = Document::factory()->forTenant($tenant)->create();
        $user = User::factory()->forTenant($tenant)->create();

        return [
            'document_id' => $document->id,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'access_level' => fake()->randomElement(['read', 'edit', 'full']),
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_level' => AccessLevel::READ,
        ]);
    }

    public function edit(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_level' => AccessLevel::EDIT,
        ]);
    }

    public function full(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_level' => AccessLevel::FULL,
        ]);
    }

    public function forDocument(Document $document): static
    {
        return $this->state(fn (array $attributes) => [
            'document_id' => $document->id,
            'tenant_id' => $document->tenant_id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);
    }

    public function sharedWith(Document $document, User $user, AccessLevel $level): static
    {
        return $this->state(fn (array $attributes) => [
            'document_id' => $document->id,
            'tenant_id' => $document->tenant_id,
            'user_id' => $user->id,
            'access_level' => $level,
        ]);
    }
}
