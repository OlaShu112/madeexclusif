<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Order extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Order Statuses
    |--------------------------------------------------------------------------
    */

    public const STATUSES = [
        'pending',
        'paid',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
        'refunded',
    ];

    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'number',
        'user_id',
        'store_id',
        'email',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'discount',
        'shipping',
        'total',
        'coupon_code',
        'shipping_address',
        'tracking_number',
        'stripe_session_id',
        'stripe_payment_intent',
        'paid_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'user_id' => 'integer',
        'store_id' => 'integer',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total' => 'decimal:2',
        'shipping_address' => 'array',
        'paid_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Hidden Attributes
    |--------------------------------------------------------------------------
    */

    protected $hidden = [
        'stripe_session_id',
        'stripe_payment_intent',
    ];

    /*
    |--------------------------------------------------------------------------
    | Database Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * An order contains multiple order items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * An order belongs to a customer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * An order belongs to a business or store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * An order can have multiple transactional email logs.
     */
    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    /**
     * An order can have multiple refund records.
     *
     * This relationship supports refund tracking,
     * financial reconciliation and refund history.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Processing
    |--------------------------------------------------------------------------
    */

    /**
     * Mark an order as paid using transaction locking.
     *
     * Returns true only when the payment status changes.
     */
    public function markPaid(
        ?string $paymentIntent = null
    ): bool {

        $changed = DB::transaction(function () use (
            $paymentIntent
        ) {

            $order = static::query()
                ->whereKey($this->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Prevent repeated payment processing.
             */

            if ($order->payment_status === 'paid') {
                return false;
            }

            /*
             * Cancelled or refunded orders
             * require reconciliation.
             */

            if (
                in_array(
                    $order->status,
                    ['cancelled', 'refunded'],
                    true
                ) ||
                $order->payment_status === 'refunded'
            ) {

                throw new RuntimeException(
                    "Payment reconciliation required for order {$order->number}."
                );
            }

            $order->update([

                'status' => $order->status === 'pending'
                    ? 'paid'
                    : $order->status,

                'payment_status' => 'paid',

                'stripe_payment_intent' =>
                    $paymentIntent
                    ?? $order->stripe_payment_intent,

                'paid_at' => now(),
            ]);

            return true;

        }, 3);

        $this->refresh();

        return $changed;
    }

    /*
    |--------------------------------------------------------------------------
    | Order Cancellation and Inventory
    |--------------------------------------------------------------------------
    */

    /**
     * Cancel an unpaid order and restore reserved stock.
     *
     * Returns true only when cancellation occurs.
     */
    public function cancelAndRestock(): bool
    {
        $changed = DB::transaction(function () {

            $order = static::query()
                ->whereKey($this->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Only pending and unpaid orders
             * can be cancelled using this method.
             */

            if (
                $order->status !== 'pending' ||
                $order->payment_status !== 'unpaid'
            ) {
                return false;
            }

            /*
             * Restore reserved product inventory.
             */

            foreach (
                $order->items()
                    ->orderBy('product_variant_id')
                    ->get() as $item
            ) {

                if ($item->product_variant_id) {

                    ProductVariant::query()
                        ->whereKey($item->product_variant_id)
                        ->increment(
                            'stock',
                            $item->quantity
                        );
                }
            }

            /*
             * Update the order cancellation status.
             */

            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
            ]);

            return true;

        }, 3);

        $this->refresh();

        return $changed;
    }
}