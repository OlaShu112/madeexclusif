<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\TransactionalEmailService;
use App\Services\OrderCancellationService;
use App\Services\SellerLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;
use RuntimeException;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TransactionalEmailService $emails,
        OrderCancellationService $cancellations,
        SellerLedgerService $ledger
    ): JsonResponse {

        /*
         * 1. Verify webhook configuration.
         */

        $secret = config('stripe.webhook_secret');

        if (! $secret) {

            return response()->json([
                'error' =>
                    'Webhook secret not configured.',
            ], 503);
        }

        /*
         * 2. Verify Stripe webhook signature.
         */

        try {

            $event = Webhook::constructEvent(

                $request->getContent(),

                (string) $request->header(
                    'Stripe-Signature'
                ),

                $secret
            );

        } catch (
            UnexpectedValueException |
            SignatureVerificationException $exception
        ) {

            return response()->json([
                'error' => 'Invalid webhook signature.',
            ], 400);
        }

        /*
         * 3. Process supported Checkout events only.
         */

        $supportedEvents = [

            'checkout.session.completed',

            'checkout.session.async_payment_succeeded',

            'checkout.session.async_payment_failed',

            'checkout.session.expired',
        ];

        if (! in_array(
            $event->type,
            $supportedEvents,
            true
        )) {

            return response()->json([
                'received' => true,
            ]);
        }

        $session = $event->data->object;

        try {

            DB::transaction(function () use (
                $event,
                $session,
                $emails,
                $cancellations,
                $ledger
            ) {

                /*
                 * 4. Locate and lock the order.
                 */

                $order = Order::query()
                    ->where(
                        'stripe_session_id',
                        $session->id
                    )
                    ->lockForUpdate()
                    ->first();

                /*
                 * Recover an order when Stripe created
                 * a session before its ID was saved.
                 */

                if (! $order) {

                    $orderNumber =
                        $session->metadata->order_number
                        ?? null;

                    if ($orderNumber) {

                        $order = Order::query()
                            ->where('number', $orderNumber)
                            ->lockForUpdate()
                            ->first();
                    }
                }

                if (! $order) {

                    Log::warning(
                        'Stripe webhook order not found.',
                        [
                            'event_id' => $event->id,
                        ]
                    );

                    throw new RuntimeException(
                        'Order reconciliation required.'
                    );
                }

                /*
                 * 5. Validate session ownership.
                 */

                if (
                    $session->client_reference_id !==
                    $order->number
                ) {

                    throw new RuntimeException(
                        'Stripe order reference mismatch.'
                    );
                }

                if (
                    $order->stripe_session_id &&
                    $order->stripe_session_id !==
                    $session->id
                ) {

                    throw new RuntimeException(
                        'Stripe session mismatch.'
                    );
                }

                /*
                 * 6. Validate payment amount and currency.
                 */

                $expectedAmount = (int) round(
                    ((float) $order->total) * 100
                );

                if (
                    (int) $session->amount_total !==
                    $expectedAmount
                ) {

                    throw new RuntimeException(
                        'Stripe payment amount mismatch.'
                    );
                }

                if (
                    strtolower((string) $session->currency)
                    !==
                    strtolower(
                        config('stripe.currency', 'gbp')
                    )
                ) {

                    throw new RuntimeException(
                        'Stripe payment currency mismatch.'
                    );
                }

                if (! $order->stripe_session_id) {

                    $order->update([
                        'stripe_session_id' => $session->id,
                    ]);
                }

                /*
                 * 7. Process the payment event.
                 */

                switch ($event->type) {

                    /*
                     * Successful payment.
                     */

                    case 'checkout.session.completed':

                    case 'checkout.session.async_payment_succeeded':

                        if (
                            $session->payment_status === 'paid'
                        ) {

                            $this->processSuccessfulPayment(
                                $order,
                                $session->payment_intent ?? null,
                                $emails,
                                $ledger
                            );
                        }

                        break;

                    /*
                     * Failed payment.
                     */

                    case 'checkout.session.async_payment_failed':

                        $this->processFailedPayment(
                            $order,
                            $emails
                        );

                        break;

                    /*
                     * Expired Checkout session.
                     *
                     * Confirm that the session has expired
                     * and payment was not completed before
                     * initiating order cancellation.
                     */

                    case 'checkout.session.expired':

                        if (
                            $session->status !== 'expired' ||
                            $session->payment_status !== 'unpaid'
                        ) {

                            throw new RuntimeException(
                                'Checkout expiry requires reconciliation.'
                            );
                        }

                        $cancellations->cancelExpiredCheckout(
                            $order,
                            $session->id
                        );

                        break;
                }

            }, 3);

        } catch (Throwable $exception) {

            report($exception);

            /*
             * Return a failure response so Stripe
             * can retry an unsuccessful event.
             */

            return response()->json([
                'error' =>
                    'Webhook processing failed.',
            ], 500);
        }

        return response()->json([
            'received' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Successful Payment
    |--------------------------------------------------------------------------
    */

    private function processSuccessfulPayment(
        Order $order,
        ?string $paymentIntent,
        TransactionalEmailService $emails,
        SellerLedgerService $ledger
    ): void {

        /*
         * Mark the order as paid.
         */

        $order->markPaid($paymentIntent);

        /*
         * Refresh the order after payment confirmation.
         */

        $order->refresh();

        /*
         * Load the associated business and seller.
         */

        $order->loadMissing('store.owner');

        /*
         * Resolve the customer name.
         */

        $customerName =
            $order->shipping_address['name']
            ?? 'Customer';

        /*
         * Customer payment confirmation.
         */

        $emails->queue(

            notificationKey:
                "order:{$order->id}:payment-confirmation",

            emailType:
                'payment_confirmation',

            recipientEmail:
                $order->email,

            subject:
                "Payment Confirmed {$order->number} | Made Exclusif",

            recipientName:
                $customerName,

            userId:
                $order->user_id,

            orderId:
                $order->id,

            storeId:
                $order->store_id
        );

        /*
         * Notify the seller only after payment
         * has been confirmed.
         */

        $seller = $order->store?->owner;

        if ($seller && $seller->email) {

            $emails->queue(

                notificationKey:
                    "order:{$order->id}:seller-notification",

                emailType:
                    'seller_new_order',

                recipientEmail:
                    $seller->email,

                subject:
                    "New Paid Order {$order->number} | Made Exclusif",

                recipientName:
                    $seller->name,

                userId:
                    $seller->id,

                orderId:
                    $order->id,

                storeId:
                    $order->store_id
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Failed Payment
    |--------------------------------------------------------------------------
    */

    private function processFailedPayment(
        Order $order,
        TransactionalEmailService $emails
    ): void {

        /*
         * Do not cancel or restore inventory
         * for paid or refunded orders.
         */

        if (in_array(
            $order->payment_status,
            ['paid', 'refunded'],
            true
        )) {
            return;
        }

        /*
         * Cancel the unpaid order and restore stock.
         */

        $changed = $order->cancelAndRestock();

        if (! $changed) {
            return;
        }

        /*
         * Refresh the order after cancellation.
         */

        $order->refresh();

        /*
         * Register the failed payment notification.
         */

        $emails->queue(

            notificationKey:
                "order:{$order->id}:payment-failed",

            emailType:
                'payment_failed',

            recipientEmail:
                $order->email,

            subject:
                "Payment Unsuccessful {$order->number} | Made Exclusif",

            recipientName:
                $order->shipping_address['name']
                    ?? 'Customer',

            userId:
                $order->user_id,

            orderId:
                $order->id,

            storeId:
                $order->store_id
        );
    }
}