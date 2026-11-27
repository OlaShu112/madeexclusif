<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    public const STATUSES = [
        'pending',
        'processing',
        'succeeded',
        'failed',
        'cancelled',
        'partially_refunded',
        'refunded',
        'disputed',
    ];

    protected $fillable = [
        'store_id',
        'store_subscription_id',
        'platform_invoice_id',
        'reference',
        'idempotency_key',
        'provider',
        'provider_payment_id',
        'stripe_payment_intent_id',
        'amount_minor',
        'fee_minor',
        'refunded_minor',
        'currency',
        'status',
        'payment_method',
        'livemode',
        'failure_reason',
        'paid_at',
        'failed_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'fee_minor' => 'integer',
        'refunded_minor' => 'integer',
        'livemode' => 'boolean',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected $hidden = [
        'idempotency_key',
        'provider_payment_id',
        'stripe_payment_intent_id',
    ];

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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            PlatformInvoice::class,
            'platform_invoice_id'
        );
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'succeeded');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('livemode', true);
    }

    public function scopeTestMode(Builder $query): Builder
    {
        return $query->where('livemode', false);
    }

    public function scopeForCurrency(
        Builder $query,
        string $currency
    ): Builder {
        return $query->where('currency', strtolower($currency));
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'succeeded';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function refundableAmountMinor(): int
    {
        if (!in_array($this->status, [
            'succeeded',
            'partially_refunded',
        ], true)) {
            return 0;
        }

        return max(
            0,
            $this->amount_minor - $this->refunded_minor
        );
    }

    public function netAmountMinor(): int
    {
        return $this->amount_minor
            - $this->fee_minor
            - $this->refunded_minor;
    }
}