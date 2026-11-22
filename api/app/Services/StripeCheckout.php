<?php

namespace App\Services;

use App\Models\Order;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

/**
 * Thin wrapper around Stripe Checkout (hosted payment page).
 * Card details never touch our server - Stripe collects them on its own page.
 */
class StripeCheckout
{
    private ?StripeClient $client = null;

    public function createSession(Order $order): Session
    {
        $order->loadMissing('items');
        $currency = config('stripe.currency', 'gbp');

        $lineItems = $order->items->map(fn ($item) => [
            'quantity' => $item->quantity,
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => $this->toMinorUnits($item->unit_price),
                'product_data' => ['name' => "{$item->name} - {$item->size_ml}ml"],
            ],
        ])->values()->all();

        $params = [
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $order->email,
            'client_reference_id' => $order->number,
            'metadata' => ['order_number' => $order->number],
            'payment_intent_data' => ['metadata' => ['order_number' => $order->number]],
            'shipping_options' => [[
                'shipping_rate_data' => [
                    'type' => 'fixed_amount',
                    'display_name' => (float) $order->shipping > 0 ? 'Standard delivery' : 'Free delivery',
                    'fixed_amount' => ['amount' => $this->toMinorUnits($order->shipping), 'currency' => $currency],
                ],
            ]],
            'success_url' => config('stripe.frontend_url') . '/order-success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('stripe.frontend_url') . '/cart?cancelled=1',
            // Stripe's minimum is 30 minutes; after expiry the webhook releases the reserved stock.
            'expires_at' => now()->addMinutes(31)->timestamp,
        ];

        if ((float) $order->discount > 0) {
            $coupon = $this->client()->coupons->create([
                'amount_off' => $this->toMinorUnits($order->discount),
                'currency' => $currency,
                'duration' => 'once',
                'name' => $order->coupon_code ?: 'Discount',
            ]);
            $params['discounts'] = [['coupon' => $coupon->id]];
        }

        return $this->client()->checkout->sessions->create($params);
    }

    public function retrieveSession(string $sessionId): Session
    {
        return $this->client()->checkout->sessions->retrieve($sessionId);
    }

    private function client(): StripeClient
    {
        if ($this->client) {
            return $this->client;
        }

        $secret = config('stripe.secret');
        if (! $secret) {
            throw new RuntimeException('STRIPE_SECRET is not set in .env');
        }

        return $this->client = new StripeClient($secret);
    }

    /** Stripe wants integer pence, never floats. */
    private function toMinorUnits(float|int|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}