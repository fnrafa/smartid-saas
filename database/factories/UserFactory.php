<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Tenant\Enums\UserRole;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'tenant_id' => Tenant::factory(),
            'role' => fake()->randomElement(['staff', 'manager', 'director', 'head']),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::STAFF,
        ]);
    }

    public function manager(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::MANAGER,
        ]);
    }

    public function director(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::DIRECTOR,
        ]);
    }

    public function head(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::HEAD,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn(array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
