<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price_minor',
        'annual_price_minor',
        'currency',
        'stripe_monthly_price_id',
        'stripe_annual_price_id',
        'product_limit',
        'storage_limit_bytes',
        'ai_monthly_limit',
        'features',
        'is_active',
    ];

    protected $casts = [
        'monthly_price_minor' => 'integer',
        'annual_price_minor' => 'integer',
        'product_limit' => 'integer',
        'storage_limit_bytes' => 'integer',
        'ai_monthly_limit' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StoreSubscription::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(SubscriptionOffer::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(SubscriptionPriceHistory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function priceFor(string $interval): int
    {
        return match ($interval) {
            'monthly' => $this->monthly_price_minor,
            'annual' => $this->annual_price_minor,
            default => throw new \InvalidArgumentException(
                'Invalid billing interval.'
            ),
        };
    }
}