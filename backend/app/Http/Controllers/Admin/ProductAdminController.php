<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Product::with(['brand:id,name', 'category:id,name', 'variants'])->latest()->paginate(20)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $product = DB::transaction(function () use ($data) {
            $variants = $data['variants'];
            unset($data['variants']);
            $product = Product::create($data);
            $this->syncVariants($product, $variants);

            return $product;
        });

        return response()->json($product->load('variants'), 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $this->validated($request, $product);

        DB::transaction(function () use ($data, $product) {
            $variants = $data['variants'];
            unset($data['variants']);
            $product->update($data);
            $this->syncVariants($product, $variants);
        });

        return response()->json($product->fresh('variants'));
    }

    public function destroy(Product $product): JsonResponse
    {
        // Soft retire: keeps order history intact and links valid.
        $product->update(['is_active' => false]);

        return response()->json(['message' => 'Product hidden from the storefront.']);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'brand_id' => ['required', 'exists:brands,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'notes_top' => ['nullable', 'string', 'max:190'],
            'notes_heart' => ['nullable', 'string', 'max:190'],
            'notes_base' => ['nullable', 'string', 'max:190'],
            'gender' => ['required', Rule::in(['women', 'men', 'unisex'])],
            'concentration' => ['required', 'string', 'max:30'],
            'image' => ['nullable', 'string', 'max:255'],
            'accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.size_ml' => ['required', 'integer', 'min:1', 'max:1000'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'max:60'],
        ]);
    }

    private function syncVariants(Product $product, array $variants): void
    {
        $keep = [];
        foreach ($variants as $v) {
            $row = $product->variants()->updateOrCreate(
                ['size_ml' => $v['size_ml']],
                [
                    'price' => $v['price'],
                    'stock' => $v['stock'],
                    'sku' => $v['sku'] ?? strtoupper('MX-' . $product->id . '-' . $v['size_ml']),
                ]
            );
            $keep[] = $row->id;
        }
        // Variants referenced by past orders are nulled on delete (nullOnDelete), so this is safe.
        $product->variants()->whereNotIn('id', $keep)->delete();
    }
}
