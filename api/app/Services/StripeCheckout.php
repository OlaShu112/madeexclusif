<?php

namespace App\Services;

use App\Models\Order;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class StripeCheckout
{
    private ?StripeClient $client = null;

    /**
     * Create a hosted Stripe Checkout session.
     */
    public function createSession(
        Order $order
    ): Session {

        $order->refresh();

        /*
         * Reuse an existing Stripe Checkout session.
         */

        if ($order->stripe_session_id) {

            return $this->retrieveSession(
                $order->stripe_session_id
            );
        }

        if (
            $order->status !== 'pending' ||
            $order->payment_status !== 'unpaid'
        ) {
            throw new RuntimeException(
                'This order is not available for checkout.'
            );
        }

        $order->loadMissing('items');

        $currency = strtolower(
            config('stripe.currency', 'gbp')
        );

        /*
         * Build Stripe line items using product
         * names and marketplace variant labels.
         */

        $lineItems = $order->items->map(
            function ($item) use ($currency) {

                $variant = $item->display_variant;

                $name = $variant
                    ? "{$item->name} - {$variant}"
                    : $item->name;

                return [
                    'quantity' => $item->quantity,

                    'price_data' => [
                        'currency' => $currency,

                        'unit_amount' =>
                            $this->toMinorUnits(
                                $item->unit_price
                            ),

                        'product_data' => [
                            'name' => $name,
                        ],
                    ],
                ];
            }
        )->values()->all();

        $frontendUrl = rtrim(
            config('stripe.frontend_url'),
            '/'
        );

        $params = [
            'mode' => 'payment',

            'line_items' => $lineItems,

            'customer_email' => $order->email,

            'client_reference_id' => $order->number,

            'metadata' => [
                'order_number' => $order->number,
                'order_id' => (string) $order->id,
                'store_id' => (string) $order->store_id,
            ],

            'payment_intent_data' => [
                'metadata' => [
                    'order_number' => $order->number,
                    'order_id' => (string) $order->id,
                    'store_id' => (string) $order->store_id,
                ],
            ],

            'shipping_options' => [
                [
                    'shipping_rate_data' => [

                        'type' => 'fixed_amount',

                        'display_name' =>
                            (float) $order->shipping > 0
                                ? 'Standard delivery'
                                : 'Free delivery',

                        'fixed_amount' => [

                            'amount' =>
                                $this->toMinorUnits(
                                    $order->shipping
                                ),

                            'currency' => $currency,
                        ],
                    ],
                ],
            ],

            'success_url' =>
                $frontendUrl
                . '/order-success?session_id='
                . '{CHECKOUT_SESSION_ID}',

            'cancel_url' =>
                $frontendUrl
                . '/cart?cancelled=1',

            /*
             * Use a stable expiry time for this order
             * so repeated requests have identical
             * Stripe parameters.
             */

            'expires_at' =>
                $order->created_at
                    ->copy()
                    ->addMinutes(60)
                    ->timestamp,
        ];

        /*
         * Existing coupon functionality.
         */

        if ((float) $order->discount > 0) {

            $coupon = $this->client()
                ->coupons
                ->create(
                    [
                        'amount_off' =>
                            $this->toMinorUnits(
                                $order->discount
                            ),

                        'currency' => $currency,

                        'duration' => 'once',

                        'name' =>
                            $order->coupon_code
                            ?: 'Discount',
                    ],
                    [
                        'idempotency_key' =>
                            'made-exclusif-coupon-'
                            . $order->number,
                    ]
                );

            $params['discounts'] = [
                [
                    'coupon' => $coupon->id,
                ],
            ];
        }

        /*
         * Prevent duplicate Stripe sessions when
         * retrying checkout for the same order.
         */

        $session = $this->client()
            ->checkout
            ->sessions
            ->create(
                $params,
                [
                    'idempotency_key' =>
                        'made-exclusif-checkout-'
                        . $order->number,
                ]
            );

        if (empty($session->id)) {

            throw new RuntimeException(
                'Stripe returned an invalid checkout session.'
            );
        }

        /*
         * Persist the session identifier so the
         * webhook can locate the correct order.
         */

        $order->update([
            'stripe_session_id' => $session->id,
        ]);

        return $session;
    }

    /**
     * Retrieve a Stripe Checkout session.
     */
    public function retrieveSession(
        string $sessionId
    ): Session {

        return $this->client()
            ->checkout
            ->sessions
            ->retrieve($sessionId);
    }

    /**
     * Initialise the Stripe API client.
     */
    private function client(): StripeClient
    {
        if ($this->client) {
            return $this->client;
        }

        $secret = config('stripe.secret');

        if (! $secret) {

            throw new RuntimeException(
                'STRIPE_SECRET is not configured.'
            );
        }

        return $this->client =
            new StripeClient($secret);
    }

    /**
     * Convert monetary values to integer pence.
     */
    private function toMinorUnits(
        float|int|string $amount
    ): int {

        return (int) round(
            ((float) $amount) * 100
        );
    }
}