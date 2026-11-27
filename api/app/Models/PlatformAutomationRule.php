<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAutomationRule extends Model
{
    public const CHANNELS = [
        'email',
        'sms',
        'in_app',
    ];

    public const EVENTS = [
        'user.registered',
        'business.registered',
        'business.approved',
        'business.rejected',
        'business.suspended',
        'business.reactivated',
        'subscription.activated',
        'subscription.renewal_due',
        'subscription.renewed',
        'subscription.cancelled',
        'subscription.payment_failed',
        'subscription.trial_ending',
        'subscription.trial_expired',
        'invoice.issued',
        'invoice.overdue',
        'payment.succeeded',
        'payment.failed',
        'refund.succeeded',
        'payout.paid',
        'security.alert',
        'system.maintenance',
    ];

    protected $fillable = [
        'platform_email_template_id',
        'created_by',
        'name',
        'slug',
        'event',
        'channel',
        'delay_minutes',
        'conditions',
        'configuration',
        'is_active',
        'last_triggered_at',
    ];

    protected $casts = [
        'delay_minutes' => 'integer',
        'conditions' => 'array',
        'configuration' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => false,
        'delay_minutes' => 0,
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent(
        Builder $query,
        string $event
    ): Builder {
        return $query->where('event', $event);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}