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
 * A store is one branded "website" on the platform: the main MadeExlusif perfume store,
 * or a seller's own store (e.g. clothing) living at /s/{slug}.
 */
class Store extends Model
{
    public const STATUSES = ['pending', 'active', 'suspended'];

    protected $fillable = [
        'user_id', 'brand_id', 'name', 'slug', 'type', 'tagline', 'logo', 'accent', 'is_main', 'status',
    ];

    protected $casts = ['is_main' => 'boolean'];

    protected $appends = ['logo_url'];

    protected static function booted(): void
    {
        static::creating(function (Store $s) {
            $base = $s->slug ?: (Str::slug($s->name) ?: 'store');
            $slug = $base;
            $i = 2;
            while (static::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $s->slug = $slug;
        });
    }

    public static function main(): self
    {
        return static::where('is_main', true)->firstOrFail();
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo ? Storage::disk('public')->url($this->logo) : null);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
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
