<?php

namespace App\Services\Finance;

use App\Models\PlatformTransaction;
use App\Models\SubscriptionPayment;
use App\Models\PlatformInvoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FinancialReconciliationService
{
    public function overview(
        string $currency = 'gbp',
        bool $livemode = false
    ): array {

        $query = PlatformTransaction::query()
            ->where(
                'currency',
                strtolower($currency)
            )
            ->where('livemode', $livemode)
            ->where('status', 'completed');

        $total = (clone $query)->count();

        $reconciled = (clone $query)
            ->whereNotNull('reconciled_at')
            ->count();

        $unreconciled = (clone $query)
            ->whereNull('reconciled_at')
            ->count();

        $credits = (int) (clone $query)
            ->whereNull('reconciled_at')
            ->where('direction', 'credit')
            ->sum('amount_minor');

        $debits = (int) (clone $query)
            ->whereNull('reconciled_at')
            ->where('direction', 'debit')
            ->sum('amount_minor');

        return [

            'currency' => strtoupper($currency),

            'livemode' => $livemode,

            'total_transactions' => $total,

            'reconciled_transactions' => $reconciled,

            'unreconciled_transactions' => $unreconciled,

            'unreconciled_credits_minor' => $credits,

            'unreconciled_debits_minor' => $debits,

            'unreconciled_net_minor' =>
                $credits - $debits,

            'reconciliation_rate' => $total > 0
                ? round(($reconciled / $total) * 100, 2)
                : 0,
        ];
    }

    public function unreconciled(
        array $filters = []
    ): LengthAwarePaginator {

        return PlatformTransaction::query()
            ->where('status', 'completed')
            ->whereNull('reconciled_at')
            ->where(
                'currency',
                strtolower($filters['currency'] ?? 'gbp')
            )
            ->where(
                'livemode',
                filter_var(
                    $filters['livemode'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                )
            )
            ->oldest('occurred_at')
            ->paginate(
                max(
                    1,
                    min(
                        (int) ($filters['per_page'] ?? 25),
                        100
                    )
                )
            );
    }

    public function paymentDiscrepancies(
        string $currency = 'gbp',
        bool $livemode = false
    ): array {

        $discrepancies = [];

        SubscriptionPayment::query()
            ->where('currency', strtolower($currency))
            ->where('livemode', $livemode)
            ->whereIn('status', [
                'succeeded',
                'partially_refunded',
                'refunded',
                'disputed',
            ])
            ->chunkById(
                250,
                function ($payments) use (
                    &$discrepancies
                ) {

                    foreach ($payments as $payment) {

                        $transaction = PlatformTransaction::query()
                            ->where(
                                'idempotency_key',
                                'subscription-payment-'
                                . $payment->id
                            )
                            ->first();

                        if (!$transaction) {

                            $discrepancies[] = [

                                'type' =>
                                    'missing_ledger_entry',

                                'payment_id' =>
                                    $payment->id,

                                'reference' =>
                                    $payment->reference,

                                'amount_minor' =>
                                    $payment->amount_minor,
                            ];

                            continue;
                        }

                        if (
                            $transaction->amount_minor
                                !== $payment->amount_minor

                            || $transaction->currency
                                !== $payment->currency

                            || $transaction->livemode
                                !== $payment->livemode

                            || $transaction->direction
                                !== 'credit'

                            || $transaction->status
                                !== 'completed'

                            || $transaction->type
                                !== 'subscription_payment'

                            || $transaction->source_type
                                !== 'subscription_payment'

                            || (int) $transaction->source_id
                                !== (int) $payment->id

                            || (int) $transaction->store_id
                                !== (int) $payment->store_id
                        ) {

                            $discrepancies[] = [

                                'type' =>
                                    'ledger_payment_mismatch',

                                'payment_id' =>
                                    $payment->id,

                                'transaction_id' =>
                                    $transaction->id,

                                'reference' =>
                                    $payment->reference,

                                'payment_amount_minor' =>
                                    $payment->amount_minor,

                                'ledger_amount_minor' =>
                                    $transaction->amount_minor,
                            ];
                        }
                    }
                }
            );

        return $discrepancies;
    }

    public function invoiceDiscrepancies(
        string $currency = 'gbp',
        bool $livemode = false
    ): array {

        $discrepancies = [];

        PlatformInvoice::query()
            ->where('currency', strtolower($currency))
            ->where('livemode', $livemode)
            ->whereNotIn('status', [
                'draft',
                'void',
            ])
            ->chunkById(
                250,
                function ($invoices) use (
                    &$discrepancies
                ) {

                    foreach ($invoices as $invoice) {

                        $payments = (int) $invoice->payments()
                            ->whereIn('status', [
                                'succeeded',
                                'partially_refunded',
                                'refunded',
                                'disputed',
                            ])
                            ->sum('amount_minor');

                        $credits = (int) $invoice->creditNotes()
                            ->where('status', 'issued')
                            ->sum('amount_minor');

                        $expectedDue = max(
                            0,
                            $invoice->total_minor
                            - $payments
                            - $credits
                        );

                        if (
                            $expectedDue
                                !== $invoice->amount_due_minor

                            || $payments
                                !== $invoice->amount_paid_minor
                        ) {

                            $discrepancies[] = [

                                'type' =>
                                    'invoice_balance_mismatch',

                                'invoice_id' =>
                                    $invoice->id,

                                'invoice_number' =>
                                    $invoice->invoice_number,

                                'expected_due_minor' =>
                                    $expectedDue,

                                'recorded_due_minor' =>
                                    $invoice->amount_due_minor,

                                'expected_paid_minor' =>
                                    $payments,

                                'recorded_paid_minor' =>
                                    $invoice->amount_paid_minor,
                            ];
                        }
                    }
                }
            );

        return $discrepancies;
    }

    public function audit(
        string $currency = 'gbp',
        bool $livemode = false
    ): array {

        $paymentIssues = $this->paymentDiscrepancies(
            $currency,
            $livemode
        );

        $invoiceIssues = $this->invoiceDiscrepancies(
            $currency,
            $livemode
        );

        return [

            'currency' => strtoupper($currency),

            'livemode' => $livemode,

            'checked_at' => now()->toIso8601String(),

            'payment_discrepancies' => $paymentIssues,

            'invoice_discrepancies' => $invoiceIssues,

            'total_discrepancies' =>
                count($paymentIssues)
                + count($invoiceIssues),

            'status' =>
                empty($paymentIssues)
                && empty($invoiceIssues)
                    ? 'passed'
                    : 'review_required',
        ];
    }
}