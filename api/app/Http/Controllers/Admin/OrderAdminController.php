<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with('items')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('number', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json($order->load('items', 'user:id,name,email'));
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['nullable', Rule::in(['unpaid', 'paid', 'refunded'])],
        ]);

        $order->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json($order->fresh('items'));
    }
}
