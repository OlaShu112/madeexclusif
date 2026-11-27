<?php

namespace App\Services\Finance;

use App\Models\PlatformTransaction;
use App\Models\PlatformInvoice;
use App\Models\SubscriptionPayment;
use App\Models\PlatformExpense;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class FinanceDashboardService
{
    public function overview(
        string $currency = 'gbp',
        bool $livemode = false,
        ?string $from = null,
        ?string $to = null
    ): array {

        $currency = strtolower($currency);

        $start = $from
            ? CarbonImmutable::parse($from)->startOfDay()
            : CarbonImmutable::now()->startOfMonth();

        $end = $to
            ? CarbonImmutable::parse($to)->endOfDay()
            : CarbonImmutable::now()->endOfDay();

        if ($start->gt($end)) {
            throw new \InvalidArgumentException(
                'The start date must precede the end date.'
            );
        }

        $transactions = PlatformTransaction::query()
            ->where('currency', $currency)
            ->where('livemode', $livemode)
            ->where('status', 'completed')
            ->whereBetween('occurred_at', [
                $start,
                $end,
            ]);

        $subscriptionRevenue = (int) (clone $transactions)
            ->where('type', 'subscription_payment')
            ->where('direction', 'credit')
            ->sum('amount_minor');

        $commissionRevenue = (int) (clone $transactions)
            ->where('type', 'commission')
            ->where('direction', 'credit')
            ->sum('amount_minor');

        $otherRevenue = (int) (clone $transactions)
            ->whereIn('type', [
                'service_payment',
                'usage_payment',
                'other_income',
            ])
            ->where('direction', 'credit')
            ->sum('amount_minor');

        $refunds = (int) (clone $transactions)
            ->whereIn('type', [
                'subscription_refund',
                'commission_reversal',
            ])
            ->where('direction', 'debit')
            ->sum('amount_minor');

        $processingFees = (int) (clone $transactions)
            ->where('type', 'processing_fee')
            ->where('direction', 'debit')
            ->sum('amount_minor');

        $expenses = (int) (clone $transactions)
            ->where('type', 'expense')
            ->where('direction', 'debit')
            ->sum('amount_minor');

        $expenseReversals = (int) (clone $transactions)
            ->where('type', 'expense_reversal')
            ->where('direction', 'credit')
            ->sum('amount_minor');

        $grossRevenue = $subscriptionRevenue
            + $commissionRevenue
            + $otherRevenue;

        $netRevenue = $grossRevenue - $refunds;

        $netExpenses = $expenses - $expenseReversals;

        $totalCredits = (int) (clone $transactions)
            ->where('direction', 'credit')
            ->sum('amount_minor');

        $totalDebits = (int) (clone $transactions)
            ->where('direction', 'debit')
            ->sum('amount_minor');

        $invoices = PlatformInvoice::query()
            ->where('currency', $currency)
            ->where('livemode', $livemode);

        $payments = SubscriptionPayment::query()
            ->where('currency', $currency)
            ->where('livemode', $livemode);

        return [
            'currency' => strtoupper($currency),

            'livemode' => $livemode,

            'period' => [
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
            ],

            'revenue' => [
                'gross_revenue_minor' => $grossRevenue,

                'subscription_revenue_minor' =>
                    $subscriptionRevenue,

                'commission_revenue_minor' =>
                    $commissionRevenue,

                'other_revenue_minor' =>
                    $otherRevenue,

                'refunds_minor' => $refunds,

                'net_revenue_minor' => $netRevenue,
            ],

            'expenses' => [
                'operating_expenses_minor' =>
                    $netExpenses,

                'processing_fees_minor' =>
                    $processingFees,

                'total_expenses_minor' =>
                    $netExpenses + $processingFees,
            ],

            'summary' => [
                'total_credits_minor' => $totalCredits,

                'total_debits_minor' => $totalDebits,

                'net_cash_movement_minor' =>
                    $totalCredits - $totalDebits,

                'operating_cash_surplus_minor' =>
                    $netRevenue
                    - $netExpenses
                    - $processingFees,

                'transaction_count' =>
                    (clone $transactions)->count(),
            ],

            'invoices' => [
                'total' => (clone $invoices)->count(),

                'paid' => (clone $invoices)
                    ->where('status', 'paid')
                    ->count(),

                'outstanding' => (clone $invoices)
                    ->outstanding()
                    ->count(),

                'overdue' => (clone $invoices)
                    ->overdue()
                    ->count(),

                'outstanding_amount_minor' =>
                    (int) (clone $invoices)
                        ->outstanding()
                        ->sum('amount_due_minor'),
            ],

            'payments' => [
                'successful' => (clone $payments)
                    ->whereIn('status', [
                        'succeeded',
                        'partially_refunded',
                        'refunded',
                    ])
                    ->whereBetween('paid_at', [
                        $start,
                        $end,
                    ])
                    ->count(),

                'failed' => (clone $payments)
                    ->where('status', 'failed')
                    ->whereBetween('failed_at', [
                        $start,
                        $end,
                    ])
                    ->count(),

                'pending' => (clone $payments)
                    ->whereIn('status', [
                        'pending',
                        'processing',
                    ])
                    ->count(),
            ],

            'recent_transactions' =>
                (clone $transactions)
                    ->latest('occurred_at')
                    ->limit(10)
                    ->get(),

            'revenue_breakdown' => [
                [
                    'name' => 'Subscriptions',
                    'amount_minor' => $subscriptionRevenue,
                ],
                [
                    'name' => 'Commissions',
                    'amount_minor' => $commissionRevenue,
                ],
                [
                    'name' => 'Other Revenue',
                    'amount_minor' => $otherRevenue,
                ],
            ],
        ];
    }

    public function monthlyTrend(
        string $currency = 'gbp',
        bool $livemode = false,
        int $months = 12
    ): array {

        $months = max(1, min($months, 24));

        $start = CarbonImmutable::now()
            ->startOfMonth()
            ->subMonths($months - 1);

        $end = CarbonImmutable::now()->endOfMonth();

        $records = PlatformTransaction::query()
            ->where('currency', strtolower($currency))
            ->where('livemode', $livemode)
            ->where('status', 'completed')
            ->whereBetween('occurred_at', [
                $start,
                $end,
            ])
            ->selectRaw("
                DATE_FORMAT(occurred_at, '%Y-%m') as month,
                SUM(
                    CASE
                        WHEN direction = 'credit'
                        THEN amount_minor
                        ELSE 0
                    END
                ) as credits_minor,
                SUM(
                    CASE
                        WHEN direction = 'debit'
                        THEN amount_minor
                        ELSE 0
                    END
                ) as debits_minor
            ")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $results = [];

        for ($index = 0; $index < $months; $index++) {

            $date = $start->addMonths($index);

            $key = $date->format('Y-m');

            $record = $records->get($key);

            $credits = (int) (
                $record?->credits_minor ?? 0
            );

            $debits = (int) (
                $record?->debits_minor ?? 0
            );

            $results[] = [
                'month' => $key,
                'label' => $date->format('M Y'),
                'credits_minor' => $credits,
                'debits_minor' => $debits,
                'net_minor' => $credits - $debits,
            ];
        }

        return $results;
    }
}