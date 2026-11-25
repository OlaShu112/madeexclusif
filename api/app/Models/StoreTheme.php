<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreTheme extends Model
{
    protected $fillable = [

        // Website template.

        'template_key',
        'font_family',

        // Brand colours.

        'primary_colour',
        'secondary_colour',
        'background_colour',
        'text_colour',
        'header_colour',
        'footer_colour',

        // Announcement bar.

        'announcement_enabled',
        'announcement_text',
        'announcement_colour',

        // Header and navigation.

        'navigation_items',
        'header_settings',

        // Homepage.

        'hero_title',
        'hero_subtitle',
        'hero_image',
        'hero_button_text',
        'homepage_sections',

        // Footer.

        'footer_description',
        'footer_columns',
        'contact_email',
        'contact_phone',
        'delivery_information',

        // Social media.

        'social_links',

        // SEO.

        'seo_title',
        'seo_description',

        // Publication management.

        'draft_settings',
        'published_at',
    ];

    protected $casts = [
        'announcement_enabled' => 'boolean',
        'navigation_items' => 'array',
        'header_settings' => 'array',
        'homepage_sections' => 'array',
        'footer_columns' => 'array',
        'social_links' => 'array',

        // Publication management casts.

        'draft_settings' => 'array',
        'published_at' => 'datetime',
    ];

    protected $hidden = [
        'id',
        'store_id',
        'draft_settings',
        'created_at',
        'updated_at',
    ];

    /**
     * The business that owns this website.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}