<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAnnouncement extends Model
{
    public const TYPES = [
        'information',
        'success',
        'warning',
        'critical',
    ];

    public const STATUSES = [
        'draft',
        'scheduled',
        'published',
        'archived',
        'cancelled',
    ];

    protected $fillable = [
        'created_by',
        'title',
        'content',
        'type',
        'audience_type',
        'audience_filters',
        'status',
        'is_pinned',
        'is_dismissible',
        'action_label',
        'action_url',
        'starts_at',
        'ends_at',
        'published_at',
    ];

    protected $casts = [
        'audience_filters' => 'array',
        'is_pinned' => 'boolean',
        'is_dismissible' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    public function isActive(): bool
    {
        return $this->status === 'published'
            && (
                $this->starts_at === null
                || !$this->starts_at->isFuture()
            )
            && (
                $this->ends_at === null
                || $this->ends_at->isFuture()
            );
    }
}