<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformInvoiceItem extends Model
{
    public const TYPES = [
        'subscription',
        'service',
        'usage',
        'commission',
        'adjustment',
        'other',
    ];

    protected $fillable = [
        'platform_invoice_id',
        'platform_tax_rate_id',
        'type',
        'description',
        'quantity',
        'unit_price_minor',
        'subtotal_minor',
        'discount_minor',
        'tax_percentage',
        'tax_minor',
        'total_minor',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_minor' => 'integer',
        'subtotal_minor' => 'integer',
        'discount_minor' => 'integer',
        'tax_percentage' => 'decimal:2',
        'tax_minor' => 'integer',
        'total_minor' => 'integer',
        'metadata' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            PlatformInvoice::class,
            'platform_invoice_id'
        );
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(
            PlatformTaxRate::class,
            'platform_tax_rate_id'
        );
    }
}