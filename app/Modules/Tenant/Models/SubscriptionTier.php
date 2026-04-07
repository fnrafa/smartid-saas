<?php

namespace App\Modules\Tenant\Models;

use Database\Factories\SubscriptionTierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
