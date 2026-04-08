<?php

namespace App\Modules\Document\Models;

use App\Models\User;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Traits\HasTenantScope;
use Database\Factories\DocumentVersionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * @property int $id
 * @property int $document_id
 * @property int $tenant_id
 * @property int $user_id
 * @property int $version_number
 * @property string $title
 * @property string $content
 * @property string|null $category
 * @property Carbon $created_at
 * 
 * @property-read Document $document
 * @property-read Tenant $tenant
 * @property-read User $user
 * 
 * @method static DocumentVersion|null find(int $id)
 * @method static DocumentVersion findOrFail(int $id)
 * @method static DocumentVersion create(array $attributes)
 * @method static Collection<DocumentVersion> all()
 * @method static Builder|DocumentVersion where(string $column, mixed $operator = null, mixed $value = null)
 * @method static Builder|DocumentVersion whereIn(string $column, array $values)
 * @method static Builder|DocumentVersion orderBy(string $column, string $direction = 'asc')
 * @method static DocumentVersion|null first()
 * @method static DocumentVersion firstOrFail()
 * @method static DocumentVersion firstOrCreate(array $attributes)
 * @method static DocumentVersion updateOrCreate(array $attributes, array $values = [])
 */
class DocumentVersion extends Model
{
    use HasFactory, HasTenantScope;

    protected $table = 'document_versions';

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'tenant_id',
        'user_id',
        'version_number',
        'title',
        'content',
        'category',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function newFactory(): DocumentVersionFactory
    {
        return DocumentVersionFactory::new();
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
