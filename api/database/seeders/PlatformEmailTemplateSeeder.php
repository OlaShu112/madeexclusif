<?php

namespace Database\Seeders;

use App\Models\PlatformEmailTemplate;
use Illuminate\Database\Seeder;

class PlatformEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [

            /*
            |--------------------------------------------------------------------------
            | 1. Welcome Email
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Welcome to IVISION',
                'slug' => 'welcome',
                'category' => 'account',
                'subject' => 'Welcome to IVISION',
                'heading' => 'Welcome to IVISION!',
                'body' => <<<'TEXT'
Dear {{first_name}},

Thank you for joining IVISION. We are delighted to welcome you to a smarter, more connected digital experience.

At IVISION, we bring intelligent technology, business management, digital commerce and automation together to help individuals and businesses achieve more.

Your account is ready, and you can now explore the tools and services available through your dashboard.

We look forward to supporting your journey.
TEXT,
                'button_label' => 'Access Your Dashboard',
                'button_url' => '{{dashboard_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 2. Business Registration
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Business Registration Received',
                'slug' => 'business-registration-received',
                'category' => 'business',
                'subject' => 'We have received your business registration',
                'heading' => 'Business Registration Received',
                'body' => <<<'TEXT'
Dear {{first_name}},

Thank you for registering {{business_name}} with IVISION.

We have successfully received your business registration and our team will review the information provided.

You will receive a further notification once the review has been completed.

In the meantime, you can access your account to review your registration details.

Thank you for choosing IVISION as your digital technology partner.
TEXT,
                'button_label' => 'View Registration',
                'button_url' => '{{dashboard_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 3. Business Approved
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Business Approved',
                'slug' => 'business-approved',
                'category' => 'business',
                'subject' => 'Your IVISION business has been approved',
                'heading' => 'Your Business Has Been Approved!',
                'body' => <<<'TEXT'
Dear {{first_name}},

We are pleased to inform you that {{business_name}} has been successfully approved on IVISION.

You can now access the business management features available under your subscription.

Your business dashboard provides access to your digital services, account configuration and operational tools.

We look forward to helping your business grow through intelligent digital technology.
TEXT,
                'button_label' => 'Access Business Dashboard',
                'button_url' => '{{dashboard_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 4. Business Rejected
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Business Registration Rejected',
                'slug' => 'business-rejected',
                'category' => 'business',
                'subject' => 'Update regarding your business registration',
                'heading' => 'Business Registration Update',
                'body' => <<<'TEXT'
Dear {{first_name}},

Following our review of the registration submitted for {{business_name}}, we are unable to approve your application at this time.

Reason: {{reason}}

Please review the information provided and contact our support team if you require clarification or assistance with the next steps.

Thank you for your interest in IVISION.
TEXT,
                'button_label' => 'Contact Support',
                'button_url' => '{{support_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 5. Business Suspended
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Business Suspended',
                'slug' => 'business-suspended',
                'category' => 'business',
                'subject' => 'Important update regarding your IVISION business',
                'heading' => 'Business Account Suspended',
                'body' => <<<'TEXT'
Dear {{first_name}},

We are writing to inform you that access to certain IVISION services for {{business_name}} has been suspended.

Reason: {{reason}}

Please contact our support team to discuss the situation and any steps required to restore access.

We appreciate your cooperation.
TEXT,
                'button_label' => 'Contact Support',
                'button_url' => '{{support_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 6. Business Reactivated
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Business Reactivated',
                'slug' => 'business-reactivated',
                'category' => 'business',
                'subject' => 'Your IVISION business has been reactivated',
                'heading' => 'Business Account Reactivated',
                'body' => <<<'TEXT'
Dear {{first_name}},

We are pleased to confirm that your business account for {{business_name}} has been reactivated.

You can now access the services and features available under your account.

Thank you for your cooperation and continued trust in IVISION.
TEXT,
                'button_label' => 'Access Dashboard',
                'button_url' => '{{dashboard_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 7. Subscription Confirmation
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Subscription Confirmation',
                'slug' => 'subscription-confirmation',
                'category' => 'subscription',
                'subject' => 'Your IVISION subscription is active',
                'heading' => 'Subscription Confirmed',
                'body' => <<<'TEXT'
Dear {{first_name}},

Thank you for subscribing to IVISION.

Your subscription for {{business_name}} has been successfully activated.

Subscription details:

Plan: {{plan_name}}
Subscription amount: {{amount}}
Billing cycle: {{billing_cycle}}
Next billing date: {{next_billing_date}}

You can manage your subscription, billing information and available services through your dashboard.

Thank you for choosing IVISION.
TEXT,
                'button_label' => 'Manage Subscription',
                'button_url' => '{{subscription_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 8. Subscription Renewal Reminder
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Subscription Renewal Reminder',
                'slug' => 'subscription-renewal-reminder',
                'category' => 'subscription',
                'subject' => 'Your IVISION subscription renewal is approaching',
                'heading' => 'Upcoming Subscription Renewal',
                'body' => <<<'TEXT'
Dear {{first_name}},

This is a friendly reminder that your IVISION subscription for {{business_name}} is approaching its renewal date.

Plan: {{plan_name}}
Renewal amount: {{amount}}
Renewal date: {{renewal_date}}

Please ensure your billing information is up to date to avoid unnecessary interruptions to your services.

You can review or manage your subscription through your account.
TEXT,
                'button_label' => 'Review Subscription',
                'button_url' => '{{subscription_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 9. Subscription Renewed
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Subscription Renewed',
                'slug' => 'subscription-renewed',
                'category' => 'subscription',
                'subject' => 'Your IVISION subscription has been renewed',
                'heading' => 'Subscription Successfully Renewed',
                'body' => <<<'TEXT'
Dear {{first_name}},

We are pleased to confirm that your IVISION subscription for {{business_name}} has been successfully renewed.

Plan: {{plan_name}}
Amount paid: {{amount}}
Next billing date: {{next_billing_date}}

Your subscription services remain active.

Thank you for continuing to choose IVISION as your digital technology partner.
TEXT,
                'button_label' => 'View Subscription',
                'button_url' => '{{subscription_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 10. Subscription Payment Failed
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Subscription Payment Failed',
                'slug' => 'subscription-payment-failed',
                'category' => 'subscription',
                'subject' => 'Action required: IVISION subscription payment',
                'heading' => 'Subscription Payment Unsuccessful',
                'body' => <<<'TEXT'
Dear {{first_name}},

We were unable to process your recent subscription payment for {{business_name}}.

Plan: {{plan_name}}
Outstanding amount: {{amount}}

Please review your payment information and complete any required action to maintain access to your subscription services.

If you require assistance, our support team is available to help.
TEXT,
                'button_label' => 'Review Billing Information',
                'button_url' => '{{billing_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 11. Subscription Cancelled
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Subscription Cancelled',
                'slug' => 'subscription-cancelled',
                'category' => 'subscription',
                'subject' => 'Confirmation of your IVISION subscription cancellation',
                'heading' => 'Subscription Cancelled',
                'body' => <<<'TEXT'
Dear {{first_name}},

This email confirms the cancellation of your IVISION subscription for {{business_name}}.

Plan: {{plan_name}}
Cancellation date: {{cancellation_date}}
Service access ends: {{access_end_date}}

Please review your account for information about remaining access and any outstanding billing matters.

Thank you for using IVISION. We hope to welcome you back in the future.
TEXT,
                'button_label' => 'View Subscription',
                'button_url' => '{{subscription_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 12. Trial Ending
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Trial Ending Reminder',
                'slug' => 'trial-ending-reminder',
                'category' => 'subscription',
                'subject' => 'Your IVISION trial is ending soon',
                'heading' => 'Your Free Trial Is Ending Soon',
                'body' => <<<'TEXT'
Dear {{first_name}},

We hope you have enjoyed exploring IVISION.

Your trial for {{business_name}} is scheduled to end on {{trial_end_date}}.

You can review our subscription options and select the services that best meet your business requirements.

Please check your subscription settings for information about any scheduled billing or changes to service access.
TEXT,
                'button_label' => 'Explore Subscription Plans',
                'button_url' => '{{pricing_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 13. Invoice Issued
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Invoice Issued',
                'slug' => 'invoice-issued',
                'category' => 'finance',
                'subject' => 'Your IVISION invoice {{invoice_number}}',
                'heading' => 'Your Invoice Is Available',
                'body' => <<<'TEXT'
Dear {{first_name}},

A new invoice has been issued for {{business_name}}.

Invoice number: {{invoice_number}}
Invoice date: {{invoice_date}}
Total amount: {{amount}}
Amount due: {{amount_due}}
Payment due date: {{due_date}}

You can securely access your invoice and review its details through your IVISION account.

Thank you for your business.
TEXT,
                'button_label' => 'View Invoice',
                'button_url' => '{{invoice_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 14. Payment Receipt
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Payment Receipt',
                'slug' => 'payment-receipt',
                'category' => 'finance',
                'subject' => 'IVISION payment receipt {{payment_reference}}',
                'heading' => 'Payment Received',
                'body' => <<<'TEXT'
Dear {{first_name}},

Thank you for your payment to IVISION.

We confirm that your payment has been successfully received.

Payment reference: {{payment_reference}}
Invoice number: {{invoice_number}}
Amount paid: {{amount}}
Payment date: {{payment_date}}
Payment method: {{payment_method}}

Please retain this confirmation for your financial records.

Thank you for choosing IVISION.
TEXT,
                'button_label' => 'View Payment Receipt',
                'button_url' => '{{receipt_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 15. Refund Confirmation
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Refund Confirmation',
                'slug' => 'refund-confirmation',
                'category' => 'finance',
                'subject' => 'Your IVISION refund has been processed',
                'heading' => 'Refund Confirmation',
                'body' => <<<'TEXT'
Dear {{first_name}},

We confirm that your refund has been processed.

Refund reference: {{refund_reference}}
Original payment reference: {{payment_reference}}
Refund amount: {{amount}}
Refund date: {{refund_date}}

The time required for the funds to appear in your account may depend on your payment provider or financial institution.

You can review your refund details through your IVISION account.
TEXT,
                'button_label' => 'View Refund Details',
                'button_url' => '{{refund_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 16. Payout Confirmation
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Payout Confirmation',
                'slug' => 'payout-confirmation',
                'category' => 'finance',
                'subject' => 'Your IVISION business payout has been processed',
                'heading' => 'Business Payout Confirmation',
                'body' => <<<'TEXT'
Dear {{first_name}},

We confirm that a payout for {{business_name}} has been processed.

Payout reference: {{payout_reference}}
Payout amount: {{amount}}
Payout date: {{payout_date}}

The time required for the funds to reach your designated bank account may vary depending on your financial institution.

Please visit your business dashboard for further details.
TEXT,
                'button_label' => 'View Payout',
                'button_url' => '{{payout_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 17. Password Reset
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Password Reset',
                'slug' => 'password-reset',
                'category' => 'authentication',
                'subject' => 'Reset your IVISION password',
                'heading' => 'Password Reset Request',
                'body' => <<<'TEXT'
Dear {{first_name}},

We received a request to reset the password associated with your IVISION account.

Please use the secure link below to create a new password.

This link will expire after {{expiry_minutes}} minutes.

If you did not request a password reset, you can safely disregard this email. Your password will remain unchanged.

For your security, never share your password or reset link with anyone.
TEXT,
                'button_label' => 'Reset Password',
                'button_url' => '{{reset_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 18. Email Verification
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Email Verification',
                'slug' => 'email-verification',
                'category' => 'authentication',
                'subject' => 'Verify your IVISION email address',
                'heading' => 'Verify Your Email Address',
                'body' => <<<'TEXT'
Dear {{first_name}},

Thank you for creating an IVISION account.

To help protect your account and confirm your registration, please verify your email address using the secure link below.

If you did not create an IVISION account, you can disregard this email.

Thank you for joining IVISION.
TEXT,
                'button_label' => 'Verify Email Address',
                'button_url' => '{{verification_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 19. Platform Announcement
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Platform Announcement',
                'slug' => 'platform-announcement',
                'category' => 'system',
                'subject' => 'IVISION announcement: {{announcement_title}}',
                'heading' => '{{announcement_title}}',
                'body' => <<<'TEXT'
Dear {{first_name}},

We would like to share an important update regarding IVISION.

{{announcement_message}}

We remain committed to delivering reliable, secure and innovative digital technology services.

Thank you for being part of IVISION.
TEXT,
                'button_label' => 'View Announcement',
                'button_url' => '{{announcement_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 20. Promotional Offer
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Promotional Offer',
                'slug' => 'promotional-offer',
                'category' => 'marketing',
                'subject' => 'Discover our latest IVISION offer',
                'heading' => '{{offer_title}}',
                'body' => <<<'TEXT'
Dear {{first_name}},

We are pleased to introduce a new promotional offer from IVISION.

{{offer_description}}

Offer expiry: {{offer_expiry}}

Eligibility requirements and applicable terms and conditions may apply.

Explore the offer to discover how IVISION can support your digital journey.
TEXT,
                'button_label' => 'Explore Offer',
                'button_url' => '{{offer_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 21. Security Alert
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Security Alert',
                'slug' => 'security-alert',
                'category' => 'security',
                'subject' => 'Important security notification from IVISION',
                'heading' => 'Account Security Alert',
                'body' => <<<'TEXT'
Dear {{first_name}},

We are contacting you regarding a security-related event associated with your IVISION account.

Event: {{security_event}}
Date and time: {{event_time}}

If you do not recognise this activity, please review your account security settings and contact our support team.

IVISION will never request your password or security verification codes by email.
TEXT,
                'button_label' => 'Review Account Security',
                'button_url' => '{{security_url}}',
            ],

            /*
            |--------------------------------------------------------------------------
            | 22. Scheduled Maintenance
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Scheduled Maintenance',
                'slug' => 'scheduled-maintenance',
                'category' => 'system',
                'subject' => 'IVISION scheduled maintenance notification',
                'heading' => 'Scheduled Platform Maintenance',
                'body' => <<<'TEXT'
Dear {{first_name}},

To maintain the reliability, security and performance of IVISION, we will be carrying out scheduled platform maintenance.

Maintenance starts: {{maintenance_start}}
Expected completion: {{maintenance_end}}
Affected services: {{affected_services}}

Some services may be temporarily unavailable during this period.

We apologise for any inconvenience and appreciate your patience while we continue improving our platform.
TEXT,
                'button_label' => 'View Service Status',
                'button_url' => '{{status_url}}',
            ],
        ];

        foreach ($templates as $template) {

            $html = $this->buildHtml($template);

            $text = $this->buildText($template);

            preg_match_all(
                '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
                $template['subject'] . $html . $text,
                $matches
            );

            $variables = array_values(
                array_unique($matches[1])
            );

            sort($variables);

            PlatformEmailTemplate::firstOrCreate(
                [
                    'slug' => $template['slug'],
                ],
                [
                    'name' => $template['name'],
                    'category' => $template['category'],
                    'subject' => $template['subject'],
                    'html_content' => $html,
                    'text_content' => $text,
                    'variables' => $variables,
                    'language' => 'en',
                    'is_active' => true,
                    'is_system' => true,
                ]
            );
        }

        $this->command?->info(
            'IVISION email templates seeded successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Build HTML Email
    |--------------------------------------------------------------------------
    */

    private function buildHtml(array $template): string
    {
        $heading = e($template['heading']);

        $body = nl2br(e($template['body']));

        $buttonLabel = e($template['button_label']);

        $buttonUrl = e($template['button_url']);

        $footer = '';

        if ($template['category'] === 'marketing') {
            $footer = <<<'HTML'
<p style="font-size:12px;color:#64748b;">
    You are receiving this email because you subscribed
    to IVISION marketing communications.
    <a href="{{unsubscribe_url}}"
       style="color:#334155;">
        Unsubscribe
    </a>
</p>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>{$heading}</title>
</head>

<body style="margin:0;padding:0;background:#f1f5f9;
font-family:Arial,Helvetica,sans-serif;color:#1e293b;">

<table role="presentation"
       width="100%"
       cellspacing="0"
       cellpadding="0"
       style="background:#f1f5f9;">

<tr>
<td align="center" style="padding:30px 15px;">

<table role="presentation"
       width="100%"
       cellspacing="0"
       cellpadding="0"
       style="max-width:620px;background:#ffffff;
       border-radius:12px;overflow:hidden;">

<tr>
<td style="background:#0f172a;padding:30px;
text-align:center;">

    <div style="font-size:32px;font-weight:bold;
    letter-spacing:4px;color:#ffffff;">
        IVISION
    </div>

    <div style="font-size:11px;letter-spacing:2px;
    color:#cbd5e1;margin-top:8px;">
        INTELLIGENT DIGITAL TECHNOLOGY
    </div>

</td>
</tr>

<tr>
<td style="padding:35px;">

    <h1 style="font-size:24px;line-height:1.4;
    margin:0 0 24px;color:#0f172a;">
        {$heading}
    </h1>

    <div style="font-size:15px;line-height:1.8;
    color:#334155;">
        {$body}
    </div>

    <table role="presentation"
           cellspacing="0"
           cellpadding="0"
           style="margin-top:30px;margin-bottom:30px;">

        <tr>
            <td style="background:#0f172a;
            border-radius:6px;">

                <a href="{$buttonUrl}"
                   style="display:inline-block;
                   padding:14px 24px;color:#ffffff;
                   text-decoration:none;font-size:14px;
                   font-weight:bold;">
                    {$buttonLabel}
                </a>

            </td>
        </tr>

    </table>

    <p style="font-size:15px;line-height:1.7;">
        Kind regards,<br>
        <strong>The IVISION Team</strong>
    </p>

</td>
</tr>

<tr>
<td style="background:#f8fafc;padding:25px;
text-align:center;border-top:1px solid #e2e8f0;">

    <p style="font-size:14px;font-weight:bold;
    color:#0f172a;margin:0;">
        IVISION
    </p>

    <p style="font-size:12px;color:#64748b;">
        Intelligent Digital Technology
    </p>

    <p style="font-size:12px;">
        <a href="https://ivisionone.com"
           style="color:#334155;">
            www.ivisionone.com
        </a>
    </p>

    <p style="font-size:12px;color:#64748b;">
        &copy; {{year}} IVISION. All rights reserved.
    </p>

    {$footer}

</td>
</tr>

</table>

</td>
</tr>

</table>

</body>
</html>
HTML;
    }

    /*
    |--------------------------------------------------------------------------
    | Build Plain Text Email
    |--------------------------------------------------------------------------
    */

    private function buildText(array $template): string
    {
        $text = $template['heading']
            . "\n\n"
            . $template['body']
            . "\n\n"
            . $template['button_label']
            . ': '
            . $template['button_url']
            . "\n\n"
            . "Kind regards,\n"
            . "The IVISION Team\n\n"
            . "IVISION\n"
            . "Intelligent Digital Technology\n"
            . "https://ivisionone.com\n\n"
            . "Copyright {{year}} IVISION. All rights reserved.";

        if ($template['category'] === 'marketing') {
            $text .= "\n\nUnsubscribe: {{unsubscribe_url}}";
        }

        return $text;
    }
}