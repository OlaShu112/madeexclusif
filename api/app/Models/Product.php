<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    /** The only bottle sizes the perfume shop sells. */
    public const SIZES = [50, 100];

    protected $fillable = [
        'store_id',
        'brand_id',
        'category_id',
        'name',
        'slug',
        'description',
        'notes_top',
        'notes_heart',
        'notes_base',
        'gender',
        'concentration',
        'image',
        'accent',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['image_url'];

    protected static function booted(): void
    {
        static::creating(function (Product $p) {
            $base = $p->slug ?: Str::slug($p->name);
            $slug = $base;
            $i = 2;

            while (static::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }

            $p->slug = $slug;
        });
    }

    /**
     * Public URL of the uploaded product image.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->image) {
                return null;
            }

            return Str::startsWith($this->image, ['http://', 'https://'])
                ? $this->image
                : Storage::disk('public')->url($this->image);
        });
    }

    /**
     * Store that owns this product.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Brand associated with this product.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Category associated with this product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Product variants, ordered by bottle size.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->orderBy('size_ml');
    }

    /**
     * Customer reviews.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Scope for active products.
     */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }
}
