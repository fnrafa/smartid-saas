<?php

namespace App\Modules\Tenant\Observers;

use App\Models\AuditLog;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class TenantObserver
{
    public function created(Tenant $tenant): void
    {
        $this->logActivity($tenant, 'created', null, $tenant->getAttributes());
    }

    public function updated(Tenant $tenant): void
    {
        $this->logActivity($tenant, 'updated', $tenant->getOriginal(), $tenant->getChanges());
    }

    public function deleted(Tenant $tenant): void
    {
        $this->logActivity($tenant, 'deleted', $tenant->getAttributes(), null);
    }

    private function logActivity(Tenant $tenant, string $event, ?array $oldValues, ?array $newValues): void
    {
        $user = Auth::user();

        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user?->id,
            'auditable_type' => Tenant::class,
            'auditable_id' => $tenant->id,
            'event' => $event,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
