<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorePaymentSetting extends Model
{
    public const MODES = [
        'platform',
        'independent',
        'not_configured',
    ];

    protected $fillable = [
        'store_id',
        'payment_mode',
        'provider',
        'status',
        'checkout_enabled',
        'preference_changed_at',
    ];

    protected $casts = [
        'checkout_enabled' => 'boolean',
        'preference_changed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}