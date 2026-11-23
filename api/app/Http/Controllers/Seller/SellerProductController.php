<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Sellers can manage products belonging only to their own store.
 */
class SellerProductController extends Controller
{
    /**
     * GET /api/seller/products
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->currentStore($request);

        $products = Product::query()
            ->where('store_id', $store->id)
            ->with([
                'category:id,name',
                'variants',
            ])
            ->latest('id')
            ->paginate(50);

        return response()->json($products);
    }

    /**
     * POST /api/seller/products
     */
    public function store(Request $request): JsonResponse
    {
        $store = $this->currentStore($request);

        $data = $this->validated($request, $store);

        $labels = $this->labels($data['variants']);

        $imagePath = null;

        try {
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')
                    ->store('products', 'public');
            }

            $product = DB::transaction(function () use (
                $request,
                $store,
                $data,
                $labels,
                $imagePath
            ) {
                $attributes = $this->productAttributes(
                    $request,
                    $store,
                    $data
                );

                if ($imagePath) {
                    $attributes['image'] = $imagePath;
                }

                $product = Product::create($attributes);

                $this->syncVariants(
                    $product,
                    $data['variants'],
                    $labels
                );

                return $product;
            });
        } catch (Throwable $e) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $e;
        }

        return response()->json(
            $product->load('category', 'variants'),
            201
        );
    }

    /**
     * PUT/PATCH /api/seller/products/{product}
     */
    public function update(
        Request $request,
        Product $product
    ): JsonResponse {
        $store = $this->currentStore($request);

        abort_if($product->store_id !== $store->id, 404);

        $data = $this->validated($request, $store);

        $labels = $this->labels($data['variants']);

        $oldImage = $product->image;
        $newImage = null;

        try {
            if ($request->hasFile('image')) {
                $newImage = $request->file('image')
                    ->store('products', 'public');
            }

            DB::transaction(function () use (
                $request,
                $store,
                $data,
                $labels,
                $product,
                $newImage
            ) {
                $attributes = $this->productAttributes(
                    $request,
                    $store,
                    $data
                );

                if ($newImage) {
                    $attributes['image'] = $newImage;
                } elseif ($request->boolean('remove_image')) {
                    $attributes['image'] = null;
                }

                $product->update($attributes);

                $this->syncVariants(
                    $product,
                    $data['variants'],
                    $labels
                );
            });
        } catch (Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $e;
        }

        if (
            $oldImage &&
            ($newImage || $request->boolean('remove_image')) &&
            ! str_starts_with($oldImage, 'http')
        ) {
            Storage::disk('public')->delete($oldImage);
        }

        return response()->json(
            $product->fresh(['category', 'variants'])
        );
    }

    /**
     * DELETE /api/seller/products/{product}
     *
     * Retire a product without deleting order history.
     */
    public function destroy(
        Request $request,
        Product $product
    ): JsonResponse {
        $store = $this->currentStore($request);

        abort_if($product->store_id !== $store->id, 404);

        $product->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Product retired successfully.',
        ]);
    }

    /**
     * Retrieve the seller's approved store.
     */
    private function currentStore(Request $request): Store
    {
        $store = $request->attributes->get('store');

        abort_unless($store instanceof Store, 403);

        abort_unless($store->status === 'active', 403);

        return $store;
    }

    /**
     * Validate product and variant information.
     */
    private function validated(
        Request $request,
        Store $store
    ): array {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:180',
            ],

            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('type', $store->type),
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'remove_image' => [
                'sometimes',
                'boolean',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'is_featured' => [
                'sometimes',
                'boolean',
            ],

            'variants' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'variants.*.sku' => [
                'required',
                'string',
                'max:100',
            ],

            'variants.*.size_label' => [
                'required',
                'string',
                'max:60',
            ],

            'variants.*.price' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'variants.*.stock' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        $skus = collect($data['variants'])
            ->pluck('sku')
            ->map(fn ($sku) => strtolower(trim($sku)));

        if ($skus->count() !== $skus->unique()->count()) {
            throw ValidationException::withMessages([
                'variants' => 'Variant SKUs must be unique.',
            ]);
        }

        return $data;
    }

    /**
     * Generate display labels for clothing variants.
     */
    private function labels(array $variants): array
    {
        return collect($variants)
            ->map(fn ($variant) => trim($variant['size_label']))
            ->all();
    }

    /**
     * Prepare product attributes.
     */
    private function productAttributes(
        Request $request,
        Store $store,
        array $data
    ): array {
        return [
            'store_id' => $store->id,
            'brand_id' => $store->brand_id,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(8)),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured', false),
        ];
    }

    /**
     * Synchronise product variants.
     *
     * Existing variants with matching SKUs are updated.
     * New SKUs create new variants.
     *
     * Missing variants are retained to preserve
     * references from historical orders.
     */
    private function syncVariants(
        Product $product,
        array $variants,
        array $labels
    ): void {
        foreach ($variants as $index => $variant) {
            $sku = trim($variant['sku']);

            $existing = $product->variants()
                ->where('sku', $sku)
                ->first();

            $attributes = [
                'size_ml' => null,
                'size_label' => $labels[$index],
                'price' => $variant['price'],
                'stock' => $variant['stock'],
            ];

            if ($existing) {
                $existing->update($attributes);
            } else {
                $product->variants()->create([
                    'sku' => $sku,
                    ...$attributes,
                ]);
            }
        }
    }
}

