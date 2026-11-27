<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformInvoice extends Model
{
    public const STATUSES = [
        'draft',
        'open',
        'partially_paid',
        'paid',
        'overdue',
        'void',
        'uncollectible',
    ];

    public const TYPES = [
        'subscription',
        'service',
        'usage',
        'commission',
        'adjustment',
        'other',
    ];

    protected $fillable = [
        'store_id',
        'store_subscription_id',
        'invoice_number',
        'stripe_invoice_id',
        'type',
        'status',
        'currency',
        'subtotal_minor',
        'discount_minor',
        'tax_minor',
        'total_minor',
        'amount_paid_minor',
        'amount_due_minor',
        'livemode',
        'billing_details',
        'notes',
        'issued_at',
        'due_at',
        'paid_at',
        'voided_at',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'subtotal_minor' => 'integer',
        'discount_minor' => 'integer',
        'tax_minor' => 'integer',
        'total_minor' => 'integer',
        'amount_paid_minor' => 'integer',
        'amount_due_minor' => 'integer',
        'livemode' => 'boolean',
        'billing_details' => 'array',
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
    ];

    protected $hidden = [
        'stripe_invoice_id',
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

    public function items(): HasMany
    {
        return $this->hasMany(PlatformInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(PlatformCreditNote::class);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [
                'open',
                'partially_paid',
                'overdue',
            ])
            ->where('amount_due_minor', '>', 0);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->outstanding()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('livemode', true);
    }

    public function scopeTestMode(Builder $query): Builder
    {
        return $query->where('livemode', false);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, [
            'open',
            'partially_paid',
            'overdue',
        ], true)
            && $this->amount_due_minor > 0
            && $this->due_at !== null
            && $this->due_at->isPast();
    }
}