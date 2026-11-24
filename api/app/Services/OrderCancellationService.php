<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCancellationService
{
    public function __construct(
        private TransactionalEmailService $emails
    ) {
    }

    /**
     * Cancel an unpaid order following a verified
     * Stripe Checkout expiry event.
     *
     * This method must only be called after
     * webhook signature and session validation.
     */
    public function cancelExpiredCheckout(
        Order $order,
        string $sessionId
    ): bool {

        return DB::transaction(function () use (
            $order,
            $sessionId
        ) {

            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedOrder->stripe_session_id
                    !== $sessionId
            ) {

                throw ValidationException::withMessages([
                    'order' =>
                        'The checkout session does not match this order.',
                ]);
            }

            if (
                $lockedOrder->status !== 'pending' ||
                $lockedOrder->payment_status !== 'unpaid'
            ) {
                return false;
            }

            /*
             * Reuse the existing inventory
             * restoration functionality.
             */

            $changed = $lockedOrder->cancelAndRestock();

            if (! $changed) {
                return false;
            }

            $lockedOrder->refresh();

            $this->notifyCustomer($lockedOrder);

            return true;

        }, 3);
    }

    /**
     * Register the customer cancellation email.
     */
    private function notifyCustomer(
        Order $order
    ): void {

        $customerName =
            $order->shipping_address['name']
            ?? $order->shipping_address['full_name']
            ?? $order->user?->name
            ?? 'Customer';

        $this->emails->queue(

            notificationKey:
                "order:{$order->id}:order-cancelled",

            emailType:
                'order_cancelled',

            recipientEmail:
                $order->email,

            subject:
                "Order Cancelled {$order->number} | Made Exclusif",

            recipientName:
                $customerName,

            userId:
                $order->user_id,

            orderId:
                $order->id,

            storeId:
                $order->store_id
        );
    }
}
