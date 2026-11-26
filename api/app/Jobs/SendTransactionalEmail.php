<?php

namespace App\Jobs;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendTransactionalEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [
        60,
        300,
        900,
    ];

    public function __construct(
        public int $emailLogId
    ) {
    }

    /**
     * Prevent concurrent processing of the same email.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(
                'transactional-email:' . $this->emailLogId
            ))
                ->releaseAfter(30)
                ->expireAfter(120),
        ];
    }

    /**
     * Process the queued transactional email.
     */
    public function handle(): void
    {
        $emailLog = EmailLog::query()
            ->with([
                'user',
                'order.items',
                'store.owner',
            ])
            ->findOrFail($this->emailLogId);

        if (in_array(
            $emailLog->status,
            ['sent', 'delivered', 'logged'],
            true
        )) {
            return;
        }

        $emailLog->increment('attempts');

        $emailLog->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        try {

            $view = $this->resolveView(
                $emailLog->email_type
            );

            $mailer = config('mail.default');

            Mail::mailer($mailer)->send(
                $view,
                [
                    'emailLog' => $emailLog,

                    'user' => $emailLog->user,

                    'order' => $emailLog->order,

                    'store' => $emailLog->store,

                    'recipientName' =>
                        $emailLog->recipient_name,

                    'frontendUrl' =>
                        config('app.frontend_url'),
                ],

                function ($message) use ($emailLog) {

                    $message->to(
                        $emailLog->recipient_email,
                        $emailLog->recipient_name
                    );

                    $message->subject(
                        $emailLog->subject
                    );
                }
            );

            /*
             * Development mailers do not deliver
             * actual emails to recipients.
             */

            $isDevelopmentMailer = in_array(
                $mailer,
                ['log', 'array'],
                true
            );

            $emailLog->update([
                'status' => $isDevelopmentMailer
                    ? 'logged'
                    : 'sent',

                'provider' => $mailer,

                'sent_at' => $isDevelopmentMailer
                    ? null
                    : now(),

                'failed_at' => null,

                'error_message' => null,
            ]);

        } catch (Throwable $exception) {

            $emailLog->update([
                'status' => 'retrying',

                'error_message' => mb_substr(
                    $exception->getMessage(),
                    0,
                    1000
                ),
            ]);

            throw $exception;
        }
    }

    /**
     * Map transactional email types to templates.
     */
    private function resolveView(
        string $emailType
    ): string {

        return match ($emailType) {

            /*
             * Customer account security.
             */

            'password_changed' =>
                'emails.customer.password-changed',

            /*
             * Customer order and payment emails.
             */

            'order_confirmation' =>
                'emails.orders.order-confirmation',

            'payment_confirmation' =>
                'emails.orders.payment-confirmation',

            'payment_failed' =>
                'emails.orders.payment-failed',

            'order_processing' =>
                'emails.orders.order-processing',

            'order_dispatched' =>
                'emails.orders.order-dispatched',

            'order_delivered' =>
                'emails.orders.order-delivered',

            'order_cancelled' =>
                'emails.orders.order-cancelled',

            'refund_confirmation' =>
                'emails.orders.refund-confirmation',

            /*
             * Business registration and seller emails.
             */

            'store_application' =>
                'emails.seller.store-application',

            'store_approved' =>
                'emails.seller.store-approved',

            'store_rejected' =>
                'emails.seller.store-rejected',

            'store_suspended' =>
                'emails.seller.store-suspended',

            'seller_welcome' =>
                'emails.seller.welcome',

            'seller_new_order' =>
                'emails.seller.new-customer-order',

            'seller_payout_confirmation' =>
                'emails.seller.payout-confirmation',

            /*
             * Administrator notifications.
             */

            'admin_store_application' =>
                'emails.admin.new-store-application',

            /*
             * Reject unsupported email types.
             */

            default => throw new RuntimeException(
                "Unsupported email type: {$emailType}"
            ),
        };
    }

    /**
     * Handle permanent email processing failure.
     */
    public function failed(
        ?Throwable $exception
    ): void {

        EmailLog::whereKey(
            $this->emailLogId
        )
            ->whereNotIn(
                'status',
                ['sent', 'delivered', 'logged']
            )
            ->update([
                'status' => 'failed',

                'failed_at' => now(),

                'error_message' => mb_substr(
                    $exception?->getMessage()
                        ?? 'Email processing failed.',
                    0,
                    1000
                ),
            ]);

        Log::error(
            'Transactional email processing failed.',
            [
                'email_log_id' => $this->emailLogId,
            ]
        );
    }
}