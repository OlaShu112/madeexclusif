<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformTaxRate extends Model
{
    protected $fillable = [
        'name',
        'country',
        'percentage',
        'is_inclusive',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'is_inclusive' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(PlatformInvoiceItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    public function scopeForCountry(
        Builder $query,
        string $country
    ): Builder {
        return $query->where('country', strtoupper($country));
    }
}