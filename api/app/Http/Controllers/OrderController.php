<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\StripeCheckout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class OrderController extends Controller
{
    private const FREE_UK_DELIVERY_THRESHOLD = 10000;
    private const UK_DELIVERY_PENCE = 595;
    private const MAX_QUANTITY_PER_VARIANT = 100;

    /**
     * Create an order and initiate Stripe Checkout.
     */
    public function store(
        Request $request,
        StripeCheckout $stripe
    ): JsonResponse {

        /*
         * 1. Validate customer, delivery and cart details.
         */
        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'shipping_address' => [
                'required',
                'array',
            ],

            'shipping_address.name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'shipping_address.full_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'shipping_address.phone_code' => [
                'required',
                'regex:/^\+[1-9]\d{0,3}$/',
            ],

            'shipping_address.phone' => [
                'required',
                'string',
                'regex:/^[0-9\s().-]{4,25}$/',
            ],

            'shipping_address.address' => [
                'required',
                'string',
                'max:255',
            ],

            'shipping_address.apartment' => [
                'nullable',
                'string',
                'max:255',
            ],

            'shipping_address.city' => [
                'required',
                'string',
                'max:255',
            ],

            'shipping_address.postcode' => [
                'nullable',
                'string',
                'max:30',
            ],

            'shipping_address.country' => [
                'required',
                'string',
                'max:100',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_variant_id' => [
                'required',
                'integer',
                'exists:product_variants,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            'coupon_code' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        /*
         * 2. Normalise the customer's full name.
         */
        $address = $data['shipping_address'];

        $customerName = trim((string) (
            ($address['name'] ?? null)
            ?: ($address['full_name'] ?? '')
        ));

        if ($customerName === '') {
            throw ValidationException::withMessages([
                'shipping_address.name' =>
                    'Please enter your full name.',
            ]);
        }

        $data['shipping_address']['name'] = $customerName;

        /*
         * 3. Normalise the international phone number.
         */
        $phoneCode = $address['phone_code'];

        $phone = preg_replace(
            '/\D+/',
            '',
            $address['phone']
        );

        $fullPhone = $phoneCode . $phone;

        if (! preg_match('/^\+[1-9]\d{6,14}$/', $fullPhone)) {
            throw ValidationException::withMessages([
                'shipping_address.phone' =>
                    'Please enter a valid international phone number.',
            ]);
        }

        $data['shipping_address']['phone_full'] = $fullPhone;

        /*
         * 4. Normalise the country.
         *
         * Customers enter their country manually.
         * We do not use a predefined country dropdown.
         */
        $country = trim($address['country']);

        if ($country === '') {
            throw ValidationException::withMessages([
                'shipping_address.country' =>
                    'Please enter your delivery country.',
            ]);
        }

        $data['shipping_address']['country'] = $country;

        /*
         * 5. Coupon validation.
         *
         * Discount calculations must be implemented
         * against the existing Coupon model before
         * enabling WELCOME10 or other promotions.
         */
        if (! empty($data['coupon_code'])) {
            throw ValidationException::withMessages([
                'coupon_code' =>
                    'Coupon checkout is not yet configured.',
            ]);
        }

        /*
         * 6. Resolve authenticated customer and store.
         */
        $user = $request->user();

        $store = DB::table('stores')
            ->orderBy('id')
            ->first();

        if (! $store) {
            throw ValidationException::withMessages([
                'store' =>
                    'The store is currently unavailable.',
            ]);
        }

        /*
         * 7. Combine duplicate product variants.
         */
        $wanted = [];

        foreach ($data['items'] as $item) {
            $variantId = (int) $item['product_variant_id'];
            $quantity = (int) $item['quantity'];

            $wanted[$variantId] =
                ($wanted[$variantId] ?? 0) + $quantity;
        }

        foreach ($wanted as $quantity) {
            if ($quantity > self::MAX_QUANTITY_PER_VARIANT) {
                throw ValidationException::withMessages([
                    'items' =>
                        'The maximum quantity per fragrance is 100.',
                ]);
            }
        }

        /*
         * 8. Validate shipping destination.
         *
         * Only UK shipping rates are configured.
         * Other countries require their own delivery
         * rates before payment can be accepted.
         */
        $ukCountries = [
            'united kingdom',
            'uk',
            'great britain',
            'england',
            'scotland',
            'wales',
            'northern ireland',
        ];

        $isUk = in_array(
            Str::lower($country),
            $ukCountries,
            true
        );

        if (! $isUk) {
            throw ValidationException::withMessages([
                'shipping_address.country' =>
                    'International delivery rates are not yet configured. Please contact us before placing an order.',
            ]);
        }

        /*
         * 9. Create the order and reserve inventory
         * inside a database transaction.
         */
        $order = DB::transaction(function () use (
            $wanted,
            $data,
            $user,
            $store
        ) {

            /*
             * Lock variants to prevent concurrent
             * customers purchasing the same stock.
             */
            $variants = ProductVariant::query()
                ->with('product')
                ->whereIn('id', array_keys($wanted))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotalPence = 0;
            $orderItems = [];

            foreach ($wanted as $variantId => $quantity) {

                $variant = $variants->get($variantId);

                if (! $variant || ! $variant->product) {
                    throw ValidationException::withMessages([
                        'items' =>
                            'One or more fragrances are unavailable.',
                    ]);
                }

                if ((int) $variant->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "Insufficient stock for {$variant->product->name}.",
                    ]);
                }

                /*
                 * Prices always come from the database.
                 */
                $unitPence = (int) round(
                    ((float) $variant->price) * 100
                );

                if ($unitPence <= 0) {
                    throw ValidationException::withMessages([
                        'items' =>
                            'A fragrance has an invalid price.',
                    ]);
                }

                $linePence = $unitPence * $quantity;
                $subtotalPence += $linePence;

                /*
                 * IMPORTANT:
                 * The order_items table uses line_total.
                 */
                $orderItems[] = [
                    'product_variant_id' => $variant->id,
                    'name' => $variant->product->name,
                    'size_ml' => $variant->size_ml,
                    'quantity' => $quantity,
                    'unit_price' => $unitPence / 100,
                    'line_total' => $linePence / 100,
                ];
            }

            /*
             * 10. Calculate UK delivery charges.
             */
            $shippingPence =
                $subtotalPence >= self::FREE_UK_DELIVERY_THRESHOLD
                    ? 0
                    : self::UK_DELIVERY_PENCE;

            $subtotal = $subtotalPence / 100;
            $shipping = $shippingPence / 100;
            $discount = 0.00;

            $total = (
                $subtotalPence + $shippingPence
            ) / 100;

            /*
             * 11. Create order.
             */
            $order = Order::create([
                'number' =>
                    'ME-' . strtoupper(Str::random(12)),

                'user_id' => $user?->id,
                'store_id' => $store->id,
                'email' => $data['email'],

                'status' => 'pending',

                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => $shipping,
                'total' => $total,

                'coupon_id' => null,

                'shipping_address' =>
                    $data['shipping_address'],
            ]);

            /*
             * 12. Create order items.
             */
            $order->items()->createMany($orderItems);

            /*
             * 13. Reserve stock.
             */
            foreach ($wanted as $variantId => $quantity) {
                $variants->get($variantId)
                    ->decrement('stock', $quantity);
            }

            return $order->load('items');

        }, 3);

        /*
         * 14. Create Stripe Checkout Session.
         */
        try {

            $session = $stripe->createSession($order);

            if (empty($session->id) || empty($session->url)) {
                throw new RuntimeException(
                    'Stripe returned an incomplete Checkout Session.'
                );
            }

        } catch (Throwable $exception) {

            report($exception);

            /*
             * A network error may occur after Stripe
             * creates the session.
             *
             * Therefore, do not automatically restore
             * inventory until the payment session has
             * been reconciled.
             */
            return response()->json([
                'message' =>
                    'Unable to start payment. Please try again later.',

                'order_number' => $order->number,
            ], 502);
        }

        /*
         * 15. Return Stripe Checkout URL to Vue.
         */
        return response()->json([
            'message' => 'Checkout session created.',

            'order_number' => $order->number,

            'checkout_url' => $session->url,

            'session_id' => $session->id,
        ], 201);
    }
}
