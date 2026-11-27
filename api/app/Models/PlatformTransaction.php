<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformTransaction extends Model
{
    public const TYPES = [
        'subscription_payment',
        'subscription_refund',
        'commission',
        'commission_reversal',
        'service_payment',
        'usage_payment',
        'processing_fee',
        'expense',
        'expense_reversal',
        'adjustment',
        'other_income',
    ];

    public const STATUSES = [
        'pending',
        'processing',
        'completed',
        'failed',
        'cancelled',
    ];

    public const DIRECTIONS = [
        'credit',
        'debit',
    ];

    protected $fillable = [
        'store_id',
        'created_by',
        'reference',
        'idempotency_key',
        'type',
        'direction',
        'amount_minor',
        'currency',
        'status',
        'source_type',
        'source_id',
        'provider',
        'provider_reference',
        'livemode',
        'description',
        'metadata',
        'occurred_at',
        'reconciled_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'livemode' => 'boolean',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'reconciled_at' => 'datetime',
    ];

    protected $hidden = [
        'idempotency_key',
        'provider_reference',
        'metadata',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('direction', 'credit');
    }

    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('direction', 'debit');
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('livemode', true);
    }

    public function scopeTestMode(Builder $query): Builder
    {
        return $query->where('livemode', false);
    }

    public function scopeReconciled(Builder $query): Builder
    {
        return $query->whereNotNull('reconciled_at');
    }

    public function scopeUnreconciled(Builder $query): Builder
    {
        return $query->whereNull('reconciled_at');
    }

    public function scopeForCurrency(
        Builder $query,
        string $currency
    ): Builder {
        return $query->where('currency', strtolower($currency));
    }

    public function isCredit(): bool
    {
        return $this->direction === 'credit';
    }

    public function isDebit(): bool
    {
        return $this->direction === 'debit';
    }

    public function isReconciled(): bool
    {
        return $this->reconciled_at !== null;
    }

    public function signedAmountMinor(): int
    {
        return match ($this->direction) {
            'credit' => $this->amount_minor,
            'debit' => -$this->amount_minor,
            default => throw new \LogicException(
                'Invalid transaction direction.'
            ),
        };
    }
}