<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessCustomerController extends Controller
{
    public function index(
        Request $request,
        BusinessAccessService $access
    ) {
        $store = $access->currentStore($request);

        return response()->json(
            DB::table('orders')
                ->join('users', 'users.id', '=', 'orders.user_id')
                ->where('orders.store_id', $store->id)
                ->select(
                    'users.id',
                    'users.name',
                    'orders.email'
                )
                ->selectRaw('COUNT(orders.id) as order_count')
                ->selectRaw(
                    'MAX(orders.created_at) as last_order_at'
                )
                ->groupBy(
                    'users.id',
                    'users.name',
                    'orders.email'
                )
                ->orderByDesc('last_order_at')
                ->paginate(30)
        );
    }

    public function show(
        Request $request,
        int $customer,
        BusinessAccessService $access
    ) {
        $store = $access->currentStore($request);

        $orders = DB::table('orders')
            ->where('store_id', $store->id)
            ->where('user_id', $customer);

        abort_unless((clone $orders)->exists(), 404);

        return response()->json(
            $orders->select([
                'number',
                'status',
                'payment_status',
                'total',
                'created_at',
            ])->latest()->paginate(30)
        );
    }
}
