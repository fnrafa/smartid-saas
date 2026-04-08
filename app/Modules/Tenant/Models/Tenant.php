<?php

namespace App\Modules\Tenant\Models;

use App\Models\User;
use App\Modules\Document\Models\Document;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_system
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read Subscription|null $activeSubscription
 * @property-read Collection<Subscription> $subscriptions
 * @property-read Collection<User> $users
 * @property-read Collection<Document> $documents
 * 
 * @method static Tenant|null find(int $id)
 * @method static Tenant findOrFail(int $id)
 * @method static Tenant create(array $attributes)
 * @method static Collection<Tenant> all()
 * @method static Builder|Tenant where(string $column, mixed $operator = null, mixed $value = null)
 * @method static Builder|Tenant whereIn(string $column, array $values)
 * @method static Tenant|null first()
 * @method static Tenant firstOrFail()
 * @method static Tenant firstOrCreate(array $attributes)
 * @method static Tenant updateOrCreate(array $attributes, array $values = [])
 * @method static Builder|Tenant systemTenant()
 * @method static Builder|Tenant clientTenants()
 */
class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('is_active', true)->latest();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function getCurrentTier(): ?SubscriptionTier
    {
        return $this->activeSubscription?->tier;
    }

    public function isPremium(): bool
    {
        $tier = $this->getCurrentTier();
        return $tier && $tier->name === 'premium';
    }

    public function isBasic(): bool
    {
        $tier = $this->getCurrentTier();
        return $tier && $tier->name === 'basic';
    }

    public function getDocumentLimit(): ?int
    {
        $tier = $this->getCurrentTier();
        return $tier?->max_documents;
    }

    public function getRemainingDocumentQuota(): ?int
    {
        if ($this->isPremium()) {
            return null;
        }

        $limit = $this->getDocumentLimit();
        if ($limit === null) {
            return null;
        }

        $used = $this->documents()->count();
        return max(0, $limit - $used);
    }

    public function canCreateDocument(): bool
    {
        if ($this->isPremium()) {
            return true;
        }

        $used = $this->documents()->count();
        $limit = $this->getDocumentLimit();

        if ($limit === null) {
            return true;
        }

        return $used < $limit;
    }

    public function hasAIGenerateAccess(): bool
    {
        $tier = $this->getCurrentTier();
        return $tier && $tier->has_ai_generate;
    }

    public function isSystemTenant(): bool
    {
        return $this->is_system === true;
    }

    public function scopeSystemTenant(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeClientTenants(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }
}
