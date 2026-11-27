<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformCommunicationRecipient extends Model
{
    public const STATUSES = [
        'pending',
        'queued',
        'processing',
        'retrying',
        'sent',
        'delivered',
        'failed',
        'skipped',
        'cancelled',
    ];

    protected $fillable = [
        'platform_communication_id',
        'user_id',
        'store_id',
        'email_log_id',
        'recipient_name',
        'recipient_email',
        'recipient_phone',
        'recipient_key',
        'status',
        'attempts',
        'provider_message_id',
        'failure_reason',
        'queued_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'skipped_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'skipped_at' => 'datetime',
    ];

    protected $hidden = [
        'recipient_key',
        'provider_message_id',
    ];

    public function communication(): BelongsTo
    {
        return $this->belongsTo(
            PlatformCommunication::class,
            'platform_communication_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', 'delivered');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeSkipped(Builder $query): Builder
    {
        return $query->where('status', 'skipped');
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }
}