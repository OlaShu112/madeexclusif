<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessAnalyticsController extends Controller
{
    public function index(
        Request $request,
        BusinessAccessService $access
    ) {
        $store = $access->currentStore($request);

        $data = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $days = $data['days'] ?? 30;
        $from = now()->subDays($days)->startOfDay();

        $orders = DB::table('orders')
            ->where('store_id', $store->id)
            ->where('created_at', '>=', $from);

        $paid = (clone $orders)
            ->where('payment_status', 'paid');

        $products = DB::table('products')
            ->where('store_id', $store->id);

        $inventory = DB::table('product_variants')
            ->join(
                'products',
                'products.id',
                '=',
                'product_variants.product_id'
            )
            ->where('products.store_id', $store->id);

        return response()->json([
            'period_days' => $days,
            'currency' => 'GBP',
            'total_orders' => (clone $orders)->count(),
            'paid_orders' => (clone $paid)->count(),
            'paid_order_value' => (clone $paid)->sum('total'),
            'average_order_value' => round(
                (float) (clone $paid)->avg('total'), 2
            ),
            'ordering_customers' => (clone $orders)
                ->distinct()->count('user_id'),
            'total_products' => (clone $products)->count(),
            'active_products' => (clone $products)
                ->where('is_active', true)->count(),
            'inventory_units' => (clone $inventory)
                ->sum('product_variants.stock'),
            'low_stock_variants' => (clone $inventory)
                ->whereColumn(
                    'product_variants.stock',
                    '<=',
                    'product_variants.low_stock_threshold'
                )->count(),
            'order_statuses' => (clone $orders)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')->get(),
            'daily_sales' => (clone $paid)
                ->selectRaw(
                    'DATE(created_at) as date, SUM(total) as total'
                )
                ->groupByRaw('DATE(created_at)')
                ->orderBy('date')->get(),
        ]);
    }
}
