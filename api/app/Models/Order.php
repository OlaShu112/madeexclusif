<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    public const STATUSES = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

    protected $fillable = [
        'number', 'user_id', 'email', 'status', 'payment_method', 'payment_status',
        'subtotal', 'discount', 'shipping', 'total', 'coupon_code', 'shipping_address', 'tracking_number',
        'stripe_session_id', 'stripe_payment_intent', 'paid_at',
    ];

    protected $casts = ['shipping_address' => 'array', 'paid_at' => 'datetime'];

    protected $hidden = ['stripe_session_id', 'stripe_payment_intent'];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Idempotent: Stripe may deliver the same event more than once. */
    public function markPaid(?string $paymentIntent = null): void
    {
        if ($this->payment_status === 'paid') {
            return;
        }

        $this->update([
            'status' => $this->status === 'pending' ? 'paid' : $this->status,
            'payment_status' => 'paid',
            'stripe_payment_intent' => $paymentIntent,
            'paid_at' => now(),
        ]);
    }

    /** Cancels an unpaid order and puts its reserved stock back on the shelf. */
    public function cancelAndRestock(): void
    {
        DB::transaction(function () {
            $order = static::whereKey($this->id)->lockForUpdate()->first();

            if (! $order || $order->status !== 'pending' || $order->payment_status === 'paid') {
                return;
            }

            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::whereKey($item->product_variant_id)->increment('stock', $item->quantity);
                }
            }

            $order->update(['status' => 'cancelled', 'payment_status' => 'unpaid']);
        });

        $this->refresh();
    }
}