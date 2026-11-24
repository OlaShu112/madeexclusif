<?php

namespace App\Services;

use App\Models\SellerAccount;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SellerAccountService
{
    public function getOrCreate(Store $store): SellerAccount
    {
        return DB::transaction(function () use ($store) {

            $lockedStore = Store::query()
                ->whereKey($store->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedStore->status !== 'active' ||
                $lockedStore->is_main
            ) {
                throw ValidationException::withMessages([
                    'store' =>
                        'This store is not eligible for an independent seller account.',
                ]);
            }

            return SellerAccount::firstOrCreate(
                [
                    'store_id' => $lockedStore->id,
                ],
                [
                    'currency' => config(
                        'financial.currency',
                        'gbp'
                    ),
                    'status' => 'pending',
                    'charges_enabled' => false,
                    'payouts_enabled' => false,
                    'details_submitted' => false,
                ]
            );

        }, 3);
    }
}
