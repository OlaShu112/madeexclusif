<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Services\StoreCustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(
        Request $request,
        StoreCustomerService $customers
    ): JsonResponse {

        $store = $this->resolveStore(
            $request,
            $customers
        );

        $products = Product::active()

            ->where('store_id', $store->id)

            ->whereIn(
                'id',
                $request->user()
                    ->wishlist()
                    ->pluck('product_id')
            )

            ->with([
                'brand:id,name',
                'store',
            ])

            ->withMin(
                'variants as min_price',
                'price'
            )

            ->get();

        return response()->json($products);
    }

    public function toggle(
        Request $request,
        int $productId,
        StoreCustomerService $customers
    ): JsonResponse {

        $store = $this->resolveStore(
            $request,
            $customers
        );

        Product::active()
            ->where('store_id', $store->id)
            ->findOrFail($productId);

        $existing = $request->user()
            ->wishlist()
            ->where('product_id', $productId)
            ->first();

        if ($existing) {

            $existing->delete();

            return response()->json([
                'wishlisted' => false,
            ]);
        }

        $request->user()
            ->wishlist()
            ->firstOrCreate([
                'product_id' => $productId,
            ]);

        return response()->json([
            'wishlisted' => true,
        ]);
    }

    private function resolveStore(
        Request $request,
        StoreCustomerService $customers
    ): Store {

        $data = $request->validate([
            'store' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $store = ! empty($data['store'])
            ? $customers->resolveStore($data['store'])
            : Store::main();

        abort_unless(
            $store->isActive(),
            404,
            'Business unavailable.'
        );

        $customers->associate(
            $store,
            $request->user()
        );

        $customers->requireActiveCustomer(
            $store,
            $request->user()
        );

        return $store;
    }
}
