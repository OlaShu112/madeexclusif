<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPriceHistory extends Model
{
    protected $fillable = [
        'subscription_plan_id',
        'subscription_offer_id',
        'action',
        'billing_interval',
        'previous_price_minor',
        'new_price_minor',
        'currency',
        'previous_values',
        'new_values',
        'reason',
        'changed_by',
    ];

    protected $casts = [
        'previous_price_minor' => 'integer',
        'new_price_minor' => 'integer',
        'previous_values' => 'array',
        'new_values' => 'array',
    ];

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

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'changed_by'
        );
    }
}