<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Models\DocumentVersion;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentVersionFactory extends Factory
{
    protected $model = DocumentVersion::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $document = Document::factory()->forTenant($tenant)->create();
        $user = User::factory()->forTenant($tenant)->create();

        return [
            'document_id' => $document->id,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'version_number' => 1,
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'category' => fake()->randomElement(['Administrasi', 'Kebijakan', 'Laporan', 'Proposal', 'Surat']),
            'created_at' => now(),
        ];
    }

    public function forDocument(Document $document): static
    {
        return $this->state(fn (array $attributes) => [
            'document_id' => $document->id,
            'tenant_id' => $document->tenant_id,
        ]);
    }

    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);
    }

    public function version(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'version_number' => $number,
        ]);
    }

    public function withContent(string $title, string $content, ?string $category = null): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
            'content' => $content,
            'category' => $category ?? fake()->randomElement(['Administrasi', 'Kebijakan', 'Laporan']),
        ]);
    }
}
