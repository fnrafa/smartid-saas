<?php

namespace App\Modules\Document\Models;

use App\Models\User;
use App\Modules\Document\Enums\DocumentVisibility;
use App\Modules\Tenant\Models\Tenant;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Document extends Model
{
    use HasFactory, SoftDeletes;

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
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function (Document $document) {
            if (auth()->check()) {
                if (!$document->tenant_id) {
                    $document->tenant_id = auth()->user()->tenant_id;
                }
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

    public function scopeAllTenants(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
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

    public function getContentAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setContentAttribute($value): void
    {
        $this->attributes['content'] = $value ? Crypt::encryptString($value) : null;
    }
}
