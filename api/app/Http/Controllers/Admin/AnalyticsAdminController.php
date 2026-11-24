<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\Models\Refund;
use App\Models\SellerPayout;
use App\Models\SellerTransaction;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'days' => ['nullable', 'integer', 'in:7,30,90,365'],
        ]);

        $days = (int) ($data['days'] ?? 30);

        $end = now()->startOfDay()->addDay();
        $start = $end->copy()->subDays($days);

        $paidOrders = Order::query()
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $end);

        $refunds = Refund::query()
            ->where('status', 'succeeded')
            ->where('succeeded_at', '>=', $start)
            ->where('succeeded_at', '<', $end);

        $ledger = SellerTransaction::query()
            ->where('status', 'posted')
            ->where('currency', 'gbp')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);

        $grossSales = (int) round(
            (float) (clone $paidOrders)->sum('total') * 100
        );

        $refundAmount = (int) (clone $refunds)
            ->where('currency', 'gbp')
            ->sum('amount_minor');

        $commission = (int) (clone $ledger)
            ->where('type', 'commission')
            ->sum('amount_minor');

        $commissionReversals = (int) (clone $ledger)
            ->where('type', 'commission_reversal')
            ->sum('amount_minor');

        $salesByDay = (clone $paidOrders)
            ->selectRaw('DATE(paid_at) as day, SUM(total) as total')
            ->groupByRaw('DATE(paid_at)')
            ->pluck('total', 'day');

        $customersByDay = User::query()
            ->where('role', 'customer')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'day');

        $businessesByDay = Store::query()
            ->where('is_main', false)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'day');

        $series = [];

        for (
            $date = $start->copy();
            $date->lt($end);
            $date->addDay()
        ) {
            $key = $date->format('Y-m-d');

            $series[] = [
                'date' => $key,
                'sales_minor' => (int) round(
                    (float) ($salesByDay[$key] ?? 0) * 100
                ),
                'customers' =>
                    (int) ($customersByDay[$key] ?? 0),
                'businesses' =>
                    (int) ($businessesByDay[$key] ?? 0),
            ];
        }

        return response()->json([

            'period' => [
                'days' => $days,
                'from' => $start->toDateString(),
                'to' => $end->copy()->subDay()->toDateString(),
            ],

            'summary' => [
                'gross_sales_minor' => $grossSales,
                'refunds_minor' => $refundAmount,
                'net_sales_minor' =>
                    $grossSales - $refundAmount,

                'commission_minor' =>
                    $commission - $commissionReversals,

                'paid_orders' => (clone $paidOrders)->count(),

                'customers' => User::where(
                    'role',
                    'customer'
                )->count(),

                'new_customers' => User::query()
                    ->where('role', 'customer')
                    ->where('created_at', '>=', $start)
                    ->where('created_at', '<', $end)
                    ->count(),

                'businesses' => Store::where(
                    'is_main',
                    false
                )->count(),

                'active_businesses' => Store::query()
                    ->where('is_main', false)
                    ->where('status', 'active')
                    ->count(),

                'pending_businesses' => Store::query()
                    ->where('is_main', false)
                    ->where('status', 'pending')
                    ->count(),

                'products' => Product::count(),

                'paid_payouts_minor' => (int)
                    SellerPayout::query()
                        ->where('status', 'paid')
                        ->where('currency', 'gbp')
                        ->where('paid_at', '>=', $start)
                        ->where('paid_at', '<', $end)
                        ->sum('amount_minor'),

                'failed_emails' => EmailLog::where(
                    'status',
                    'failed'
                )->count(),
            ],

            'series' => $series,

            'order_statuses' => Order::query()
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->get(),

            'recent_orders' => Order::query()
                ->with('store:id,name')
                ->latest('id')
                ->limit(10)
                ->get([
                    'id',
                    'number',
                    'store_id',
                    'status',
                    'payment_status',
                    'total',
                    'created_at',
                ]),

            'top_products' => DB::table('order_items')
                ->join(
                    'orders',
                    'orders.id',
                    '=',
                    'order_items.order_id'
                )
                ->where('orders.payment_status', 'paid')
                ->where('orders.paid_at', '>=', $start)
                ->where('orders.paid_at', '<', $end)
                ->selectRaw(
                    'order_items.name, SUM(order_items.quantity) as units'
                )
                ->groupBy('order_items.name')
                ->orderByDesc('units')
                ->limit(10)
                ->get(),
        ]);
    }
}
