<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    public function created(User $user): void
    {
        $this->logActivity($user, 'created', null, $user->getAttributes());
    }

    public function updated(User $user): void
    {
        $this->logActivity($user, 'updated', $user->getOriginal(), $user->getChanges());
    }

    public function deleted(User $user): void
    {
        $this->logActivity($user, 'deleted', $user->getAttributes(), null);
    }

    private function logActivity(User $user, string $event, ?array $oldValues, ?array $newValues): void
    {
        $authenticatedUser = Auth::user();

        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $authenticatedUser?->id,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'event' => $event,
            'old_values' => $oldValues ? json_encode($this->filterSensitiveData($oldValues)) : null,
            'new_values' => $newValues ? json_encode($this->filterSensitiveData($newValues)) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    private function filterSensitiveData(array $data): array
    {
        $sensitiveFields = ['password', 'remember_token'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}
