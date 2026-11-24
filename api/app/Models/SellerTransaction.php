<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerTransaction extends Model
{
    public const TYPES = [
        'sale',
        'commission',
        'refund',
        'commission_reversal',
        'payout',
        'payout_reversal',
        'adjustment',
    ];

    protected $fillable = [
        'seller_account_id',
        'order_id',
        'refund_id',
        'seller_payout_id',
        'reference',
        'type',
        'direction',
        'amount_minor',
        'currency',
        'status',
        'description',
        'available_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'available_at' => 'datetime',
    ];

    public function sellerAccount(): BelongsTo
    {
        return $this->belongsTo(SellerAccount::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(
            SellerPayout::class,
            'seller_payout_id'
        );
    }
}
