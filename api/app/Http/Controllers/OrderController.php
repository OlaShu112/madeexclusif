<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\StripeCheckout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    private const FREE_SHIPPING_OVER = 100.00;
    private const SHIPPING_FLAT = 5.95;

    /** Customer's own order history. */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->orders()->with('items')->latest()->paginate(10)
        );
    }

    public function show(Request $request, string $number): JsonResponse
    {
        $order = $request->user()->orders()->with('items')->where('number', $number)->firstOrFail();

        return response()->json($order);
    }

    /**
     * Guest or authenticated checkout.
     *
     * 1. Prices, stock and totals are recomputed server-side (the client only sends ids + quantities).
     * 2. Stock is reserved inside a transaction with row locks.
     * 3. A Stripe Checkout Session is created and its URL returned; the customer pays on Stripe's page.
     * 4. The order becomes "paid" via Stripe's webhook (or the confirm endpoint after redirect).
     */
    public function store(Request $request, StripeCheckout $stripe): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'shipping_address.full_name' => ['required', 'string', 'max:120'],
            'shipping_address.line1' => ['required', 'string', 'max:190'],
            'shipping_address.line2' => ['nullable', 'string', 'max:190'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.postcode' => ['required', 'string', 'max:20'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
        ]);

        $user = $request->user('sanctum'); // optional auth: works for guests too

        $order = DB::transaction(function () use ($data, $user) {
            $wanted = collect($data['items'])
                ->groupBy('variant_id')
                ->map(fn ($rows) => $rows->sum('quantity'));

            $variants = ProductVariant::with('product:id,name,is_active')
                ->whereIn('id', $wanted->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0.0;
            $lines = [];

            foreach ($wanted as $variantId => $qty) {
                $variant = $variants[$variantId];

                if (! $variant->product->is_active) {
                    throw ValidationException::withMessages(['items' => "{$variant->product->name} is no longer available."]);
                }
                if ($variant->stock < $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Only {$variant->stock} left of {$variant->product->name} ({$variant->size_ml}ml).",
                    ]);
                }

                $line = round((float) $variant->price * $qty, 2);
                $subtotal += $line;
                $lines[] = [
                    'product_variant_id' => $variant->id,
                    'name' => $variant->product->name,
                    'size_ml' => $variant->size_ml,
                    'unit_price' => $variant->price,
                    'quantity' => $qty,
                    'line_total' => $line,
                ];
                $variant->decrement('stock', $qty);
            }

            $discount = 0.0;
            $couponCode = null;
            if (! empty($data['coupon_code'])) {
                $coupon = Coupon::where('code', strtoupper(trim($data['coupon_code'])))->lockForUpdate()->first();
                if (! $coupon) {
                    throw ValidationException::withMessages(['coupon_code' => 'Invalid code.']);
                }
                [$amount, $error] = $coupon->discountFor($subtotal);
                if ($error) {
                    throw ValidationException::withMessages(['coupon_code' => $error]);
                }
                $discount = $amount;
                $couponCode = $coupon->code;
                $coupon->increment('used_count');
            }

            $afterDiscount = max($subtotal - $discount, 0);
            $shipping = $afterDiscount >= self::FREE_SHIPPING_OVER ? 0.0 : self::SHIPPING_FLAT;

            $order = Order::create([
                'number' => 'MX-' . strtoupper(Str::random(8)),
                'user_id' => $user?->id,
                'email' => $data['email'],
                'status' => 'pending',
                'payment_method' => 'card',
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => $shipping,
                'total' => round($afterDiscount + $shipping, 2),
                'coupon_code' => $couponCode,
                'shipping_address' => $data['shipping_address'],
            ]);

            $order->items()->createMany($lines);

            return $order->load('items');
        });

        try {
            $session = $stripe->createSession($order);
            $order->update(['stripe_session_id' => $session->id]);
        } catch (\Throwable $e) {
            report($e);
            $order->cancelAndRestock(); // give the reserved stock back

            return response()->json(['message' => 'We could not start the payment. Please try again in a moment.'], 502);
        }

        return response()->json(['order' => $order, 'checkout_url' => $session->url], 201);
    }

    /**
     * Called by the success page after Stripe redirects back. Confirms payment directly with Stripe,
     * so it works even if the webhook is delayed (or not configured on a local machine).
     */
    public function confirm(Request $request, StripeCheckout $stripe): JsonResponse
    {
        $data = $request->validate(['session_id' => ['required', 'string', 'max:255']]);

        $order = Order::with('items')->where('stripe_session_id', $data['session_id'])->firstOrFail();

        if ($order->payment_status !== 'paid') {
            $session = $stripe->retrieveSession($data['session_id']);
            if (($session->payment_status ?? null) === 'paid') {
                $order->markPaid($session->payment_intent ?? null);
            }
        }

        return response()->json($order->fresh('items'));
    }
}