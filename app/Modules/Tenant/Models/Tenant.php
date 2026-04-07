<?php

namespace App\Modules\Tenant\Models;

use App\Models\User;
use App\Modules\Document\Models\Document;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

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
}
