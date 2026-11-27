<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformNotification extends Model
{
    public const TYPES = [
        'information',
        'success',
        'warning',
        'critical',
        'subscription',
        'payment',
        'business',
        'security',
        'announcement',
        'system',
    ];

    protected $fillable = [
        'user_id',
        'store_id',
        'platform_communication_id',
        'notification_key',
        'type',
        'title',
        'message',
        'action_url',
        'data',
        'read_at',
        'dismissed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    protected $hidden = [
        'notification_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function communication(): BelongsTo
    {
        return $this->belongsTo(
            PlatformCommunication::class,
            'platform_communication_id'
        );
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query
            ->whereNull('read_at')
            ->whereNull('dismissed_at');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('dismissed_at');
    }

    public function scopeForUser(
        Builder $query,
        int $userId
    ): Builder {
        return $query->where('user_id', $userId);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update([
                'read_at' => now(),
            ]);
        }
    }

    public function dismiss(): void
    {
        if ($this->dismissed_at === null) {
            $this->update([
                'dismissed_at' => now(),
            ]);
        }
    }
}