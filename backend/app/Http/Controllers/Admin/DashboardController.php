<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $counted = ['paid', 'processing', 'shipped', 'delivered'];

        return response()->json([
            'revenue' => (float) Order::whereIn('status', $counted)->sum('total'),
            'orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'customers' => User::where('role', 'customer')->count(),
            'products' => Product::count(),
            'low_stock' => ProductVariant::with('product:id,name')->where('stock', '<=', 5)->orderBy('stock')->limit(10)->get(),
            'recent_orders' => Order::latest()->limit(8)->get(['id', 'number', 'email', 'status', 'total', 'created_at']),
            'top_products' => DB::table('order_items')
                ->select('name', DB::raw('SUM(quantity) as units'))
                ->groupBy('name')->orderByDesc('units')->limit(5)->get(),
        ]);
    }
}
