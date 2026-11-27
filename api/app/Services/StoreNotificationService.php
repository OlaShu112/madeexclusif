<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Log;

class StoreNotificationService
{
    public function __construct(
        private TransactionalEmailService $emails
    ) {
    }

    /**
     * Notify the business owner and administrator
     * when a new business application is submitted.
     */
    public function applicationSubmitted(Store $store): void
    {
        $store->loadMissing('owner');

        $this->notifyOwner(
            $store,
            "store:{$store->id}:application",
            'store_application',
            'Business Application Received | IVISION'
        );

        $adminEmail = config('marketplace.emails.admin');

        if (! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {

            Log::warning(
                'Marketplace administrator email is not configured.',
                ['store_id' => $store->id]
            );

            return;
        }

        $this->emails->queue(
            notificationKey:
                "store:{$store->id}:admin-application",

            emailType: 'admin_store_application',

            recipientEmail: $adminEmail,

            subject:
                'New Business Application | IVISION',

            recipientName: 'Administration Team',

            storeId: $store->id
        );
    }

    /**
     * Notify the owner when the business
     * is approved or reactivated.
     */
    public function approved(
        Store $store,
        bool $firstApproval
    ): void {

        $this->notifyOwner(
            $store,
            "store:{$store->id}:approved:{$store->status_version}",
            'store_approved',
            'Your Business Is Approved | IVISION'
        );

        if ($firstApproval) {

            $this->notifyOwner(
                $store,
                "store:{$store->id}:welcome",
                'seller_welcome',
                'Welcome to IVISION for Business!'
            );
        }
    }

    /**
     * Notify the owner when the application
     * has been rejected.
     */
    public function rejected(Store $store): void
    {
        $this->notifyOwner(
            $store,
            "store:{$store->id}:rejected:{$store->status_version}",
            'store_rejected',
            'Business Application Update | IVISION'
        );
    }

    /**
     * Notify the owner when the store
     * has been suspended.
     */
    public function suspended(Store $store): void
    {
        $this->notifyOwner(
            $store,
            "store:{$store->id}:suspended:{$store->status_version}",
            'store_suspended',
            'Important Business Suspension Notice | IVISION'
        );
    }

    /**
     * Register an email notification for
     * the authenticated business owner.
     */
    private function notifyOwner(
        Store $store,
        string $notificationKey,
        string $emailType,
        string $subject
    ): void {

        $store->loadMissing('owner');

        $owner = $store->owner;

        if (
            ! $owner ||
            ! filter_var($owner->email, FILTER_VALIDATE_EMAIL)
        ) {

            Log::warning(
                'Store notification recipient is unavailable.',
                [
                    'store_id' => $store->id,
                    'email_type' => $emailType,
                ]
            );

            return;
        }

        $this->emails->queue(
            notificationKey: $notificationKey,

            emailType: $emailType,

            recipientEmail: $owner->email,

            subject: $subject,

            recipientName: $owner->name,

            userId: $owner->id,

            storeId: $store->id
        );
    }
}