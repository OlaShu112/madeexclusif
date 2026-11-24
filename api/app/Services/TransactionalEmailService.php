<?php

namespace App\Services;

use App\Jobs\SendTransactionalEmail;
use App\Models\EmailLog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionalEmailService
{
    /**
     * Register a transactional email and dispatch
     * its background processing job.
     */
    public function queue(
        string $notificationKey,
        string $emailType,
        string $recipientEmail,
        string $subject,
        ?string $recipientName = null,
        ?int $userId = null,
        ?int $orderId = null,
        ?int $storeId = null
    ): EmailLog {

        if (! filter_var(
            $recipientEmail,
            FILTER_VALIDATE_EMAIL
        )) {
            throw new InvalidArgumentException(
                'Invalid recipient email address.'
            );
        }

        return DB::transaction(function () use (
            $notificationKey,
            $emailType,
            $recipientEmail,
            $subject,
            $recipientName,
            $userId,
            $orderId,
            $storeId
        ) {

            /*
             * The notification_key column has a unique
             * database constraint to prevent duplicates.
             */

            $emailLog = EmailLog::firstOrCreate(
                [
                    'notification_key' => $notificationKey,
                ],
                [
                    'user_id' => $userId,
                    'order_id' => $orderId,
                    'store_id' => $storeId,
                    'recipient_email' => $recipientEmail,
                    'recipient_name' => $recipientName,
                    'email_type' => $emailType,
                    'subject' => $subject,
                    'status' => 'pending',
                    'attempts' => 0,
                    'provider' => config('mail.default'),
                ]
            );

            /*
             * Dispatch only when a new notification
             * has been registered.
             */

            if ($emailLog->wasRecentlyCreated) {

                $emailLog->update([
                    'status' => 'queued',
                    'queued_at' => now(),
                ]);

                SendTransactionalEmail::dispatch(
                    $emailLog->id
                )
                    ->onQueue(
                        config(
                            'marketplace.email_processing.queue',
                            'default'
                        )
                    )
                    ->afterCommit();
            }

            return $emailLog;

        }, 3);
    }
}