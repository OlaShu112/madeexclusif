<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

    protected $fillable = [
        'number', 'user_id', 'email', 'status', 'payment_method', 'payment_status',
        'subtotal', 'discount', 'shipping', 'total', 'coupon_code', 'shipping_address', 'tracking_number',
    ];

    protected $casts = ['shipping_address' => 'array'];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
