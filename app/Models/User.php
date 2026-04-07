<?php

namespace App\Models;

use App\Modules\Document\Models\Document;
use App\Modules\Tenant\Enums\UserRole;
use App\Modules\Tenant\Models\Tenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function ownedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'owner_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function hasPremiumAccess(): bool
    {
        return $this->tenant && $this->tenant->isPremium();
    }

    public function canCreateDocument(): bool
    {
        return $this->tenant && $this->tenant->canCreateDocument();
    }

    public function canCreatePrivateDocument(): bool
    {
        return $this->role->canCreatePrivateDocuments();
    }

    public function canApproveDocuments(): bool
    {
        return $this->role->canApproveDocuments();
    }

    public function isHead(): bool
    {
        return $this->role === UserRole::HEAD;
    }

    public function isDirector(): bool
    {
        return $this->role === UserRole::DIRECTOR;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::MANAGER;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::STAFF;
    }

    public function getRemainingDocumentQuota(): ?int
    {
        return $this->tenant ? $this->tenant->getRemainingDocumentQuota() : 0;
    }

    public function canManageUser(User $targetUser): bool
    {
        if ($this->tenant_id !== $targetUser->tenant_id) {
            return false;
        }

        return $this->role->canManageUser($targetUser->role);
    }
}
