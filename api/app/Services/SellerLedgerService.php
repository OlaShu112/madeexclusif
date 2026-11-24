<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Refund;
use App\Models\Store;
use App\Models\SellerAccount;
use App\Models\SellerTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SellerLedgerService
{
    public function __construct(
        private CommissionService $commissions
    ) {
    }

    public function recordSale(Order $order): void
    {
        DB::transaction(function () use ($order) {

            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($order->payment_status !== 'paid') {
                throw new RuntimeException(
                    'Only confirmed payments can enter the ledger.'
                );
            }

            $store = Store::query()
                ->lockForUpdate()
                ->findOrFail($order->store_id);

            if ($store->is_main) {
                return;
            }

            $account = SellerAccount::firstOrCreate(
                ['store_id' => $store->id],
                [
                    'currency' => 'gbp',
                    'status' => 'pending',
                ]
            );

            $account = SellerAccount::query()
                ->lockForUpdate()
                ->findOrFail($account->id);

            $saleReference = "order:{$order->id}:sale";

            if (
                SellerTransaction::where(
                    'reference',
                    $saleReference
                )->exists()
            ) {
                return;
            }

            $grossMinor = (int) round(
                (float) $order->total * 100
            );

            $calculation = $this->commissions->calculate(
                $store,
                $grossMinor
            );

            $availableAt = $order->paid_at
                ->copy()
                ->addDays(
                    config(
                        'financial.payouts.holding_period_days',
                        14
                    )
                );

            SellerTransaction::create([
                'seller_account_id' => $account->id,
                'order_id' => $order->id,
                'reference' => $saleReference,
                'type' => 'sale',
                'direction' => 'credit',
                'amount_minor' => $grossMinor,
                'currency' => $account->currency,
                'status' => 'posted',
                'description' =>
                    "Confirmed sale {$order->number}",
                'available_at' => $availableAt,
            ]);

            if ($calculation['commission_amount_minor'] > 0) {

                SellerTransaction::create([
                    'seller_account_id' => $account->id,
                    'order_id' => $order->id,
                    'reference' =>
                        "order:{$order->id}:commission",
                    'type' => 'commission',
                    'direction' => 'debit',
                    'amount_minor' =>
                        $calculation['commission_amount_minor'],
                    'currency' => $account->currency,
                    'status' => 'posted',
                    'description' =>
                        "Marketplace commission for {$order->number}",
                    'available_at' => $availableAt,
                ]);
            }

        }, 3);
    }

    public function recordRefund(Refund $refund): void
    {
        DB::transaction(function () use ($refund) {

            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($refund->order_id);

            $refund = Refund::query()
                ->lockForUpdate()
                ->findOrFail($refund->id);

            if (
                $refund->status !== 'succeeded' ||
                $refund->type !== 'full'
            ) {
                throw new RuntimeException(
                    'A confirmed full refund is required.'
                );
            }

            $store = Store::findOrFail($order->store_id);

            if ($store->is_main) {
                return;
            }

            $account = SellerAccount::query()
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->firstOrFail();

            $sale = SellerTransaction::query()
                ->where('seller_account_id', $account->id)
                ->where(
                    'reference',
                    "order:{$order->id}:sale"
                )
                ->firstOrFail();

            if (
                $sale->amount_minor !== $refund->amount_minor ||
                $sale->currency !== $refund->currency
            ) {
                throw new RuntimeException(
                    'Refund requires financial reconciliation.'
                );
            }

            SellerTransaction::firstOrCreate(
                [
                    'reference' =>
                        "refund:{$refund->id}:debit",
                ],
                [
                    'seller_account_id' => $account->id,
                    'order_id' => $order->id,
                    'refund_id' => $refund->id,
                    'type' => 'refund',
                    'direction' => 'debit',
                    'amount_minor' => $refund->amount_minor,
                    'currency' => $refund->currency,
                    'status' => 'posted',
                    'description' =>
                        "Refund for {$order->number}",
                    'available_at' => now(),
                ]
            );

            $commission = SellerTransaction::query()
                ->where('seller_account_id', $account->id)
                ->where(
                    'reference',
                    "order:{$order->id}:commission"
                )
                ->first();

            if ($commission) {

                SellerTransaction::firstOrCreate(
                    [
                        'reference' =>
                            "refund:{$refund->id}:commission-reversal",
                    ],
                    [
                        'seller_account_id' => $account->id,
                        'order_id' => $order->id,
                        'refund_id' => $refund->id,
                        'type' => 'commission_reversal',
                        'direction' => 'credit',
                        'amount_minor' =>
                            $commission->amount_minor,
                        'currency' => $refund->currency,
                        'status' => 'posted',
                        'description' =>
                            "Commission reversal for {$order->number}",
                        'available_at' => now(),
                    ]
                );
            }

        }, 3);
    }
}
