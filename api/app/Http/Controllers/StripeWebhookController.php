<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('stripe.webhook_secret');
        if (! $secret) {
            return response()->json(['error' => 'Webhook secret not configured.'], 503);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret
            );
        } catch (UnexpectedValueException | SignatureVerificationException) {
            return response()->json(['error' => 'Invalid signature.'], 400);
        }

        $session = $event->data->object;
        $order = Order::where('stripe_session_id', $session->id ?? '')->first();

        if ($order) {
            switch ($event->type) {
                case 'checkout.session.completed':
                case 'checkout.session.async_payment_succeeded':
                    if (($session->payment_status ?? null) === 'paid') {
                        $order->markPaid($session->payment_intent ?? null);
                    }
                    break;

                case 'checkout.session.expired':
                case 'checkout.session.async_payment_failed':
                    $order->cancelAndRestock();
                    break;
            }
        }

        // Always 200 for verified events so Stripe doesn't keep retrying.
        return response()->json(['received' => true]);
    }
}