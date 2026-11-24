<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderFulfilmentService
{
    public function __construct(
        private TransactionalEmailService $emails
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Permitted Order Status Transitions
    |--------------------------------------------------------------------------
    */

    private const TRANSITIONS = [
        'paid' => ['processing'],
        'processing' => ['shipped'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'pending' => [],
        'cancelled' => [],
        'refunded' => [],
    ];

    /*
    |--------------------------------------------------------------------------
    | Update Order Fulfilment
    |--------------------------------------------------------------------------
    */

    public function update(
        Order $order,
        string $newStatus,
        ?string $trackingNumber = null,
        ?int $storeId = null
    ): Order {

        return DB::transaction(function () use (
            $order,
            $newStatus,
            $trackingNumber,
            $storeId
        ) {

            $query = Order::query()
                ->whereKey($order->id);

            /*
             * Restrict seller access to their own store.
             */

            if ($storeId !== null) {
                $query->where('store_id', $storeId);
            }

            $lockedOrder = $query
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Only successfully paid orders
             * may proceed to fulfilment.
             */

            if ($lockedOrder->payment_status !== 'paid') {

                throw ValidationException::withMessages([
                    'status' =>
                        'Only paid orders can be fulfilled.',
                ]);
            }

            $currentStatus = $lockedOrder->status;
	    $hasOutstandingRefund = Refund::query()
    ->where('order_id', $lockedOrder->id)
    ->whereIn('status', [
        'requested',
        'submitting',
        'pending',
        'requires_action',
        'reconciliation_required',
        'succeeded',
    ])
    ->exists();

if ($hasOutstandingRefund) {

    throw ValidationException::withMessages([
        'status' =>
            'Fulfilment is blocked while this order has an outstanding or completed refund.',
    ]);
}

            /*
             * Validate requested status.
             */

            if (! in_array(
                $newStatus,
                ['processing', 'shipped', 'delivered'],
                true
            )) {

                throw ValidationException::withMessages([
                    'status' =>
                        'Invalid fulfilment status.',
                ]);
            }

            /*
             * Prevent invalid status transitions.
             */

            if ($currentStatus !== $newStatus) {

                $allowed = self::TRANSITIONS[
                    $currentStatus
                ] ?? [];

                if (! in_array(
                    $newStatus,
                    $allowed,
                    true
                )) {

                    throw ValidationException::withMessages([
                        'status' =>
                            "Cannot change order status from {$currentStatus} to {$newStatus}.",
                    ]);
                }
            }

            $attributes = [
                'status' => $newStatus,
            ];

            /*
             * Preserve existing tracking information
             * unless a new tracking number is provided.
             */

            if (
                $trackingNumber !== null &&
                trim($trackingNumber) !== ''
            ) {

                $attributes['tracking_number'] =
                    trim($trackingNumber);
            }

            $lockedOrder->update($attributes);

            /*
             * Register the appropriate customer email.
             *
             * Unique notification keys prevent duplicate
             * emails when the same status is submitted again.
             */

            $this->notifyCustomer(
                $lockedOrder,
                $newStatus
            );

            return $lockedOrder->load('items');

        }, 3);
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Notifications
    |--------------------------------------------------------------------------
    */

    private function notifyCustomer(
        Order $order,
        string $status
    ): void {

        $notification = match ($status) {

            'processing' => [
                'type' => 'order_processing',
                'subject' =>
                    "Your Order Is Being Prepared {$order->number} | Made Exclusif",
            ],

            'shipped' => [
                'type' => 'order_dispatched',
                'subject' =>
                    "Your Order Has Been Dispatched {$order->number} | Made Exclusif",
            ],

            'delivered' => [
                'type' => 'order_delivered',
                'subject' =>
                    "Your Order Has Been Delivered {$order->number} | Made Exclusif",
            ],

            default => null,
        };

        if ($notification === null) {
            return;
        }

        $customerName =
            $order->shipping_address['name']
            ?? $order->shipping_address['full_name']
            ?? $order->user?->name
            ?? 'Customer';

        $this->emails->queue(

            notificationKey:
                "order:{$order->id}:{$notification['type']}",

            emailType:
                $notification['type'],

            recipientEmail:
                $order->email,

            subject:
                $notification['subject'],

            recipientName:
                $customerName,

            userId:
                $order->user_id,

            orderId:
                $order->id,

            storeId:
                $order->store_id
        );
    }
}