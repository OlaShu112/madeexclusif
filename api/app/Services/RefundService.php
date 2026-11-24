<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RefundService
{
    /**
     * Register a full refund request.
     *
     * This does not transfer money or issue
     * a Stripe refund.
     */
    public function requestFullRefund(
        Order $order,
        int $adminId,
        string $reason
    ): Refund {

        $reason = trim($reason);

        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages([
                'reason' => 'A valid refund reason is required.',
            ]);
        }

        return DB::transaction(function () use (
            $order,
            $adminId,
            $reason
        ) {

            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->payment_status !== 'paid') {
                throw ValidationException::withMessages([
                    'order' => 'Only paid orders can be refunded.',
                ]);
            }

            if (
                in_array(
                    $lockedOrder->status,
                    ['cancelled', 'refunded'],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'order' => 'This order is not eligible for a refund.',
                ]);
            }

            if (! $lockedOrder->stripe_payment_intent) {
                throw ValidationException::withMessages([
                    'order' => 'Payment reconciliation is required.',
                ]);
            }

            $existingRefund = Refund::query()
                ->where('order_id', $lockedOrder->id)
                ->whereNotIn('status', [
                    'failed',
                    'cancelled',
                ])
                ->first();

            if ($existingRefund) {
                throw ValidationException::withMessages([
                    'refund' =>
                        'A refund already exists for this order.',
                ]);
            }

            $amountMinor = (int) round(
                ((float) $lockedOrder->total) * 100
            );

            if ($amountMinor <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Invalid refund amount.',
                ]);
            }

            $reference = 'REF-' . Str::upper(
                (string) Str::ulid()
            );

            return Refund::create([
                'order_id' => $lockedOrder->id,
                'store_id' => $lockedOrder->store_id,
                'requested_by' => $adminId,

                'reference' => $reference,

                'idempotency_key' =>
                    'made-exclusif-' . $reference,

                'stripe_payment_intent' =>
                    $lockedOrder->stripe_payment_intent,

                'livemode' => str_starts_with(
                    (string) $lockedOrder->stripe_session_id,
                    'cs_live_'
                ),

                'amount_minor' => $amountMinor,

                'currency' => strtolower(
                    config('financial.currency', 'gbp')
                ),

                'type' => 'full',

                'status' => 'requested',

                'reason' => $reason,

                'requested_at' => now(),
            ]);

        }, 3);
    }
}
