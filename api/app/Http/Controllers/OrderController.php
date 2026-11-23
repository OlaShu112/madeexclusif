<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Store;
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

    /**
     * Return the authenticated customer's order history.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with('items')
            ->latest()
            ->paginate(10);

        return response()->json($orders);
    }

    /**
     * Return an order belonging to the authenticated customer.
     */
    public function show(Request $request, string $number): JsonResponse
    {
        $order = $request->user()
            ->orders()
            ->with('items')
            ->where('number', $number)
            ->firstOrFail();

        return response()->json($order);
    }

    /**
     * Create an order and initiate Stripe Checkout.
     *
     * Supports guest and authenticated customers.
     * Products must belong to one active store.
     */
    public function store(
        Request $request,
        StripeCheckout $stripe
    ): JsonResponse {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],

            'items' => [
                'required',
                'array',
                'min:1',
                'max:30',
            ],

            'items.*.variant_id' => [
                'required',
                'integer',
                'exists:product_variants,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:10',
            ],

            'coupon_code' => [
                'nullable',
                'string',
                'max:50',
            ],

            'shipping_address' => [
                'required',
                'array',
            ],

            'shipping_address.full_name' => [
                'required',
                'string',
                'max:120',
            ],

            'shipping_address.line1' => [
                'required',
                'string',
                'max:190',
            ],

            'shipping_address.line2' => [
                'nullable',
                'string',
                'max:190',
            ],

            'shipping_address.city' => [
                'required',
                'string',
                'max:100',
            ],

            'shipping_address.postcode' => [
                'required',
                'string',
                'max:20',
            ],

            'shipping_address.country' => [
                'required',
                'string',
                'size:2',
            ],
        ]);

        $user = $request->user('sanctum');

        $order = DB::transaction(function () use ($data, $user) {
            $wanted = collect($data['items'])
                ->groupBy('variant_id')
                ->map(
                    fn ($rows) => $rows->sum('quantity')
                );

            $variants = ProductVariant::with([
                'product:id,name,is_active,store_id',
            ])
                ->whereIn('id', $wanted->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($variants->count() !== $wanted->count()) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected variants are unavailable.',
                ]);
            }

            $storeIds = $variants
                ->map(fn ($variant) => $variant->product?->store_id)
                ->unique()
                ->values();

            if (
                $storeIds->count() !== 1 ||
                $storeIds->first() === null
            ) {
                throw ValidationException::withMessages([
                    'items' => 'Your bag can only contain products from one store.',
                ]);
            }

            $store = Store::active()
                ->find($storeIds->first());

            if (! $store) {
                throw ValidationException::withMessages([
                    'items' => 'This store is currently unavailable.',
                ]);
            }

            $subtotal = 0;
            $orderItems = [];

            foreach ($wanted as $variantId => $quantity) {
                $variant = $variants->get($variantId);

                if (
                    ! $variant ||
                    ! $variant->product ||
                    ! $variant->product->is_active
                ) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more products are unavailable.',
                    ]);
                }

                if ($variant->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$variant->product->name}.",
                    ]);
                }

                $unitPrice = (float) $variant->price;
                $lineTotal = round($unitPrice * $quantity, 2);

                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'name' => $variant->product->name,
                    'size_ml' => $variant->size_ml,
                    'variant_label' => $variant->label,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                ];
            }

            $subtotal = round($subtotal, 2);

            $discount = 0;
            $coupon = null;

            if (! empty($data['coupon_code'])) {
                $coupon = Coupon::query()
                    ->where('code', $data['coupon_code'])
                    ->first();

                if (! $coupon) {
                    throw ValidationException::withMessages([
                        'coupon_code' => 'Invalid coupon code.',
                    ]);
                }

                // Coupon eligibility and discount calculation
                // must be validated against your Coupon model.
                // Do not apply an unverified discount.
                throw ValidationException::withMessages([
                    'coupon_code' => 'Coupon checkout is not yet configured.',
                ]);
            }

            $shipping = $subtotal >= self::FREE_SHIPPING_OVER
                ? 0
                : self::SHIPPING_FLAT;

            $total = round(
                $subtotal - $discount + $shipping,
                2
            );

            $order = Order::create([
                'number' => 'ME-' . strtoupper(Str::random(12)),
                'user_id' => $user?->id,
                'store_id' => $store->id,
                'email' => $data['email'],
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => $shipping,
                'total' => $total,
                'coupon_id' => $coupon?->id,
                'shipping_address' => $data['shipping_address'],
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            // Reserve stock for the pending checkout.
            foreach ($wanted as $variantId => $quantity) {
                $variants->get($variantId)
                    ->decrement('stock', $quantity);
            }

            return $order->load('items');
        });

        /*
         * The Stripe service must create a Checkout Session
         * using the order's server-calculated amount.
         *
         * Your webhook must:
         * 1. Verify the Stripe webhook signature.
         * 2. Mark successfully paid orders as paid.
         * 3. Release reserved stock on failed or expired checkout.
         *
         * The service interface must be verified before enabling
         * this checkout in production.
         */
        $session = $stripe->createSession($order);

        return response()->json([
            'message' => 'Checkout session created.',
            'order_number' => $order->number,
            'checkout_url' => $session->url,
            'session_id' => $session->id,
        ], 201);
    }
}
