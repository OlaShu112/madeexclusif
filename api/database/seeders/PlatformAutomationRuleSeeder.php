<?php

namespace Database\Seeders;

use App\Models\PlatformAutomationRule;
use App\Models\PlatformEmailTemplate;
use Illuminate\Database\Seeder;

class PlatformAutomationRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [

            [
                'name' => 'Send Welcome Email',
                'slug' => 'send-welcome-email',
                'event' => 'user.registered',
                'template' => 'welcome',
            ],

            [
                'name' => 'Business Registration Confirmation',
                'slug' => 'business-registration-confirmation',
                'event' => 'business.registered',
                'template' => 'business-registration-received',
            ],

            [
                'name' => 'Business Approval Notification',
                'slug' => 'business-approval-notification',
                'event' => 'business.approved',
                'template' => 'business-approved',
            ],

            [
                'name' => 'Business Rejection Notification',
                'slug' => 'business-rejection-notification',
                'event' => 'business.rejected',
                'template' => 'business-rejected',
            ],

            [
                'name' => 'Business Suspension Notification',
                'slug' => 'business-suspension-notification',
                'event' => 'business.suspended',
                'template' => 'business-suspended',
            ],

            [
                'name' => 'Business Reactivation Notification',
                'slug' => 'business-reactivation-notification',
                'event' => 'business.reactivated',
                'template' => 'business-reactivated',
            ],

            [
                'name' => 'Subscription Activation Confirmation',
                'slug' => 'subscription-activation-confirmation',
                'event' => 'subscription.activated',
                'template' => 'subscription-confirmation',
            ],

            [
                'name' => 'Subscription Renewal Reminder',
                'slug' => 'subscription-renewal-reminder',
                'event' => 'subscription.renewal_due',
                'template' => 'subscription-renewal-reminder',
                'configuration' => [
                    'days_before' => 7,
                ],
            ],

            [
                'name' => 'Subscription Renewal Confirmation',
                'slug' => 'subscription-renewal-confirmation',
                'event' => 'subscription.renewed',
                'template' => 'subscription-renewed',
            ],

            [
                'name' => 'Subscription Payment Failure',
                'slug' => 'subscription-payment-failure',
                'event' => 'subscription.payment_failed',
                'template' => 'subscription-payment-failed',
            ],

            [
                'name' => 'Subscription Cancellation Confirmation',
                'slug' => 'subscription-cancellation-confirmation',
                'event' => 'subscription.cancelled',
                'template' => 'subscription-cancelled',
            ],

            [
                'name' => 'Trial Ending Reminder',
                'slug' => 'trial-ending-reminder',
                'event' => 'subscription.trial_ending',
                'template' => 'trial-ending-reminder',
                'configuration' => [
                    'days_before' => 3,
                ],
            ],

            [
                'name' => 'Invoice Issued Notification',
                'slug' => 'invoice-issued-notification',
                'event' => 'invoice.issued',
                'template' => 'invoice-issued',
            ],

            [
                'name' => 'Payment Receipt Notification',
                'slug' => 'payment-receipt-notification',
                'event' => 'payment.succeeded',
                'template' => 'payment-receipt',
            ],

            [
                'name' => 'Refund Confirmation Notification',
                'slug' => 'refund-confirmation-notification',
                'event' => 'refund.succeeded',
                'template' => 'refund-confirmation',
            ],

            [
                'name' => 'Payout Confirmation Notification',
                'slug' => 'payout-confirmation-notification',
                'event' => 'payout.paid',
                'template' => 'payout-confirmation',
            ],

            [
                'name' => 'Account Security Alert',
                'slug' => 'account-security-alert',
                'event' => 'security.alert',
                'template' => 'security-alert',
            ],

            [
                'name' => 'Scheduled Maintenance Notification',
                'slug' => 'scheduled-maintenance-notification',
                'event' => 'system.maintenance',
                'template' => 'scheduled-maintenance',
            ],
        ];

        foreach ($rules as $rule) {

            $template = PlatformEmailTemplate::where(
                'slug',
                $rule['template']
            )->firstOrFail();

            PlatformAutomationRule::firstOrCreate(
                [
                    'slug' => $rule['slug'],
                ],
                [
                    'platform_email_template_id' => $template->id,

                    'name' => $rule['name'],

                    'event' => $rule['event'],

                    'channel' => 'email',

                    'delay_minutes' => 0,

                    'conditions' => $rule['conditions'] ?? null,

                    'configuration' =>
                        $rule['configuration'] ?? null,

                    'is_active' => false,

                    'last_triggered_at' => null,
                ]
            );
        }

        $this->command?->info(
            'IVISION automation rules seeded successfully.'
        );
    }
}