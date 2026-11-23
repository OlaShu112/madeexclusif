<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SellerOrderController extends Controller
{
    /**
     * GET /api/seller/orders
     *
     * List orders belonging to the authenticated seller's store.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->currentStore($request);

        $orders = Order::query()
            ->where('store_id', $store->id)
            ->with('items')
            ->latest('id')
            ->paginate(30);

        return response()->json($orders);
    }

    /**
     * GET /api/seller/orders/{order}
     *
     * View a single order belonging to the seller.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $store = $this->currentStore($request);

        abort_unless(
            (int) $order->store_id === (int) $store->id,
            404
        );

        return response()->json(
            $order->load('items')
        );
    }

    /**
     * PATCH /api/seller/orders/{order}
     *
     * Update fulfilment status for a paid order.
     */
    public function update(
        Request $request,
        Order $order
    ): JsonResponse {
        $store = $this->currentStore($request);

        abort_unless(
            (int) $order->store_id === (int) $store->id,
            404
        );

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'processing',
                    'shipped',
                    'delivered',
                ]),
            ],

            'tracking_number' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $order = DB::transaction(function () use (
            $order,
            $store,
            $data
        ) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->payment_status !== 'paid') {
                throw ValidationException::withMessages([
                    'status' => 'Only paid orders can be fulfilled.',
                ]);
            }

            $allowedTransitions = [
                'pending' => ['processing'],
                'paid' => ['processing'],
                'processing' => ['shipped'],
                'shipped' => ['delivered'],
                'delivered' => [],
                'cancelled' => [],
                'refunded' => [],
            ];

            $currentStatus = $lockedOrder->status;
            $newStatus = $data['status'];

            if ($currentStatus !== $newStatus) {
                $allowed = $allowedTransitions[$currentStatus] ?? [];

                if (! in_array($newStatus, $allowed, true)) {
                    throw ValidationException::withMessages([
                        'status' => "Cannot change order status from {$currentStatus} to {$newStatus}.",
                    ]);
                }
            }

            $attributes = [
                'status' => $newStatus,
            ];

            if (array_key_exists('tracking_number', $data)) {
                $attributes['tracking_number'] = $data['tracking_number'];
            }

            $lockedOrder->update($attributes);

            return $lockedOrder;
        });

        return response()->json([
            'message' => 'Order updated successfully.',
            'order' => $order->load('items'),
        ]);
    }

    /**
     * Retrieve the store provided by seller middleware.
     */
    private function currentStore(Request $request): Store
    {
        $store = $request->attributes->get('store');

        abort_unless($store instanceof Store, 403);

        abort_unless($store->status === 'active', 403);

        return $store;
    }
}

