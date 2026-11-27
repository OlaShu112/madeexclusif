<?php

namespace App\Services\Communication;

use App\Models\PlatformAnnouncement;
use App\Models\PlatformAutomationRule;
use App\Models\PlatformCommunication;
use App\Models\PlatformCommunicationRecipient;
use App\Models\PlatformEmailTemplate;
use App\Models\PlatformNotification;

class CommunicationOverviewService
{
    public function overview(): array
    {
        $communications = PlatformCommunication::query()
            ->selectRaw('
                COUNT(*) AS total,
                COALESCE(SUM(total_recipients), 0) AS recipients,
                COALESCE(SUM(sent_count), 0) AS sent,
                COALESCE(SUM(delivered_count), 0) AS delivered,
                COALESCE(SUM(failed_count), 0) AS failed,
                COALESCE(SUM(skipped_count), 0) AS skipped
            ')
            ->first();

        $sent = (int) $communications->sent;

        $delivered = (int) $communications->delivered;

        $deliveryRate = $sent > 0
            ? round(($delivered / $sent) * 100, 2)
            : 0;

        return [

            'summary' => [

                'total_communications' =>
                    (int) $communications->total,

                'total_recipients' =>
                    (int) $communications->recipients,

                'total_sent' => $sent,

                'total_delivered' => $delivered,

                'total_failed' =>
                    (int) $communications->failed,

                'total_skipped' =>
                    (int) $communications->skipped,

                'delivery_rate' => $deliveryRate,

            ],

            'communications' => [

                'draft' => PlatformCommunication::query()
                    ->where('status', 'draft')
                    ->count(),

                'scheduled' => PlatformCommunication::query()
                    ->where('status', 'scheduled')
                    ->count(),

                'queued' => PlatformCommunication::query()
                    ->where('status', 'queued')
                    ->count(),

                'processing' => PlatformCommunication::query()
                    ->where('status', 'processing')
                    ->count(),

                'completed' => PlatformCommunication::query()
                    ->where('status', 'completed')
                    ->count(),

                'partially_failed' => PlatformCommunication::query()
                    ->where('status', 'partially_failed')
                    ->count(),

                'failed' => PlatformCommunication::query()
                    ->where('status', 'failed')
                    ->count(),

                'cancelled' => PlatformCommunication::query()
                    ->where('status', 'cancelled')
                    ->count(),

            ],

            'channels' => [

                'email' => PlatformCommunication::query()
                    ->where('channel', 'email')
                    ->count(),

                'sms' => PlatformCommunication::query()
                    ->where('channel', 'sms')
                    ->count(),

                'in_app' => PlatformCommunication::query()
                    ->where('channel', 'in_app')
                    ->count(),

            ],

            'templates' => [

                'total' => PlatformEmailTemplate::count(),

                'active' => PlatformEmailTemplate::query()
                    ->where('is_active', true)
                    ->count(),

                'inactive' => PlatformEmailTemplate::query()
                    ->where('is_active', false)
                    ->count(),

                'system' => PlatformEmailTemplate::query()
                    ->where('is_system', true)
                    ->count(),

            ],

            'announcements' => [

                'total' => PlatformAnnouncement::count(),

                'draft' => PlatformAnnouncement::query()
                    ->where('status', 'draft')
                    ->count(),

                'published' => PlatformAnnouncement::query()
                    ->where('status', 'published')
                    ->count(),

                'active' => PlatformAnnouncement::query()
                    ->active()
                    ->count(),

                'scheduled' => PlatformAnnouncement::query()
                    ->where('status', 'scheduled')
                    ->count(),

            ],

            'notifications' => [

                'total' => PlatformNotification::count(),

                'unread' => PlatformNotification::query()
                    ->unread()
                    ->count(),

                'read' => PlatformNotification::query()
                    ->whereNotNull('read_at')
                    ->count(),

            ],

            'automation' => [

                'total' => PlatformAutomationRule::count(),

                'active' => PlatformAutomationRule::query()
                    ->where('is_active', true)
                    ->count(),

                'inactive' => PlatformAutomationRule::query()
                    ->where('is_active', false)
                    ->count(),

            ],

            'delivery' => [

                'pending' => PlatformCommunicationRecipient::query()
                    ->where('status', 'pending')
                    ->count(),

                'queued' => PlatformCommunicationRecipient::query()
                    ->where('status', 'queued')
                    ->count(),

                'sent' => PlatformCommunicationRecipient::query()
                    ->where('status', 'sent')
                    ->count(),

                'delivered' => PlatformCommunicationRecipient::query()
                    ->where('status', 'delivered')
                    ->count(),

                'failed' => PlatformCommunicationRecipient::query()
                    ->where('status', 'failed')
                    ->count(),

            ],

            'recent_communications' =>
                PlatformCommunication::query()
                    ->select([
                        'id',
                        'reference',
                        'name',
                        'channel',
                        'type',
                        'status',
                        'total_recipients',
                        'sent_count',
                        'delivered_count',
                        'failed_count',
                        'created_at',
                    ])
                    ->latest()
                    ->limit(10)
                    ->get(),

            'recent_announcements' =>
                PlatformAnnouncement::query()
                    ->select([
                        'id',
                        'title',
                        'type',
                        'status',
                        'is_pinned',
                        'published_at',
                        'created_at',
                    ])
                    ->latest()
                    ->limit(5)
                    ->get(),

            'generated_at' => now()->toIso8601String(),

        ];
    }
}