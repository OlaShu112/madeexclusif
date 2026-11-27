<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionCoupon extends Model
{
    protected $fillable = [
        'subscription_offer_id',
        'code',
        'name',
        'description',
        'starts_at',
        'ends_at',
        'redemption_limit',
        'redemptions_count',
        'max_uses_per_store',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'redemption_limit' => 'integer',
        'redemptions_count' => 'integer',
        'max_uses_per_store' => 'integer',
        'is_active' => 'boolean',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionOffer::class,
            'subscription_offer_id'
        );
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(
            SubscriptionOfferRedemption::class
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(
            trim($value)
        );
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (
            $this->starts_at &&
            $this->starts_at->isFuture()
        ) {
            return false;
        }

        if (
            $this->ends_at &&
            $this->ends_at->lte(now())
        ) {
            return false;
        }

        if (
            $this->redemption_limit !== null &&
            $this->redemptions_count >=
            $this->redemption_limit
        ) {
            return false;
        }

        return true;
    }
}