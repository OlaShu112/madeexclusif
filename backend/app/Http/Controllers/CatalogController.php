<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /** GET /api/products?q=&category=&brand=&gender=&min_price=&max_price=&sort=&per_page= */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sort' => ['nullable', 'in:newest,price_asc,price_desc,name'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        $query = Product::active()
            ->with(['brand:id,name,slug', 'category:id,name,slug'])
            ->withMin('variants as min_price', 'price')
            ->withAvg('reviews as rating_avg', 'rating')
            ->withCount('reviews');

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('notes_top', 'like', "%{$q}%")
                  ->orWhere('notes_heart', 'like', "%{$q}%")
                  ->orWhere('notes_base', 'like', "%{$q}%")
                  ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$q}%"));
            });
        }
        if ($c = $request->query('category')) {
            $query->whereHas('category', fn ($x) => $x->where('slug', $c));
        }
        if ($b = $request->query('brand')) {
            $query->whereHas('brand', fn ($x) => $x->where('slug', $b));
        }
        if ($g = $request->query('gender')) {
            $query->where('gender', $g);
        }
        if ($request->filled('featured')) {
            $query->where('is_featured', true);
        }
        if ($request->filled('min_price')) {
            $query->whereHas('variants', fn ($v) => $v->where('price', '>=', (float) $request->query('min_price')));
        }
        if ($request->filled('max_price')) {
            $query->whereHas('variants', fn ($v) => $v->where('price', '<=', (float) $request->query('max_price')));
        }

        match ($request->query('sort', 'newest')) {
            'price_asc' => $query->orderBy('min_price'),
            'price_desc' => $query->orderByDesc('min_price'),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        return response()->json($query->paginate((int) $request->query('per_page', 12))->withQueryString());
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::active()
            ->where('slug', $slug)
            ->with(['brand', 'category', 'variants', 'reviews.user'])
            ->withAvg('reviews as rating_avg', 'rating')
            ->withCount('reviews')
            ->firstOrFail();

        $related = Product::active()
            ->where('id', '!=', $product->id)
            ->where(fn ($q) => $q->where('category_id', $product->category_id)->orWhere('brand_id', $product->brand_id))
            ->withMin('variants as min_price', 'price')
            ->with('brand:id,name')
            ->limit(4)
            ->get();

        return response()->json(['product' => $product, 'related' => $related]);
    }

    public function filters(): JsonResponse
    {
        return response()->json([
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug']),
            'brands' => Brand::orderBy('name')->get(['id', 'name', 'slug']),
            'genders' => ['women', 'men', 'unisex'],
        ]);
    }
}
