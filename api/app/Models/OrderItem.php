<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * These fields are used when creating order items
     * during the checkout process.
     */
    protected $fillable = [
        'order_id',
        'product_variant_id',
        'name',
        'size_ml',
        'unit_price',
        'quantity',
        'line_total',
    ];

    /**
     * Attribute type casting.
     *
     * Monetary values are represented with
     * two decimal places.
     */
    protected $casts = [
        'order_id' => 'integer',
        'product_variant_id' => 'integer',
        'size_ml' => 'integer',
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'line_total' => 'decimal:2',
    ];

    /**
     * An order item belongs to an order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * An order item belongs to a product variant.
     *
     * Example:
     * Nuit de Oud - 50ml
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class,
            'product_variant_id'
        );
    }
}
