<?php

namespace Database\Factories;

use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Pemerintah Kota ' . fake()->city(),
            'Pemerintah Kabupaten ' . fake()->city(),
            'Pemerintah Provinsi ' . fake()->state(),
            'Dinas ' . fake()->randomElement(['Pendidikan', 'Kesehatan', 'Perhubungan', 'PUPR']) . ' Kota ' . fake()->city(),
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
