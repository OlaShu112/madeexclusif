<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionOffer extends Model
{
    protected $fillable = [
        'subscription_plan_id',
        'name',
        'slug',
        'description',

        'discount_type',
        'monthly_discount_price_minor',
        'annual_discount_price_minor',
        'discount_amount_minor',
        'discount_percentage',

        'billing_interval',
        'duration_type',
        'duration_periods',

        'eligibility',
        'eligible_store_ids',

        'starts_at',
        'ends_at',

        'redemption_limit',
        'redemptions_count',

        'requires_coupon',
        'is_active',
        'show_on_website',

        'badge_text',
        'banner_text',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'monthly_discount_price_minor' => 'integer',
        'annual_discount_price_minor' => 'integer',
        'discount_amount_minor' => 'integer',
        'discount_percentage' => 'decimal:2',

        'duration_periods' => 'integer',
        'eligible_store_ids' => 'array',

        'starts_at' => 'datetime',
        'ends_at' => 'datetime',

        'redemption_limit' => 'integer',
        'redemptions_count' => 'integer',

        'requires_coupon' => 'boolean',
        'is_active' => 'boolean',
        'show_on_website' => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionPlan::class,
            'subscription_plan_id'
        );
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(SubscriptionCoupon::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(
            SubscriptionOfferRedemption::class
        );
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(
            SubscriptionPriceHistory::class
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function scopeAvailable($query)
    {
        $now = now();

        return $query
            ->where('is_active', true)

            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })

            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $now);
            })

            ->where(function ($query) {
                $query->whereNull('redemption_limit')
                    ->orWhereColumn(
                        'redemptions_count',
                        '<',
                        'redemption_limit'
                    );
            });
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

    public function supportsInterval(string $interval): bool
    {
        return in_array(
            $interval,
            ['monthly', 'annual'],
            true
        ) && (
            $this->billing_interval === 'both' ||
            $this->billing_interval === $interval
        );
    }
}