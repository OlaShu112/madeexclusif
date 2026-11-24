<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Services\OrderFulfilmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SellerOrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Seller Orders
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): JsonResponse {

        $store = $this->currentStore($request);

        $data = $request->validate([

            'status' => [
                'nullable',
                Rule::in(Order::STATUSES),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $orders = Order::query()

            ->where('store_id', $store->id)

            ->with('items')

            ->when(
                $data['status'] ?? null,
                fn ($query, $status) =>
                    $query->where('status', $status)
            )

            ->latest('id')

            ->paginate(
                $data['per_page'] ?? 30
            );

        return response()->json($orders);
    }

    /*
    |--------------------------------------------------------------------------
    | View Seller Order
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Order $order
    ): JsonResponse {

        $store = $this->currentStore($request);

        abort_unless(

            (int) $order->store_id ===
            (int) $store->id,

            404
        );

        return response()->json(

            $order->load('items')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Seller Order
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Order $order,
        OrderFulfilmentService $fulfilment
    ): JsonResponse {

        $store = $this->currentStore($request);

        abort_unless(

            (int) $order->store_id ===
            (int) $store->id,

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

            'payment_status' => [
                'prohibited',
            ],
        ]);

        abort_if(
            $request->exists('payment_status'),
            422,
            'Payment status cannot be changed by a seller.'
        );

        $updatedOrder = $fulfilment->update(

            order: $order,

            newStatus: $data['status'],

            trackingNumber:
                $data['tracking_number'] ?? null,

            storeId:
                $store->id
        );

        return response()->json([

            'message' =>
                'Order updated successfully.',

            'order' =>
                $updatedOrder,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Authenticated Seller Store
    |--------------------------------------------------------------------------
    */

    private function currentStore(
        Request $request
    ): Store {

        $store = $request->attributes->get(
            'store'
        );

        abort_unless(
            $store instanceof Store,
            403
        );

        abort_unless(
            $store->status === 'active',
            403
        );

        abort_unless(

            (int) $store->user_id ===
            (int) $request->user()->id,

            403
        );

        return $store;
    }
}