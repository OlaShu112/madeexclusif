<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::with(['brand:id,name', 'category:id,name', 'variants'])
            ->when($request->query('q'), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->latest('id')
            ->paginate(50);

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $product = DB::transaction(function () use ($request, $data) {
            $attributes = $this->productAttributes($request, $data);

            if ($request->hasFile('image')) {
                $attributes['image'] = $request->file('image')->store('products', 'public');
            }

            $product = Product::create($attributes);
            $this->syncVariants($product, $data);

            return $product;
        });

        return response()->json($product->load('brand', 'category', 'variants'), 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $this->validated($request);
        $oldImage = $product->image;
        $newImage = null;

        DB::transaction(function () use ($request, $data, $product, &$newImage) {
            $attributes = $this->productAttributes($request, $data);

            if ($request->hasFile('image')) {
                $newImage = $request->file('image')->store('products', 'public');
                $attributes['image'] = $newImage;
            } elseif ($request->boolean('remove_image')) {
                $attributes['image'] = null;
            }

            $product->update($attributes);
            $this->syncVariants($product, $data);
        });

        // Remove the old file only after the database change succeeded.
        if ($oldImage && ($newImage || $request->boolean('remove_image')) && ! str_starts_with($oldImage, 'http')) {
            Storage::disk('public')->delete($oldImage);
        }

        return response()->json($product->fresh(['brand', 'category', 'variants']));
    }

    /** Soft retire: keeps order history intact and links valid. Re-enable with is_active in the edit form. */
    public function destroy(Product $product): JsonResponse
    {
        $product->update(['is_active' => false]);

        return response()->json(['message' => 'Product hidden from the storefront.']);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'brand_id' => ['required', 'exists:brands,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'notes_top' => ['nullable', 'string', 'max:190'],
            'notes_heart' => ['nullable', 'string', 'max:190'],
            'notes_base' => ['nullable', 'string', 'max:190'],
            'gender' => ['required', Rule::in(['women', 'men', 'unisex'])],
            'concentration' => ['required', 'string', 'max:30'],
            'accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],

            // Exactly two sizes are sold: 50ml and 100ml.
            'price_50' => ['required', 'numeric', 'min:0.5', 'max:100000'],
            'price_100' => ['required', 'numeric', 'min:0.5', 'max:100000'],
            'stock_50' => ['required', 'integer', 'min:0', 'max:100000'],
            'stock_100' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);
    }

    /** @return array<string, mixed> */
    private function productAttributes(Request $request, array $data): array
    {
        return [
            'name' => $data['name'],
            'brand_id' => $data['brand_id'],
            'category_id' => $data['category_id'],
            'description' => $data['description'] ?? null,
            'notes_top' => $data['notes_top'] ?? null,
            'notes_heart' => $data['notes_heart'] ?? null,
            'notes_base' => $data['notes_base'] ?? null,
            'gender' => $data['gender'],
            'concentration' => $data['concentration'],
            'accent' => $data['accent'] ?? '#c9a86a',
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ];
    }

    private function syncVariants(Product $product, array $data): void
    {
        foreach (Product::SIZES as $ml) {
            $product->variants()->updateOrCreate(
                ['size_ml' => $ml],
                [
                    'price' => $data["price_{$ml}"],
                    'stock' => $data["stock_{$ml}"],
                    'sku' => strtoupper("MX-{$product->id}-{$ml}"),
                ]
            );
        }

        // Anything that is not 50ml/100ml (e.g. legacy sizes) is removed; past orders keep their snapshot.
        $product->variants()->whereNotIn('size_ml', Product::SIZES)->delete();
    }
}