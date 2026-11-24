<?php

namespace App\Services;

use App\Models\CommissionRule;
use App\Models\Store;
use InvalidArgumentException;

class CommissionService
{
    public function percentage(Store $store): string
    {
        if ($store->is_main) {
            return '0.00';
        }

        $rule = CommissionRule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($store) {
                $query->where('store_id', $store->id)
                    ->orWhereNull('store_id');
            })
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->orderByRaw(
                'CASE WHEN store_id IS NULL THEN 1 ELSE 0 END'
            )
            ->latest('id')
            ->first();

        return number_format(
            (float) (
                $rule?->percentage
                ?? config('financial.commissions.default_percentage', 10)
            ),
            2,
            '.',
            ''
        );
    }

    public function calculate(
        Store $store,
        int $amountMinor
    ): array {

        $percentage = $this->percentage($store);

        $basisPoints = (int) round(
            (float) $percentage * 100
        );

        if (
            $amountMinor < 0 ||
            $basisPoints < 0 ||
            $basisPoints > 10000
        ) {
            throw new InvalidArgumentException(
                'Invalid commission calculation.'
            );
        }

        $commissionMinor = intdiv(
            ($amountMinor * $basisPoints) + 5000,
            10000
        );

        return [
            'gross_amount_minor' => $amountMinor,
            'commission_percentage' => $percentage,
            'commission_amount_minor' => $commissionMinor,
            'seller_amount_minor' =>
                $amountMinor - $commissionMinor,
        ];
    }
}
