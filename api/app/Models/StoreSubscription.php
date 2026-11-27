<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreSubscription extends Model
{
    protected $fillable = [
        'store_id',
        'subscription_plan_id',
        'subscription_offer_id',
        'subscription_coupon_id',

        'stripe_customer_id',
        'stripe_subscription_id',

        'status',
        'billing_interval',

        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'cancel_at_period_end',

        'standard_price_minor',
        'discounted_price_minor',
        'renewal_price_minor',
        'pricing_currency',

        'discount_duration_type',
        'discount_duration_periods',
        'discount_periods_used',
        'discount_starts_at',
        'discount_ends_at',

        'pricing_snapshot',
        'pricing_agreed_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',

        'standard_price_minor' => 'integer',
        'discounted_price_minor' => 'integer',
        'renewal_price_minor' => 'integer',

        'discount_duration_periods' => 'integer',
        'discount_periods_used' => 'integer',

        'discount_starts_at' => 'datetime',
        'discount_ends_at' => 'datetime',

        'pricing_snapshot' => 'array',
        'pricing_agreed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionPlan::class,
            'subscription_plan_id'
        );
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionOffer::class,
            'subscription_offer_id'
        );
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionCoupon::class,
            'subscription_coupon_id'
        );
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(
            SubscriptionOfferRedemption::class
        );
    }

    public function isStripeManaged(): bool
    {
        return !empty($this->stripe_subscription_id);
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    public function hasRemainingDiscount(): bool
    {
        if ($this->discounted_price_minor === null) {
            return false;
        }

        if (
            $this->discount_ends_at &&
            $this->discount_ends_at->isPast()
        ) {
            return false;
        }

        return match ($this->discount_duration_type) {
            'lifetime' => true,

            'first_period' =>
                $this->discount_periods_used < 1,

            'limited_periods' =>
                $this->discount_periods_used <
                $this->discount_duration_periods,

            default => false,
        };
    }

    public function nextBillingPriceMinor(): ?int
    {
        if ($this->hasRemainingDiscount()) {
            return $this->discounted_price_minor;
        }

        return $this->renewal_price_minor
            ?? $this->standard_price_minor;
    }
}