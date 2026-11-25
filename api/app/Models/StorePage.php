<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorePage extends Model
{
    public const TYPES = [
        'about',
        'contact',
        'faq',
        'privacy',
        'terms',
        'returns',
        'delivery',
        'cookies',
        'custom',
    ];

    protected $fillable = [
        'store_id',
        'title',
        'slug',
        'type',
        'content',
        'draft_settings',
        'seo_title',
        'seo_description',
        'is_published',
        'show_in_header',
        'show_in_footer',
        'sort_order',
        'published_at',
    ];

    protected $casts = [
        'draft_settings' => 'array',
        'is_published' => 'boolean',
        'show_in_header' => 'boolean',
        'show_in_footer' => 'boolean',
        'published_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    protected $hidden = [
        'draft_settings',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
