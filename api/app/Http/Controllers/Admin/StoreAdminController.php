<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\StoreNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreAdminController extends Controller
{
    /**
     * List all marketplace stores.
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
            ->with([
                'owner:id,name,email',
                'businessCategory:id,name,slug',
            ])
            ->withCount('products');

        if (! empty($data['status'])) {

            $query->where(
                'status',
                $data['status']
            );
        }

        $stores = $query
            ->orderByRaw("
                CASE status
                    WHEN 'pending' THEN 1
                    WHEN 'active' THEN 2
                    WHEN 'suspended' THEN 3
                    WHEN 'rejected' THEN 4
                    ELSE 5
                END
            ")
            ->orderByDesc('id')
            ->paginate($data['per_page'] ?? 30);

        return response()->json($stores);
    }

    /**
     * Retrieve an individual business.
     */
    public function show(Store $store): JsonResponse
    {
        $store->load([
            'owner:id,name,email',
            'brand',
            'businessCategory',
        ]);

        $store->loadCount([
            'products',
            'orders',
        ]);

        return response()->json($store);
    }

    /**
     * Approve, reject, suspend or reactivate a store.
     */
    public function update(
        Request $request,
        Store $store,
        StoreNotificationService $notifications
    ): JsonResponse {

        $data = $request->validate([

            'status' => [
                'required',
                Rule::in(Store::STATUSES),
            ],

            'reason' => [
                'required_if:status,rejected,suspended',
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $adminId = $request->user()->id;

        $store = DB::transaction(function () use (
            $store,
            $data,
            $adminId,
            $notifications
        ) {

            $lockedStore = Store::query()
                ->whereKey($store->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentStatus = $lockedStore->status;

            $newStatus = $data['status'];

            if (
                $lockedStore->is_main &&
                $newStatus !== 'active'
            ) {

                throw ValidationException::withMessages([
                    'status' =>
                        'The main Made Exclusif store must remain active.',
                ]);
            }

            if ($currentStatus === $newStatus) {
                return $lockedStore;
            }

            $allowedTransitions = [

                'pending' => [
                    'active',
                    'rejected',
                ],

                'active' => [
                    'suspended',
                ],

                'suspended' => [
                    'active',
                ],

                'rejected' => [
                    'pending',
                ],
            ];

            if (! in_array(
                $newStatus,
                $allowedTransitions[$currentStatus] ?? [],
                true
            )) {

                throw ValidationException::withMessages([
                    'status' =>
                        "Cannot change store status from {$currentStatus} to {$newStatus}.",
                ]);
            }

            $firstApproval =
                $currentStatus === 'pending' &&
                $newStatus === 'active' &&
                $lockedStore->approved_at === null;

            $updates = [
                'status' => $newStatus,

                'status_version' =>
                    $lockedStore->status_version + 1,
            ];

            switch ($newStatus) {

                case 'active':

                    if ($firstApproval) {

                        $updates['approved_at'] = now();

                        $updates['approved_by'] = $adminId;
                    }

                    break;

                case 'rejected':

                    $updates['rejected_at'] = now();

                    $updates['rejected_by'] = $adminId;

                    $updates['rejection_reason'] =
                        trim($data['reason']);

                    break;

                case 'suspended':

                    $updates['suspended_at'] = now();

                    $updates['suspended_by'] = $adminId;

                    $updates['suspension_reason'] =
                        trim($data['reason']);

                    break;
            }

            $lockedStore->update($updates);

            match ($newStatus) {

                'active' =>
                    $notifications->approved(
                        $lockedStore,
                        $firstApproval
                    ),

                'rejected' =>
                    $notifications->rejected($lockedStore),

                'suspended' =>
                    $notifications->suspended($lockedStore),

                default => null,
            };

            return $lockedStore;

        }, 3);

        return response()->json([

            'message' => match ($store->status) {

                'active' =>
                    'Store activated successfully.',

                'rejected' =>
                    'Business application rejected.',

                'suspended' =>
                    'Store suspended successfully.',

                'pending' =>
                    'Business application returned for review.',

                default =>
                    'Store status updated successfully.',
            },

            'store' => $store->fresh([
                'owner:id,name,email',
                'brand',
                'businessCategory',
            ]),
        ]);
    }
}