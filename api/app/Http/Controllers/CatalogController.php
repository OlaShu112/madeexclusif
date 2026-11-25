<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CatalogController extends Controller
{
    /**
     * GET /api/products
     *
     * Supported query parameters:
     * store, q, category, brand, gender, featured,
     * min_price, max_price, sort, per_page
     *
     * Supports independent business storefronts
     * across multiple business categories.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'featured' => ['nullable', 'boolean'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],

            'sort' => [
                'nullable',
                'in:newest,price_asc,price_desc,name',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:48',
            ],
        ]);

        /*
         * Validate the selected price range.
         */

        if (
            isset($data['min_price'], $data['max_price']) &&
            $data['min_price'] > $data['max_price']
        ) {
            throw ValidationException::withMessages([
                'max_price' =>
                    'Maximum price must be greater than or equal to minimum price.',
            ]);
        }

        /*
         * Resolve the selected independent business.
         */

        $store = $this->resolveStore($request);

        /*
         * Retrieve active products belonging
         * exclusively to the selected store.
         */

        $query = Product::active()
            ->where('store_id', $store->id)
            ->with([
                'brand:id,name,slug',
                'category:id,name,slug',
                'store:id,slug,name,type,is_main',
            ])
            ->withMin('variants as min_price', 'price')
            ->withAvg('reviews as rating_avg', 'rating')
            ->withCount('reviews');

        /*
         * General product search.
         *
         * Supports product names, descriptions,
         * categories, brands and fragrance attributes.
         *
         * Search conditions remain grouped to prevent
         * products from unrelated stores being returned.
         */

        if (! empty($data['q'])) {
            $search = trim($data['q']);

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")

                    ->orWhere(
                        'description',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhereHas(
                        'category',
                        function ($category) use ($search) {
                            $category->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    )

                    ->orWhere(
                        'notes_top',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'notes_heart',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'notes_base',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'concentration',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhereHas(
                        'brand',
                        function ($brand) use ($search) {
                            $brand->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
            });
        }

        /*
         * Filter products by category.
         */

        if (! empty($data['category'])) {
            $query->whereHas(
                'category',
                function ($builder) use ($data) {
                    $builder->where(
                        'slug',
                        $data['category']
                    );
                }
            );
        }

        /*
         * Filter products by brand.
         */

        if (! empty($data['brand'])) {
            $query->whereHas(
                'brand',
                function ($builder) use ($data) {
                    $builder->where(
                        'slug',
                        $data['brand']
                    );
                }
            );
        }

        /*
         * Filter products by gender where applicable.
         */

        if (! empty($data['gender'])) {
            $query->where(
                'gender',
                $data['gender']
            );
        }

        /*
         * Filter featured products.
         */

        if (! empty($data['featured'])) {
            $query->where('is_featured', true);
        }

        /*
         * Filter products by variant price.
         *
         * Both price bounds must apply
         * to the same product variant.
         */

        if (
            isset($data['min_price']) ||
            isset($data['max_price'])
        ) {
            $query->whereHas(
                'variants',
                function ($variant) use ($data) {
                    if (isset($data['min_price'])) {
                        $variant->where(
                            'price',
                            '>=',
                            $data['min_price']
                        );
                    }

                    if (isset($data['max_price'])) {
                        $variant->where(
                            'price',
                            '<=',
                            $data['max_price']
                        );
                    }
                }
            );
        }

        /*
         * Apply the selected product sorting option.
         */

        $sort = $data['sort'] ?? 'newest';

        match ($sort) {
            'price_asc' => $query
                ->orderBy('min_price')
                ->orderBy('id'),

            'price_desc' => $query
                ->orderByDesc('min_price')
                ->orderBy('id'),

            'name' => $query
                ->orderBy('name')
                ->orderBy('id'),

            default => $query->latest(),
        };

        /*
         * Paginate the product results.
         */

        $products = $query->paginate(
            (int) ($data['per_page'] ?? 12)
        )->withQueryString();

        return response()->json($products);
    }

    /**
     * GET /api/products/{slug}
     *
     * Display a product belonging to an active store.
     */
    public function show(
        Request $request,
        string $slug
    ): JsonResponse
    {
        $request->validate([
            'store' => ['nullable', 'string', 'max:255'],
        ]);

        $store = $this->resolveStore($request);

        /*
         * Retrieve the product exclusively
         * from the selected business storefront.
         */

        $product = Product::active()
            ->where('slug', $slug)
            ->where('store_id', $store->id)
            ->with([
                'brand',
                'category',
                'variants',
                'reviews.user',
                'store:id,slug,name,type,is_main,tagline,logo',
            ])
            ->withAvg('reviews as rating_avg', 'rating')
            ->withCount('reviews')
            ->firstOrFail();

        return response()->json($product);
    }

    /**
     * GET /api/filters
     *
     * Return available product categories,
     * brands and price range for the selected store.
     *
     * Categories are determined by the registered
     * business category, with legacy type filtering
     * retained for backward compatibility.
     */
    public function filters(Request $request): JsonResponse
    {
        $request->validate([
            'store' => ['nullable', 'string', 'max:255'],
        ]);

        $store = $this->resolveStore($request);

        /*
         * Retrieve categories belonging to the
         * selected store's registered business category.
         *
         * Legacy stores without a business category
         * continue to use the existing type field.
         */

        $categories = Category::query()
            ->when(
                $store->business_category_id !== null,

                function ($query) use ($store) {
                    $query->where(
                        'business_category_id',
                        $store->business_category_id
                    );
                },

                function ($query) use ($store) {
                    $query->where(
                        'type',
                        $store->type
                    );
                }
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
            ]);

        /*
         * Retrieve brands associated with active
         * products belonging to the selected store.
         */

        $brands = Brand::query()
            ->whereHas(
                'products',
                function ($query) use ($store) {
                    $query->active()
                        ->where('store_id', $store->id);
                }
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
            ]);

        /*
         * Calculate the available product price range
         * exclusively for the selected store.
         */

        $priceRange = Product::active()
            ->where('store_id', $store->id)
            ->whereHas('variants')
            ->select('id')
            ->withMin('variants as min_price', 'price')
            ->withMax('variants as max_price', 'price')
            ->get();

        $minPrice = $priceRange->min('min_price');
        $maxPrice = $priceRange->max('max_price');

        /*
         * Return the independent store's
         * available catalogue filters.
         */

        return response()->json([
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'type' => $store->type,
                'is_main' => $store->is_main,
            ],

            'categories' => $categories,

            'brands' => $brands,

            'price_range' => [
                'min' => $minPrice !== null
                    ? (float) $minPrice
                    : 0,

                'max' => $maxPrice !== null
                    ? (float) $maxPrice
                    : 0,
            ],
        ]);
    }

    /**
     * Resolve the active independent store.
     *
     * If no store slug is supplied,
     * use the main Made Exclusif store.
     */
    private function resolveStore(Request $request): Store
    {
        $slug = $request->query('store');

        if ($slug !== null && $slug !== '') {
            return Store::active()
                ->where('slug', $slug)
                ->firstOrFail();
        }

        $store = Store::main();

        if ($store->status !== 'active') {
            abort(
                404,
                'The main store is currently unavailable.'
            );
        }

        return $store;
    }
}