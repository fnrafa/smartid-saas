<?php

namespace App\Models;

use App\Modules\Document\Models\Document;
use App\Modules\Document\Models\DocumentAccess;
use App\Modules\Tenant\Enums\UserRole;
use App\Modules\Tenant\Models\Tenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property UserRole $role
 * @property Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Tenant $tenant
 * @property-read Collection<Document> $documents
 * @property-read Collection<Document> $ownedDocuments
 * @property-read Collection<AuditLog> $auditLogs
 * @property-read Collection<DocumentAccess> $documentAccess
 *
 * @method static User|null find(int $id)
 * @method static User findOrFail(int $id)
 * @method static User create(array $attributes)
 * @method static Collection<User> all()
 * @method static Builder|User where(string $column, mixed $operator = null, mixed $value = null)
 * @method static Builder|User whereIn(string $column, array $values)
 * @method static User first()
 * @method static User firstOrFail()
 * @method static User firstOrCreate(array $attributes)
 * @method static User updateOrCreate(array $attributes, array $values = [])
 * @method static int count()
 */
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

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
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

    public function documentAccess(): HasMany
    {
        return $this->hasMany(DocumentAccess::class);
    }

    public function grantedAccess(): HasMany
    {
        return $this->hasMany(DocumentAccess::class, 'granted_by');
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

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPERADMIN;
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
        if ($this->isSuperAdmin()) {
            return $this->role->canManageUser($targetUser->role);
        }

        if ($this->isHead()) {
            if ($this->tenant_id !== $targetUser->tenant_id) {
                return false;
            }
            return $this->role->canManageUser($targetUser->role);
        }

        return false;
    }

    public function canManageTenants(): bool
    {
        return $this->role->canManageTenants();
    }

    public function canTransferUserBetweenTenants(): bool
    {
        return $this->role->canTransferUserBetweenTenants();
    }

    public function canViewAllAuditLogs(): bool
    {
        return $this->role->canViewAllAuditLogs();
    }

    public function canViewTenantAuditLogs(): bool
    {
        return $this->role->canViewTenantAuditLogs();
    }
}
