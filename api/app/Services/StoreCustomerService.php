
<?php

namespace App\Services;

use App\Models\Store;
use App\Models\StoreCustomer;
use App\Models\User;

class StoreCustomerService
{
    /**
     * Resolve an active business using its public slug.
     */
    public function resolveStore(string $slug): Store
    {
        return Store::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();
    }

    /**
     * Associate an authenticated user with a business.
     *
     * Existing associations are preserved.
     */
    public function associate(
        Store $store,
        User $user
    ): StoreCustomer {
        abort_unless(
            $store->status === 'active',
            404,
            'Business unavailable.'
        );

        $customer = StoreCustomer::query()->firstOrCreate(
            [
                'store_id' => $store->id,
                'user_id' => $user->id,
            ],
            [
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        return $customer;
    }

    /**
     * Retrieve a customer's business association.
     */
    public function find(
        Store $store,
        User $user
    ): ?StoreCustomer {
        return StoreCustomer::query()
            ->where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Determine whether a customer has an active
     * relationship with a business.
     */
    public function isActiveCustomer(
        Store $store,
        User $user
    ): bool {
        return StoreCustomer::query()
            ->where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Retrieve an active customer association
     * or reject the request.
     */
    public function requireActiveCustomer(
        Store $store,
        User $user
    ): StoreCustomer {
        $customer = $this->find($store, $user);

        abort_unless(
            $customer && $customer->isActive(),
            403,
            'Customer access is unavailable for this business.'
        );

        return $customer;
    }
}
