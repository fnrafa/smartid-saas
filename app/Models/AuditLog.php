<?php

namespace App\Models;

use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Traits\HasTenantScope;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $user_id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $event
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 * 
 * @property-read Tenant $tenant
 * @property-read User|null $user
 * @property-read Model $auditable
 * 
 * @method static AuditLog|null find(int $id)
 * @method static AuditLog findOrFail(int $id)
 * @method static AuditLog create(array $attributes)
 * @method static Collection<AuditLog> all()
 * @method static Builder|AuditLog where(string $column, mixed $operator = null, mixed $value = null)
 * @method static Builder|AuditLog whereIn(string $column, array $values)
 * @method static AuditLog|null first()
 * @method static AuditLog firstOrFail()
 * @method static AuditLog firstOrCreate(array $attributes)
 * @method static AuditLog updateOrCreate(array $attributes, array $values = [])
 */
class AuditLog extends Model
{
    use HasFactory, HasTenantScope;

    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function newFactory(): AuditLogFactory
    {
        return AuditLogFactory::new();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
