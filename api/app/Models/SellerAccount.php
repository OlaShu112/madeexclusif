<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerAccount extends Model
{
    protected $fillable = [
        'store_id',
        'stripe_account_id',
        'currency',
        'status',
        'charges_enabled',
        'payouts_enabled',
        'details_submitted',
        'onboarded_at',
    ];

    protected $casts = [
        'charges_enabled' => 'boolean',
        'payouts_enabled' => 'boolean',
        'details_submitted' => 'boolean',
        'onboarded_at' => 'datetime',
    ];

    protected $hidden = [
        'stripe_account_id',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SellerTransaction::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(SellerPayout::class);
    }

    public function isPayoutEnabled(): bool
    {
        return $this->status === 'active'
            && $this->payouts_enabled
            && $this->details_submitted;
    }
}
