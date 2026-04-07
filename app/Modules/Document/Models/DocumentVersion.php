<?php

namespace App\Modules\Document\Models;

use App\Models\User;
use App\Modules\Tenant\Models\Tenant;
use Database\Factories\DocumentVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class DocumentVersion extends Model
{
    use HasFactory;

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

    public function getContentAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setContentAttribute($value): void
    {
        $this->attributes['content'] = $value ? Crypt::encryptString($value) : null;
    }
}
