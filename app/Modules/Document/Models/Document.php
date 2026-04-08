<?php

namespace App\Modules\Document\Models;

use App\Models\User;
use App\Modules\Document\Enums\DocumentVisibility;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Traits\HasTenantScope;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property int $owner_id
 * @property string $title
 * @property string $content
 * @property string|null $category
 * @property DocumentVisibility $visibility
 * @property Carbon|null $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read Tenant $tenant
 * @property-read User $user
 * @property-read User $lastEditor
 * @property-read User $owner
 * @property-read Collection<DocumentVersion> $versions
 * @property-read Collection<DocumentAccess> $accessPermissions
 * @property-read Collection<DocumentAccess> $sharedAccess
 * 
 * @method static Document|null find(int $id)
 * @method static Document findOrFail(int $id)
 * @method static Document create(array $attributes)
 * @method static Collection<Document> all()
 * @method static Builder|Document where(string $column, mixed $operator = null, mixed $value = null)
 * @method static Builder|Document whereIn(string $column, array $values)
 * @method static Document|null first()
 * @method static Document firstOrFail()
 * @method static Document firstOrCreate(array $attributes)
 * @method static Document updateOrCreate(array $attributes, array $values = [])
 * @method static Builder|Document public()
 * @method static Builder|Document private()
 * @method static Builder|Document accessibleBy(User $user)
 * @method static int count()
 * @method static Builder withoutGlobalScopes()
 */
class Document extends Model
{
    use HasFactory, SoftDeletes, HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'owner_id',
        'title',
        'content',
        'category',
        'visibility',
    ];

    protected $casts = [
        'visibility' => DocumentVisibility::class,
    ];

    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (Document $document) {
            if (auth()->check()) {
                if (!$document->user_id) {
                    $document->user_id = auth()->id();
                }
                if (!$document->owner_id) {
                    $document->owner_id = auth()->id();
                }
                if (!$document->visibility) {
                    $document->visibility = DocumentVisibility::PRIVATE;
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function accessPermissions(): HasMany
    {
        return $this->hasMany(DocumentAccess::class);
    }

    public function sharedAccess(): HasMany
    {
        return $this->hasMany(DocumentAccess::class);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', DocumentVisibility::PUBLIC);
    }

    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('visibility', DocumentVisibility::PRIVATE);
    }

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('visibility', DocumentVisibility::PUBLIC)
                ->orWhere('owner_id', $user->id)
                ->orWhereHas('accessPermissions', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
        });
    }

    public function isPublic(): bool
    {
        return $this->visibility === DocumentVisibility::PUBLIC;
    }

    public function isPrivate(): bool
    {
        return $this->visibility === DocumentVisibility::PRIVATE;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function getContentAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return '[Content decryption failed]';
        }
    }

    public function setContentAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['content'] = null;
            return;
        }

        $this->attributes['content'] = Crypt::encryptString($value);
    }
}
