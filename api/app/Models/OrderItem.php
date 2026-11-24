<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'order_id',
        'product_variant_id',
        'name',
        'size_ml',
        'variant_label',
        'unit_price',
        'quantity',
        'line_total',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'order_id' => 'integer',
        'product_variant_id' => 'integer',
        'size_ml' => 'integer',
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'line_total' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Database Relationships
    |--------------------------------------------------------------------------
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class,
            'product_variant_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Product Variant Display
    |--------------------------------------------------------------------------
    */

    public function getDisplayVariantAttribute(): ?string
    {
        if ($this->variant_label) {
            return $this->variant_label;
        }

        if ($this->size_ml) {
            return $this->size_ml . 'ml';
        }

        return null;
    }
}