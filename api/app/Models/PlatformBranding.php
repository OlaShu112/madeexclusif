<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformBranding extends Model
{
    protected $table = 'platform_branding';

    public const MEDIA_FIELDS = [
        'primary_logo_id',
        'light_logo_id',
        'dark_logo_id',
        'sidebar_logo_id',
        'favicon_id',
        'login_logo_id',
        'login_background_id',
        'email_logo_id',
    ];

    public const COLOUR_FIELDS = [
        'primary_colour',
        'secondary_colour',
        'accent_colour',
        'background_colour',
        'text_colour',
        'sidebar_colour',
        'sidebar_text_colour',
        'active_menu_colour',
        'header_colour',
        'footer_colour',
        'button_colour',
        'button_text_colour',
        'login_background_colour',
    ];

    protected $fillable = [
        'identifier',
        'platform_name',
        'tagline',
        'administration_title',
        'browser_title',
        'primary_logo_id',
        'light_logo_id',
        'dark_logo_id',
        'sidebar_logo_id',
        'favicon_id',
        'login_logo_id',
        'login_background_id',
        'email_logo_id',
        'primary_colour',
        'secondary_colour',
        'accent_colour',
        'background_colour',
        'text_colour',
        'sidebar_colour',
        'sidebar_text_colour',
        'active_menu_colour',
        'header_colour',
        'footer_colour',
        'button_colour',
        'button_text_colour',
        'login_background_colour',
        'font_family',
        'appearance',
        'copyright_text',
        'updated_by',
    ];

    public function primaryLogo(): BelongsTo
    {
        return $this->belongsTo(
            PlatformMedia::class,
            'primary_logo_id'
        );
    }

    public function lightLogo(): BelongsTo
    {
        return $this->belongsTo(
            PlatformMedia::class,
            'light_logo_id'
        );
    }

    public function darkLogo(): BelongsTo
    {
        return $this->belongsTo(
            PlatformMedia::class,
            'dark_logo_id'
        );
    }

    public function sidebarLogo(): BelongsTo
    {
        return $this->belongsTo(
            PlatformMedia::class,
            'sidebar_logo_id'
        );
    }

    public function favicon(): BelongsTo
    {
        return $this->belongsTo(
            PlatformMedia::class,
            'favicon_id'
        );
    }

    public function loginLogo(): BelongsTo
    {
        return $this->belongsTo(
            PlatformMedia::class,
            'login_logo_id'
        );
    }

    public function loginBackground(): BelongsTo
    {
        return $this->belongsTo(
            PlatformMedia::class,
            'login_background_id'
        );
    }

    public function emailLogo(): BelongsTo
    {
        return $this->belongsTo(
            PlatformMedia::class,
            'email_logo_id'
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}