<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderFulfilmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderAdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Marketplace Orders
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): JsonResponse {

        $data = $request->validate([

            'status' => [
                'nullable',
                Rule::in(Order::STATUSES),
            ],

            'q' => [
                'nullable',
                'string',
                'max:255',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $orders = Order::query()

            ->with([
                'items',
                'store:id,name,slug',
            ])

            ->when(
                $data['status'] ?? null,
                fn ($query, $status) =>
                    $query->where('status', $status)
            )

            ->when(
                $data['q'] ?? null,
                fn ($query, $term) =>
                    $query->where(
                        fn ($search) =>
                            $search
                                ->where(
                                    'number',
                                    'like',
                                    "%{$term}%"
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$term}%"
                                )
                    )
            )

            ->latest('id')

            ->paginate(
                $data['per_page'] ?? 20
            );

        return response()->json($orders);
    }

    /*
    |--------------------------------------------------------------------------
    | View Order Details
    |--------------------------------------------------------------------------
    */

    public function show(
        Order $order
    ): JsonResponse {

        return response()->json(

            $order->load([

                'items',

                'user:id,name,email',

                'store:id,name,slug',

            ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Order Fulfilment
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Order $order,
        OrderFulfilmentService $fulfilment
    ): JsonResponse {

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

        /*
         * Payment status must not be manually changed.
         */

        if ($request->exists('payment_status')) {

            throw ValidationException::withMessages([
                'payment_status' =>
                    'Payment status must be managed through verified payment operations.',
            ]);
        }

        $updatedOrder = $fulfilment->update(

            order: $order,

            newStatus: $data['status'],

            trackingNumber:
                $data['tracking_number'] ?? null
        );

        return response()->json(

            $updatedOrder->load(
                'store:id,name,slug'
            )
        );
    }
}