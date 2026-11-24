<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
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
     * Display the authenticated customer's orders.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()
                ->orders()
                ->with('items')
                ->latest()
                ->paginate(10)
        );
    }

    /**
     * Display an individual customer order.
     */
    public function show(
        Request $request,
        string $number
    ): JsonResponse {
        $order = $request->user()
            ->orders()
            ->with('items')
            ->where('number', $number)
            ->firstOrFail();

        return response()->json($order);
    }

    /**
     * Create a customer order.
     *
     * All product prices and stock quantities
     * are retrieved from the database.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => [
                'required',
                'email'
            ],

            'items' => [
                'required',
                'array',
                'min:1',
                'max:30'
            ],

            'items.*.variant_id' => [
                'required',
                'integer',
                'exists:product_variants,id'
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:10'
            ],

            'coupon_code' => [
                'nullable',
                'string',
                'max:50'
            ],

            'payment_method' => [
                'required',
                'in:card,paypal,cod'
            ],

            'shipping_address.full_name' => [
                'required',
                'string',
                'max:120'
            ],

            'shipping_address.line1' => [
                'required',
                'string',
                'max:190'
            ],

            'shipping_address.line2' => [
                'nullable',
                'string',
                'max:190'
            ],

            'shipping_address.city' => [
                'required',
                'string',
                'max:100'
            ],

            'shipping_address.postcode' => [
                'required',
                'string',
                'max:20'
            ],

            'shipping_address.country' => [
                'required',
                'string',
                'size:2'
            ],
        ]);

        $user = $request->user('sanctum');

        $order = DB::transaction(function () use ($data, $user) {

            /*
            |--------------------------------------------------------------------------
            | GROUP ORDER ITEMS
            |--------------------------------------------------------------------------
            */

            $wanted = collect($data['items'])
                ->groupBy('variant_id')
                ->map(function ($rows) {
                    return $rows->sum('quantity');
                });

            /*
            |--------------------------------------------------------------------------
            | RETRIEVE PRODUCT VARIANTS
            |--------------------------------------------------------------------------
            */

            $variants = ProductVariant::with(
                'product:id,name,is_active'
            )
                ->whereIn('id', $wanted->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0.00;
            $lines = [];

            /*
            |--------------------------------------------------------------------------
            | VALIDATE ITEMS AND CALCULATE TOTALS
            |--------------------------------------------------------------------------
            */

            foreach ($wanted as $variantId => $qty) {

                $variant = $variants->get($variantId);

                if (! $variant || ! $variant->product) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more products are unavailable.'
                    ]);
                }

                if (! $variant->product->is_active) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "{$variant->product->name} is no longer available."
                    ]);
                }

                $qty = (int) $qty;

                if ($qty > 10) {
                    throw ValidationException::withMessages([
                        'items' =>
                            'Maximum quantity per fragrance size is 10.'
                    ]);
                }

                if ($variant->stock < $qty) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "Only {$variant->stock} left of {$variant->product->name} ({$variant->size_ml}ml)."
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | CALCULATE LINE TOTAL
                |--------------------------------------------------------------------------
                */

                $unitPrice = round(
                    (float) $variant->price,
                    2
                );

                $lineTotal = round(
                    $unitPrice * $qty,
                    2
                );

                $subtotal = round(
                    $subtotal + $lineTotal,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | PREPARE ORDER ITEM
                |--------------------------------------------------------------------------
                */

                $lines[] = [
                    'product_variant_id' => $variant->id,
                    'name' => $variant->product->name,
                    'size_ml' => $variant->size_ml,
                    'unit_price' => $unitPrice,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                ];

                /*
                |--------------------------------------------------------------------------
                | RESERVE STOCK
                |--------------------------------------------------------------------------
                */

                $variant->decrement('stock', $qty);
            }

            /*
            |--------------------------------------------------------------------------
            | APPLY COUPON
            |--------------------------------------------------------------------------
            */

            $discount = 0.00;
            $couponCode = null;

            if (! empty($data['coupon_code'])) {

                $coupon = Coupon::where(
                    'code',
                    strtoupper(trim($data['coupon_code']))
                )
                    ->lockForUpdate()
                    ->first();

                if (! $coupon) {
                    throw ValidationException::withMessages([
                        'coupon_code' => 'Invalid coupon code.'
                    ]);
                }

                [$amount, $error] = $coupon->discountFor($subtotal);

                if ($error) {
                    throw ValidationException::withMessages([
                        'coupon_code' => $error
                    ]);
                }

                $discount = round(
                    (float) $amount,
                    2
                );

                $couponCode = $coupon->code;

                $coupon->increment('used_count');
            }

            /*
            |--------------------------------------------------------------------------
            | CALCULATE SHIPPING
            |--------------------------------------------------------------------------
            */

            $afterDiscount = max(
                round($subtotal - $discount, 2),
                0
            );

            $shipping = $afterDiscount >= self::FREE_SHIPPING_OVER
                ? 0.00
                : self::SHIPPING_FLAT;

            $total = round(
                $afterDiscount + $shipping,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | CREATE ORDER
            |--------------------------------------------------------------------------
            */

            $order = Order::create([
                'number' => 'MX-' . strtoupper(Str::random(8)),
                'user_id' => $user?->id,
                'email' => $data['email'],
                'status' => 'pending',
                'payment_method' => $data['payment_method'],
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => $shipping,
                'total' => $total,
                'coupon_code' => $couponCode,
                'shipping_address' => $data['shipping_address'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | SAVE ORDER ITEMS WITH LINE TOTAL
            |--------------------------------------------------------------------------
            */

            $order->items()->createMany($lines);

            return $order->load('items');
        });

        /*
        |--------------------------------------------------------------------------
        | PAYMENT INTEGRATION
        |--------------------------------------------------------------------------
        |
        | The order is currently pending.
        |
        | Stripe payment processing will be implemented
        | separately using a Stripe Checkout Session
        | and a verified webhook.
        |
        | Do not mark an order as paid until the payment
        | provider has confirmed successful payment.
        |
        */

        return response()->json($order, 201);
    }
}
