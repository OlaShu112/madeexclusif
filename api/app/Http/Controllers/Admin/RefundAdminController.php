<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RefundAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => [
                'nullable',
                Rule::in(Refund::STATUSES),
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $refunds = Refund::query()
            ->with([
                'order:id,number,status,payment_status,total',
                'store:id,name,slug',
                'requestedBy:id,name',
            ])
            ->when(
                $data['status'] ?? null,
                fn ($query, $status) =>
                    $query->where('status', $status)
            )
            ->latest('id')
            ->paginate($data['per_page'] ?? 30);

        return response()->json($refunds);
    }

    public function show(Refund $refund): JsonResponse
    {
        return response()->json(
            $refund->load([
                'order:id,number,status,payment_status,total',
                'store:id,name,slug',
                'requestedBy:id,name',
            ])
        );
    }

    public function store(
        Request $request,
        Order $order,
        RefundService $refunds
    ): JsonResponse {

        $data = $request->validate([
            'reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $refund = $refunds->requestFullRefund(
            order: $order,
            adminId: $request->user()->id,
            reason: $data['reason']
        );

        return response()->json([
            'message' =>
                'Refund request registered. No money has been transferred.',
            'refund' => $refund,
        ], 201);
    }
}
