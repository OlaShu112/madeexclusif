<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    public const STATUSES = [
        'requested',
        'submitting',
        'pending',
        'requires_action',
        'succeeded',
        'failed',
        'cancelled',
        'reconciliation_required',
    ];

    protected $fillable = [
        'order_id',
        'store_id',
        'requested_by',
        'reference',
        'idempotency_key',
        'stripe_refund_id',
        'stripe_payment_intent',
        'livemode',
        'amount_minor',
        'currency',
        'type',
        'status',
        'reason',
        'admin_notes',
        'failure_reason',
        'error_message',
        'requested_at',
        'submitted_at',
        'succeeded_at',
        'failed_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'store_id' => 'integer',
        'requested_by' => 'integer',
        'livemode' => 'boolean',
        'amount_minor' => 'integer',
        'requested_at' => 'datetime',
        'submitted_at' => 'datetime',
        'succeeded_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    protected $hidden = [
        'idempotency_key',
        'stripe_payment_intent',
        'error_message',
        'admin_notes',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by'
        );
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'succeeded';
    }

    public function isPending(): bool
    {
        return in_array(
            $this->status,
            [
                'requested',
                'submitting',
                'pending',
                'requires_action',
                'reconciliation_required',
            ],
            true
        );
    }
}
