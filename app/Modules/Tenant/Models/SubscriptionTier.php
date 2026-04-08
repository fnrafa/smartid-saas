<?php

namespace App\Modules\Tenant\Models;

use Database\Factories\SubscriptionTierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int|null $max_documents
 * @property bool $has_ai_generate
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read Collection<Subscription> $subscriptions
 * 
 * @method static SubscriptionTier|null find(int $id)
 * @method static SubscriptionTier findOrFail(int $id)
 * @method static SubscriptionTier create(array $attributes)
 * @method static Collection<SubscriptionTier> all()
 * @method static Builder|SubscriptionTier where(string $column, mixed $operator = null, mixed $value = null)
 * @method static Builder|SubscriptionTier whereIn(string $column, array $values)
 * @method static Builder|SubscriptionTier orderBy(string $column, string $direction = 'asc')
 * @method static SubscriptionTier|null first()
 * @method static SubscriptionTier firstOrFail()
 * @method static SubscriptionTier firstOrCreate(array $attributes)
 * @method static SubscriptionTier updateOrCreate(array $attributes, array $values = [])
 */
class SubscriptionTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'max_documents',
        'has_ai_generate',
    ];

    protected $casts = [
        'max_documents' => 'integer',
        'has_ai_generate' => 'boolean',
    ];

    protected static function newFactory(): SubscriptionTierFactory
    {
        return SubscriptionTierFactory::new();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isPremium(): bool
    {
        return $this->name === 'premium';
    }

    public function isBasic(): bool
    {
        return $this->name === 'basic';
    }

    public function hasUnlimitedDocuments(): bool
    {
        return $this->max_documents === null;
    }
}
