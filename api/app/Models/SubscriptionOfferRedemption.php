<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionOfferRedemption extends Model
{
    protected $fillable = [
        'subscription_offer_id',
        'subscription_coupon_id',
        'store_id',
        'store_subscription_id',

        'billing_interval',

        'standard_price_minor',
        'discounted_price_minor',
        'discount_amount_minor',
        'currency',

        'status',
        'redeemed_at',
        'expires_at',
    ];

    protected $casts = [
        'standard_price_minor' => 'integer',
        'discounted_price_minor' => 'integer',
        'discount_amount_minor' => 'integer',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

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

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(
            StoreSubscription::class,
            'store_subscription_id'
        );
    }
}