<?php

namespace App\Modules\Document\Models;

use App\Models\User;
use App\Modules\Document\Enums\AccessLevel;
use App\Modules\Tenant\Models\Tenant;
use Database\Factories\DocumentAccessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'tenant_id',
        'user_id',
        'access_level',
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

    public function canRead(): bool
    {
        return $this->access_level->canRead();
    }

    public function canEdit(): bool
    {
        return $this->access_level->canEdit();
    }

    public function canDelete(): bool
    {
        return $this->access_level->canDelete();
    }

    public function canShare(): bool
    {
        return $this->access_level->canShare();
    }
}
