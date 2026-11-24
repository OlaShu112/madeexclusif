<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Store;
use App\Models\ProductVariant;
use App\Services\StripeCheckout;
use App\Services\TransactionalEmailService;
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

    /*
    |--------------------------------------------------------------------------
    | Create Order
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        StripeCheckout $stripe,
        TransactionalEmailService $emails
    ): JsonResponse {

        /*
         * 1. Validate customer and checkout information.
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
                'max:100',
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
         * 2. Normalise customer details.
         */

        $address = $data['shipping_address'];

        $customerName = trim(
            (string) (
                ($address['name'] ?? null)
                ?: ($address['full_name'] ?? '')
            )
        );

        if ($customerName === '') {

            throw ValidationException::withMessages([
                'shipping_address.name' =>
                    'Please enter your full name.',
            ]);
        }

        $address['name'] = $customerName;

        $address['full_name'] = $customerName;

        /*
         * 3. Normalise international telephone number.
         */

        $phoneCode = $address['phone_code'];

        $phone = preg_replace(
            '/\D+/',
            '',
            $address['phone']
        );

        if (
            $phoneCode === '+44' &&
            str_starts_with($phone, '0')
        ) {
            $phone = substr($phone, 1);
        }

        $fullPhone = $phoneCode . $phone;

        if (! preg_match(
            '/^\+[1-9]\d{6,14}$/',
            $fullPhone
        )) {

            throw ValidationException::withMessages([
                'shipping_address.phone' =>
                    'Please enter a valid international phone number.',
            ]);
        }

        $address['phone_full'] = $fullPhone;

        /*
         * 4. Validate delivery destination.
         */

        $country = trim($address['country']);

        $ukCountries = [
            'united kingdom',
            'uk',
            'gb',
            'great britain',
            'england',
            'scotland',
            'wales',
            'northern ireland',
        ];

        if (! in_array(
            Str::lower($country),
            $ukCountries,
            true
        )) {

            throw ValidationException::withMessages([
                'shipping_address.country' =>
                    'International delivery rates are not yet configured.',
            ]);
        }

        $address['country'] = $country;

        $data['shipping_address'] = $address;

        /*
         * 5. Coupon validation.
         *
         * Coupon checkout remains disabled until
         * discount validation is fully implemented.
         */

        if (! empty($data['coupon_code'])) {

            throw ValidationException::withMessages([
                'coupon_code' =>
                    'Coupon checkout is not yet configured.',
            ]);
        }

        /*
         * 6. Resolve authenticated customer.
         *
         * The checkout endpoint also supports guests.
         */

        $user = $request->user('sanctum');

        /*
         * 7. Combine duplicate product variants.
         */

        $wanted = [];

        foreach ($data['items'] as $item) {

            $variantId = (int) $item['product_variant_id'];

            $quantity = (int) $item['quantity'];

            $wanted[$variantId] =
                ($wanted[$variantId] ?? 0)
                + $quantity;
        }

        foreach ($wanted as $quantity) {

            if (
                $quantity >
                self::MAX_QUANTITY_PER_VARIANT
            ) {

                throw ValidationException::withMessages([
                    'items' =>
                        'The maximum quantity per product variant is 100.',
                ]);
            }
        }

        /*
         * 8. Create order and reserve inventory.
         */

        $order = DB::transaction(function () use (
            $wanted,
            $data,
            $user
        ) {

            $variants = ProductVariant::query()
                ->with('product')
                ->whereIn('id', array_keys($wanted))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotalPence = 0;

            $orderItems = [];

            $storeIds = [];

            /*
             * 9. Validate products and identify sellers.
             */

            foreach ($wanted as $variantId => $quantity) {

                $variant = $variants->get($variantId);

                if (
                    ! $variant ||
                    ! $variant->product ||
                    ! $variant->product->is_active
                ) {

                    throw ValidationException::withMessages([
                        'items' =>
                            'One or more products are unavailable.',
                    ]);
                }

                $product = $variant->product;

                if (! $product->store_id) {

                    throw ValidationException::withMessages([
                        'items' =>
                            'A selected product has no valid store.',
                    ]);
                }

                $storeIds[] = (int) $product->store_id;

                if ((int) $variant->stock < $quantity) {

                    throw ValidationException::withMessages([
                        'items' =>
                            "Insufficient stock for {$product->name}.",
                    ]);
                }

                $unitPence = (int) round(
                    ((float) $variant->price) * 100
                );

                if ($unitPence <= 0) {

                    throw ValidationException::withMessages([
                        'items' =>
                            'A selected product has an invalid price.',
                    ]);
                }

                $linePence = $unitPence * $quantity;

                $subtotalPence += $linePence;

                $orderItems[] = [

                    'product_variant_id' =>
                        $variant->id,

                    'name' => $product->name,

                    'size_ml' => $variant->size_ml,

                    'variant_label' => $variant->label,

                    'quantity' => $quantity,

                    'unit_price' => $unitPence / 100,

                    'line_total' => $linePence / 100,
                ];
            }

            /*
             * 10. Enforce single-store checkout.
             */

            $storeIds = array_values(
                array_unique($storeIds)
            );

            if (count($storeIds) !== 1) {

                throw ValidationException::withMessages([
                    'items' =>
                        'Please complete a separate checkout for each store.',
                ]);
            }

            $store = Store::query()
                ->whereKey($storeIds[0])
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $store) {

                throw ValidationException::withMessages([
                    'store' =>
                        'The selected store is currently unavailable.',
                ]);
            }

            /*
             * 11. Calculate delivery charges.
             */

            $shippingPence =
                $subtotalPence >=
                self::FREE_UK_DELIVERY_THRESHOLD
                    ? 0
                    : self::UK_DELIVERY_PENCE;

            $totalPence =
                $subtotalPence + $shippingPence;

            /*
             * 12. Create the order.
             */

            $order = Order::create([

                'number' =>
                    'ME-' . strtoupper(Str::random(12)),

                'user_id' => $user?->id,

                'store_id' => $store->id,

                'email' => $data['email'],

                'status' => 'pending',

                'payment_method' => 'card',

                'payment_status' => 'unpaid',

                'subtotal' => $subtotalPence / 100,

                'discount' => 0,

                'shipping' => $shippingPence / 100,

                'total' => $totalPence / 100,

                'coupon_code' => null,

                'shipping_address' =>
                    $data['shipping_address'],
            ]);

            /*
             * 13. Save order item snapshots.
             */

            $order->items()->createMany(
                $orderItems
            );

            /*
             * 14. Reserve stock.
             */

            foreach ($wanted as $variantId => $quantity) {

                $variants->get($variantId)
                    ->decrement('stock', $quantity);
            }

            return $order->load([
                'items',
                'store',
            ]);

        }, 3);

        /*
         * 15. Create Stripe Checkout session.
         */

        try {

            $session = $stripe->createSession($order);

            if (
                empty($session->id) ||
                empty($session->url)
            ) {

                throw new RuntimeException(
                    'Stripe returned an incomplete checkout session.'
                );
            }

        } catch (Throwable $exception) {

            report($exception);

            /*
             * Do not restore inventory automatically.
             * Stripe may have created the session
             * before a network failure occurred.
             */

            return response()->json([

                'message' =>
                    'Unable to start payment. Please try again later.',

                'order_number' => $order->number,

            ], 502);
        }

        /*
         * 16. Queue order confirmation email.
         *
         * Email failure must not prevent the customer
         * from proceeding to Stripe Checkout.
         */

        try {

            $emails->queue(

                notificationKey:
                    "order:{$order->id}:confirmation",

                emailType:
                    'order_confirmation',

                recipientEmail:
                    $order->email,

                subject:
                    "Order Received {$order->number} | Made Exclusif",

                recipientName:
                    $customerName,

                userId:
                    $order->user_id,

                orderId:
                    $order->id,

                storeId:
                    $order->store_id
            );

        } catch (Throwable $exception) {

            report($exception);
        }

        /*
         * 17. Return Stripe Checkout information.
         */

        return response()->json([

            'message' =>
                'Checkout session created.',

            'order_number' =>
                $order->number,

            'checkout_url' =>
                $session->url,

            'session_id' =>
                $session->id,

        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Confirm Checkout
    |--------------------------------------------------------------------------
    */

    public function confirm(
        Request $request,
        StripeCheckout $stripe
    ): JsonResponse {

        $data = $request->validate([

            'session_id' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $order = Order::query()
            ->where(
                'stripe_session_id',
                $data['session_id']
            )
            ->firstOrFail();

        try {

            $session = $stripe->retrieveSession(
                $data['session_id']
            );

        } catch (Throwable $exception) {

            report($exception);

            return response()->json([
                'message' =>
                    'Unable to confirm payment at this time.',
            ], 502);
        }

        $expectedAmount = (int) round(
            ((float) $order->total) * 100
        );

        if (
            $session->client_reference_id !==
                $order->number ||

            (int) $session->amount_total !==
                $expectedAmount ||

            strtolower((string) $session->currency) !==
                strtolower(config('stripe.currency', 'gbp'))
        ) {

            return response()->json([
                'message' =>
                    'Payment verification failed.',
            ], 409);
        }

        /*
         * The signed Stripe webhook is responsible
         * for changing the database payment status.
         */

        $order->refresh();

        return response()->json([

            'message' =>
                $order->payment_status === 'paid'
                    ? 'Payment confirmed.'
                    : 'Payment confirmation is being processed.',

            'order_number' => $order->number,

            'status' => $order->status,

            'payment_status' => $order->payment_status,

            'order' => [
                'number' => $order->number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total' => $order->total,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Order History
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): JsonResponse {

        $orders = Order::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->with('items')
            ->latest('id')
            ->paginate(20);

        return response()->json($orders);
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Order Details
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        string $number
    ): JsonResponse {

        $order = Order::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->where('number', $number)
            ->with('items')
            ->firstOrFail();

        return response()->json($order);
    }
}