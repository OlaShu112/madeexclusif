<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Represents an independent business storefront
 * operating on the Made Exclusif marketplace.
 */
class Store extends Model
{
    public const STATUSES = [
        'pending',
        'active',
        'suspended',
    ];

    protected $fillable = [
        'user_id',
        'brand_id',
        'business_category_id',
        'name',
        'slug',
        'type',
        'tagline',
        'logo',
        'accent',
        'is_main',
        'status',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    protected $appends = [
        'logo_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (Store $store) {

            $base = $store->slug
                ?: (Str::slug($store->name) ?: 'store');

            $slug = $base;

            $i = 2;

            while (static::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }

            $store->slug = $slug;
        });
    }

    public static function main(): self
    {
        return static::where('is_main', true)
            ->firstOrFail();
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->logo
                ? Storage::disk('public')->url($this->logo)
                : null
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function businessCategory(): BelongsTo
    {
        return $this->belongsTo(
            BusinessCategory::class
        );
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}