<?php

namespace App\Modules\Tenant\Models;

use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $subscription_tier_id
 * @property bool $is_active
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read Tenant $tenant
 * @property-read SubscriptionTier $tier
 * 
 * @method static Subscription|null find(int $id)
 * @method static Subscription findOrFail(int $id)
 * @method static Subscription create(array $attributes)
 * @method static Collection<Subscription> all()
 * @method static Builder|Subscription where(string $column, mixed $operator = null, mixed $value = null)
 * @method static Builder|Subscription whereIn(string $column, array $values)
 * @method static Subscription|null first()
 * @method static Subscription firstOrFail()
 * @method static Subscription firstOrCreate(array $attributes)
 * @method static Subscription updateOrCreate(array $attributes, array $values = [])
 */
class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'subscription_tier_id',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function newFactory(): SubscriptionFactory
    {
        return SubscriptionFactory::new();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class, 'subscription_tier_id');
    }

    public function isActive(): bool
    {
        return $this->is_active &&
               ($this->end_date === null || $this->end_date->isFuture());
    }
}
