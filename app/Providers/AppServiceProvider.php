<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Observers\TenantObserver;
use App\Modules\Tenant\Policies\TenantPolicy;
use App\Observers\UserObserver;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Tenant::class, TenantPolicy::class);
        
        User::observe(UserObserver::class);
        Tenant::observe(TenantObserver::class);
    }
}
