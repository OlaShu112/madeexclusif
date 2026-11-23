<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreAdminController extends Controller
{
    /**
     * GET /api/admin/stores
     *
     * List all stores, prioritising pending applications.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => [
                'nullable',
                Rule::in(Store::STATUSES),
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $query = Store::query()
            ->with('owner:id,name,email')
            ->withCount('products');

        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        $stores = $query
            ->orderByRaw(
                "CASE status
                    WHEN 'pending' THEN 1
                    WHEN 'active' THEN 2
                    WHEN 'suspended' THEN 3
                    ELSE 4
                END"
            )
            ->orderByDesc('id')
            ->paginate($data['per_page'] ?? 30);

        return response()->json($stores);
    }

    /**
     * GET /api/admin/stores/{store}
     *
     * View an individual store and its products.
     */
    public function show(Store $store): JsonResponse
    {
        $store->load([
            'owner:id,name,email',
            'brand',
        ]);

        $store->loadCount([
            'products',
            'orders',
        ]);

        return response()->json($store);
    }

    /**
     * PATCH /api/admin/stores/{store}
     *
     * Approve, suspend or reactivate a seller store.
     */
    public function update(
        Request $request,
        Store $store
    ): JsonResponse {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(Store::STATUSES),
            ],
        ]);

        $store = DB::transaction(function () use (
            $store,
            $data
        ) {
            $lockedStore = Store::query()
                ->whereKey($store->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newStatus = $data['status'];

            if (
                $lockedStore->is_main &&
                $newStatus !== 'active'
            ) {
                throw ValidationException::withMessages([
                    'status' => 'The main Made Exclusif store must remain active.',
                ]);
            }

            $allowedTransitions = [
                'pending' => ['active', 'suspended'],
                'active' => ['suspended'],
                'suspended' => ['active'],
            ];

            $currentStatus = $lockedStore->status;

            if ($currentStatus === $newStatus) {
                return $lockedStore;
            }

            $allowed = $allowedTransitions[$currentStatus] ?? [];

            if (! in_array($newStatus, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot change store status from {$currentStatus} to {$newStatus}.",
                ]);
            }

            $lockedStore->update([
                'status' => $newStatus,
            ]);

            return $lockedStore;
        });

        return response()->json([
            'message' => match ($store->status) {
                'active' => 'Store activated successfully.',
                'suspended' => 'Store suspended successfully.',
                default => 'Store status updated successfully.',
            },
            'store' => $store->fresh([
                'owner:id,name,email',
                'brand',
            ]),
        ]);
    }
}

