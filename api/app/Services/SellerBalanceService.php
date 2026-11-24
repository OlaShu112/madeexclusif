<?php

namespace App\Services;

use App\Models\SellerAccount;

class SellerBalanceService
{
    public function calculate(SellerAccount $account): array
    {
        $transactions = $account->transactions()
            ->where('currency', $account->currency)
            ->where('status', 'posted');

        $credits = (int) (clone $transactions)
            ->where('direction', 'credit')
            ->sum('amount_minor');

        $debits = (int) (clone $transactions)
            ->where('direction', 'debit')
            ->sum('amount_minor');

        $pendingCredits = (int) (clone $transactions)
            ->where('direction', 'credit')
            ->where(function ($query) {
                $query
                    ->whereNull('available_at')
                    ->orWhere('available_at', '>', now());
            })
            ->sum('amount_minor');

        $reserved = (int) $account->payouts()
            ->where('currency', $account->currency)
            ->whereIn('status', [
                'requested',
                'approved',
                'processing',
                'transferred',
                'reconciliation_required',
            ])
            ->sum('amount_minor');

        $balance = $credits - $debits;

        return [
            'currency' => $account->currency,
            'total_credits_minor' => $credits,
            'total_debits_minor' => $debits,
            'balance_minor' => $balance,
            'pending_minor' => $pendingCredits,
            'reserved_minor' => $reserved,
            'available_minor' => max(
                0,
                $balance - $pendingCredits - $reserved
            ),
            'negative_balance_minor' => max(0, -$balance),
        ];
    }
}
