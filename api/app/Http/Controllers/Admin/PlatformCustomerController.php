<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformCustomerController extends Controller
{
    /**
     * Platform customer directory.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:255',
            'store_id' => 'nullable|integer|exists:stores,id',
            'verification' => 'nullable|in:verified,unverified',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = DB::table('users')
            ->where('users.role', 'customer')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                'users.email_verified_at',
                'users.created_at',
            ])
            ->selectSub(
                DB::table('store_customers')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'store_customers.user_id',
                        'users.id'
                    ),
                'businesses_count'
            )
            ->selectSub(
                DB::table('orders')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'orders.user_id',
                        'users.id'
                    ),
                'orders_count'
            );

        if (!empty($filters['q'])) {
            $search = '%' . $filters['q'] . '%';

            $query->where(function ($query) use ($search) {
                $query->where('users.name', 'like', $search)
                    ->orWhere('users.email', 'like', $search)
                    ->orWhere('users.phone', 'like', $search);
            });
        }

        if (!empty($filters['store_id'])) {
            $query->whereExists(function ($query) use ($filters) {
                $query->selectRaw('1')
                    ->from('store_customers')
                    ->whereColumn(
                        'store_customers.user_id',
                        'users.id'
                    )
                    ->where(
                        'store_customers.store_id',
                        $filters['store_id']
                    );
            });
        }

        if (($filters['verification'] ?? null) === 'verified') {
            $query->whereNotNull('users.email_verified_at');
        }

        if (($filters['verification'] ?? null) === 'unverified') {
            $query->whereNull('users.email_verified_at');
        }

        return response()->json([
            'statistics' => [
                'total_customers' => DB::table('users')
                    ->where('role', 'customer')
                    ->count(),

                'verified_customers' => DB::table('users')
                    ->where('role', 'customer')
                    ->whereNotNull('email_verified_at')
                    ->count(),

                'unverified_customers' => DB::table('users')
                    ->where('role', 'customer')
                    ->whereNull('email_verified_at')
                    ->count(),

                'business_memberships' => DB::table('store_customers')
                    ->join(
                        'users',
                        'users.id',
                        '=',
                        'store_customers.user_id'
                    )
                    ->where('users.role', 'customer')
                    ->count(),
            ],

            'stores' => DB::table('stores')
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),

            'customers' => $query
                ->orderByDesc('users.id')
                ->paginate($filters['per_page'] ?? 20),
        ]);
    }

    /**
     * Retrieve individual customer information.
     */
    public function show(int $customer)
    {
        $user = DB::table('users')
            ->where('id', $customer)
            ->where('role', 'customer')
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'email_verified_at',
                'created_at',
            ])
            ->first();

        abort_unless($user, 404, 'Customer not found.');

        $memberships = DB::table('store_customers')
            ->join(
                'stores',
                'stores.id',
                '=',
                'store_customers.store_id'
            )
            ->where('store_customers.user_id', $customer)
            ->select([
                'store_customers.id',
                'store_customers.store_id',
                'store_customers.status',
                'store_customers.joined_at',
                'stores.name as business_name',
            ])
            ->orderBy('stores.name')
            ->get();

        $orders = DB::table('orders')
            ->leftJoin(
                'stores',
                'stores.id',
                '=',
                'orders.store_id'
            )
            ->where('orders.user_id', $customer)
            ->select([
                'orders.id',
                'orders.number',
                'orders.status',
                'orders.payment_status',
                'orders.total',
                'orders.created_at',
                'stores.name as business_name',
            ])
            ->orderByDesc('orders.id')
            ->paginate(20);

        return response()->json([
            'customer' => $user,
            'memberships' => $memberships,
            'orders' => $orders,
        ]);
    }

    /**
     * Update a customer's membership status
     * within a particular business.
     */
    public function updateMembership(
        Request $request,
        int $customer,
        int $membership,
        AuditService $audit
    ) {
        $data = $request->validate([
            'status' => 'required|in:active,blocked',
            'reason' => 'required|string|min:5|max:500',
        ]);

        return DB::transaction(function () use (
            $request,
            $customer,
            $membership,
            $data,
            $audit
        ) {
            $user = DB::table('users')
                ->where('id', $customer)
                ->where('role', 'customer')
                ->first();

            abort_unless(
                $user,
                404,
                'Customer not found.'
            );

            $record = DB::table('store_customers')
                ->where('id', $membership)
                ->where('user_id', $customer)
                ->lockForUpdate()
                ->first();

            abort_unless(
                $record,
                404,
                'Business membership not found.'
            );

            if ($record->status === $data['status']) {
                return response()->json([
                    'message' => 'Membership status is unchanged.',
                ]);
            }

            DB::table('store_customers')
                ->where('id', $record->id)
                ->update([
                    'status' => $data['status'],
                    'updated_at' => now(),
                ]);

            $audit->record(
                action: 'customer.membership.updated',
                storeId: $record->store_id,
                userId: $request->user()->id,
                entityType: 'store_customer',
                entityId: $record->id,
                metadata: [
                    'customer_id' => $customer,
                    'previous_status' => $record->status,
                    'new_status' => $data['status'],
                    'reason' => $data['reason'],
                ]
            );

            return response()->json([
                'message' => 'Customer membership updated successfully.',
            ]);
        });
    }
}