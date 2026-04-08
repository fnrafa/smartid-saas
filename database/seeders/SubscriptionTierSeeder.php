<?php

namespace Database\Seeders;

use App\Modules\Tenant\Models\SubscriptionTier;
use Illuminate\Database\Seeder;

class SubscriptionTierSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'basic',
                'max_documents' => 5,
                'has_ai_generate' => false,
            ],
            [
                'name' => 'premium',
                'max_documents' => null,
                'has_ai_generate' => true,
            ],
        ];

        foreach ($tiers as $tier) {
            SubscriptionTier::updateOrCreate(
                ['name' => $tier['name']],
                $tier
            );
        }
    }
}
