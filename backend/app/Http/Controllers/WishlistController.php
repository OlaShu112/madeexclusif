<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::active()
            ->whereIn('id', $request->user()->wishlist()->pluck('product_id'))
            ->with('brand:id,name')
            ->withMin('variants as min_price', 'price')
            ->get();

        return response()->json($products);
    }

    public function toggle(Request $request, int $productId): JsonResponse
    {
        Product::active()->findOrFail($productId);

        $existing = $request->user()->wishlist()->where('product_id', $productId)->first();
        if ($existing) {
            $existing->delete();

            return response()->json(['wishlisted' => false]);
        }

        $request->user()->wishlist()->create(['product_id' => $productId]);

        return response()->json(['wishlisted' => true]);
    }
}
