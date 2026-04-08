<?php

namespace App\Modules\Document\Models;

use App\Models\User;
use App\Modules\Document\Enums\AccessLevel;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Traits\HasTenantScope;
use Database\Factories\DocumentAccessFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $document_id
 * @property int $tenant_id
 * @property int $user_id
 * @property int|null $granted_by
 * @property AccessLevel $access_level
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read Document $document
 * @property-read Tenant $tenant
 * @property-read User $user
 * @property-read User|null $grantedBy
 * 
 * @method static DocumentAccess|null find(int $id)
 * @method static DocumentAccess findOrFail(int $id)
 * @method static DocumentAccess create(array $attributes)
 * @method static Collection<DocumentAccess> all()
 * @method static Builder|DocumentAccess where(string $column, mixed $operator = null, mixed $value = null)
 * @method static Builder|DocumentAccess whereIn(string $column, array $values)
 * @method static DocumentAccess|null first()
 * @method static DocumentAccess firstOrFail()
 * @method static DocumentAccess firstOrCreate(array $attributes)
 * @method static DocumentAccess updateOrCreate(array $attributes, array $values = [])
 */
class DocumentAccess extends Model
{
    use HasFactory, HasTenantScope;

    protected $table = 'document_access';

    protected $fillable = [
        'document_id',
        'tenant_id',
        'user_id',
        'access_level',
        'granted_by',
    ];

    protected $casts = [
        'access_level' => AccessLevel::class,
    ];

    protected static function newFactory(): DocumentAccessFactory
    {
        return DocumentAccessFactory::new();
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function canRead(): bool
    {
        return true;
    }

    public function canEdit(): bool
    {
        return in_array($this->access_level, [AccessLevel::EDIT, AccessLevel::FULL]);
    }

    public function canDelete(): bool
    {
        return $this->access_level === AccessLevel::FULL;
    }

    public function canShare(): bool
    {
        return $this->access_level === AccessLevel::FULL;
    }
}
