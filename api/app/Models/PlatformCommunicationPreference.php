<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformCommunicationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'marketing_email',
        'marketing_sms',
        'product_updates',
        'in_app_notifications',
        'consent_version',
        'consent_source',
        'email_consented_at',
        'sms_consented_at',
        'email_unsubscribed_at',
        'sms_unsubscribed_at',
    ];

    protected $casts = [
        'marketing_email' => 'boolean',
        'marketing_sms' => 'boolean',
        'product_updates' => 'boolean',
        'in_app_notifications' => 'boolean',
        'email_consented_at' => 'datetime',
        'sms_consented_at' => 'datetime',
        'email_unsubscribed_at' => 'datetime',
        'sms_unsubscribed_at' => 'datetime',
    ];

    protected $attributes = [
        'marketing_email' => false,
        'marketing_sms' => false,
        'product_updates' => false,
        'in_app_notifications' => true,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allowsMarketingEmail(): bool
    {
        return $this->marketing_email
            && $this->email_consented_at !== null
            && (
                $this->email_unsubscribed_at === null
                || $this->email_consented_at->gt(
                    $this->email_unsubscribed_at
                )
            );
    }

    public function allowsMarketingSms(): bool
    {
        return $this->marketing_sms
            && $this->sms_consented_at !== null
            && (
                $this->sms_unsubscribed_at === null
                || $this->sms_consented_at->gt(
                    $this->sms_unsubscribed_at
                )
            );
    }

    public function allowsInAppNotifications(): bool
    {
        return $this->in_app_notifications;
    }

    public function unsubscribeEmail(): void
    {
        $this->update([
            'marketing_email' => false,
            'email_unsubscribed_at' => now(),
        ]);
    }

    public function unsubscribeSms(): void
    {
        $this->update([
            'marketing_sms' => false,
            'sms_unsubscribed_at' => now(),
        ]);
    }
}