<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'value', 'min_subtotal', 'usage_limit', 'used_count', 'expires_at', 'is_active'];

    protected $casts = ['expires_at' => 'datetime', 'is_active' => 'boolean'];

    /** Returns the discount amount for a subtotal, or null with a reason if the coupon cannot be used. */
    public function discountFor(float $subtotal): array
    {
        if (! $this->is_active) {
            return [null, 'This code is no longer active.'];
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return [null, 'This code has expired.'];
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return [null, 'This code has reached its usage limit.'];
        }
        if ($subtotal < (float) $this->min_subtotal) {
            return [null, 'Spend £' . number_format($this->min_subtotal, 2) . ' or more to use this code.'];
        }

        $amount = $this->type === 'percent'
            ? round($subtotal * ((float) $this->value / 100), 2)
            : min((float) $this->value, $subtotal);

        return [$amount, null];
    }
}
