<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformCreditNote extends Model
{
    public const STATUSES = [
        'draft',
        'issued',
        'void',
    ];

    protected $fillable = [
        'platform_invoice_id',
        'created_by',
        'credit_note_number',
        'provider_credit_note_id',
        'amount_minor',
        'tax_minor',
        'currency',
        'status',
        'reason',
        'issued_at',
        'voided_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'tax_minor' => 'integer',
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    protected $hidden = [
        'provider_credit_note_id',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            PlatformInvoice::class,
            'platform_invoice_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('status', 'issued');
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }
}