<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformCommunication extends Model
{
    public const CHANNELS = [
        'email',
        'sms',
        'in_app',
    ];

    public const TYPES = [
        'transactional',
        'operational',
        'marketing',
    ];

    public const STATUSES = [
        'draft',
        'scheduled',
        'queued',
        'processing',
        'completed',
        'partially_failed',
        'failed',
        'cancelled',
    ];

    public const AUDIENCES = [
        'all',
        'businesses',
        'customers',
        'administrators',
        'selected',
        'subscription_plan',
        'active_subscribers',
        'trial_subscribers',
    ];

    protected $fillable = [
        'platform_email_template_id',
        'created_by',
        'reference',
        'name',
        'channel',
        'type',
        'subject',
        'content',
        'audience_type',
        'audience_filters',
        'status',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'failed_count',
        'skipped_count',
        'scheduled_at',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'audience_filters' => 'array',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'delivered_count' => 'integer',
        'failed_count' => 'integer',
        'skipped_count' => 'integer',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(
            PlatformEmailTemplate::class,
            'platform_email_template_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(
            PlatformCommunicationRecipient::class
        );
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(
            PlatformCommunicationAttachment::class
        );
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(
            PlatformNotification::class
        );
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function isMarketing(): bool
    {
        return $this->type === 'marketing';
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function deliveryRate(): float
    {
        if ($this->sent_count === 0) {
            return 0.0;
        }

        return round(
            ($this->delivered_count / $this->sent_count) * 100,
            2
        );
    }
}