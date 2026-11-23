<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * Supports both perfume and clothing variants.
     */
    protected $fillable = [
        'product_id',
        'sku',
        'size_ml',
        'size_label',
        'price',
        'stock',
    ];

    /**
     * Cast database attributes to appropriate types.
     */
    protected $casts = [
        'product_id' => 'integer',
        'size_ml' => 'integer',
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * Append the generated label to JSON responses.
     */
    protected $appends = [
        'label',
    ];

    /**
     * Generate a readable label for each variant.
     *
     * Examples:
     * Perfume: 30ml, 50ml, 100ml
     * Clothing: S, M, L, XL, XXL
     * Clothing: M - Black, XL - White
     * Other: One size
     */
    protected function label(): Attribute
    {
        return Attribute::get(function () {
            if ($this->size_ml !== null) {
                return "{$this->size_ml}ml";
            }

            if (
                $this->size_label !== null &&
                trim($this->size_label) !== ''
            ) {
                return $this->size_label;
            }

            return 'One size';
        });
    }

    /**
     * Each product variant belongs to one product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

