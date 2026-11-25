<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'rejected',
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
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'suspended_at',
        'suspended_by',
        'suspension_reason',
        'status_version',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'suspended_at' => 'datetime',
        'status_version' => 'integer',
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

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'rejected_by'
        );
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'suspended_by'
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Store payment configuration.
     */
    public function paymentSetting(): HasOne
    {
        return $this->hasOne(StorePaymentSetting::class);
    }

    /**
     * Independent storefront website theme.
     */
    public function websiteTheme(): HasOne
    {
        return $this->hasOne(StoreTheme::class);
    }

    /**
     * Media assets belonging to the independent storefront.
     */
    public function media(): HasMany
    {
        return $this->hasMany(StoreMedia::class);
    }
}