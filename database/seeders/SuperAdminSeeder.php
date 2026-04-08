<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Tenant\Enums\UserRole;
use App\Modules\Tenant\Models\Subscription;
use App\Modules\Tenant\Models\SubscriptionTier;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $smartidTenant = Tenant::firstOrCreate([
            'slug' => 'smartid-system',
            'name' => 'SmartID System',
            'is_system' => true,
        ]);

        $premiumTier = SubscriptionTier::where('name', 'premium')->first();

        if ($premiumTier && !$smartidTenant->activeSubscription) {
            Subscription::create([
                'tenant_id' => $smartidTenant->id,
                'subscription_tier_id' => $premiumTier->id,
                'is_active' => true,
                'start_date' => now(),
                'end_date' => null,
            ]);
        }

        $existingSuperAdmin = User::where('email', 'superadmin@smartid.co.id')->first();

        if (!$existingSuperAdmin) {
            User::create([
                'tenant_id' => $smartidTenant->id,
                'name' => 'Super Administrator',
                'email' => 'superadmin@smartid.co.id',
                'password' => Hash::make('superadmin123'),
                'role' => UserRole::SUPERADMIN,
            ]);

        }
    }
}
