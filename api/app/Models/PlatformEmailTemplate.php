<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformEmailTemplate extends Model
{
    public const CATEGORIES = [
        'authentication',
        'account',
        'business',
        'subscription',
        'finance',
        'marketing',
        'security',
        'system',
        'support',
    ];

    protected $fillable = [
        'name',
        'slug',
        'category',
        'subject',
        'html_content',
        'text_content',
        'variables',
        'language',
        'is_active',
        'is_system',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function communications(): HasMany
    {
        return $this->hasMany(
            PlatformCommunication::class
        );
    }

    public function automationRules(): HasMany
    {
        return $this->hasMany(
            PlatformAutomationRule::class
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeForCategory(
        Builder $query,
        string $category
    ): Builder {
        return $query->where('category', $category);
    }
}