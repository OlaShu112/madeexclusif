<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\SellerAccountService;
use App\Services\SellerBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerFinanceController extends Controller
{
    public function show(
        Request $request,
        SellerAccountService $accounts,
        SellerBalanceService $balances
    ): JsonResponse {

        $store = $this->currentStore($request);

        $account = $accounts->getOrCreate($store);

        return response()->json([
            'account' => $account,
            'balance' => $balances->calculate($account),
            'financial_execution_enabled' => false,
        ]);
    }

    public function transactions(
        Request $request,
        SellerAccountService $accounts
    ): JsonResponse {

        $data = $request->validate([
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $account = $accounts->getOrCreate(
            $this->currentStore($request)
        );

        return response()->json(
            $account->transactions()
                ->with('order:id,number')
                ->latest('id')
                ->paginate($data['per_page'] ?? 30)
        );
    }

    public function payouts(
        Request $request,
        SellerAccountService $accounts
    ): JsonResponse {

        $account = $accounts->getOrCreate(
            $this->currentStore($request)
        );

        return response()->json(
            $account->payouts()
                ->latest('id')
                ->paginate(30)
        );
    }

    private function currentStore(Request $request): Store
    {
        return Store::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->where('is_main', false)
            ->firstOrFail();
    }
}
