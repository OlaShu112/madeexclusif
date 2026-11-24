<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerPayout extends Model
{
    public const STATUSES = [
        'requested',
        'approved',
        'processing',
        'transferred',
        'paid',
        'failed',
        'rejected',
        'cancelled',
        'reconciliation_required',
    ];

    protected $fillable = [
        'seller_account_id',
        'requested_by',
        'approved_by',
        'reference',
        'idempotency_key',
        'stripe_transfer_id',
        'stripe_payout_id',
        'amount_minor',
        'currency',
        'livemode',
        'status',
        'failure_reason',
        'requested_at',
        'approved_at',
        'processed_at',
        'paid_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'livemode' => 'boolean',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected $hidden = [
        'idempotency_key',
        'stripe_transfer_id',
        'stripe_payout_id',
    ];

    public function sellerAccount(): BelongsTo
    {
        return $this->belongsTo(SellerAccount::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by'
        );
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SellerTransaction::class);
    }
}
