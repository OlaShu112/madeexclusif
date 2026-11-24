<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use Illuminate\Http\JsonResponse;

class BusinessCategoryController extends Controller
{
    /**
     * Retrieve all active business categories.
     */
    public function index(): JsonResponse
    {
        $categories = BusinessCategory::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
                'description',
                'icon',
                'image',
            ]);

        return response()->json($categories);
    }

    /**
     * Retrieve a business category and its
     * associated product categories.
     */
    public function show(string $slug): JsonResponse
    {
        $category = BusinessCategory::active()
            ->where('slug', $slug)
            ->with([
                'categories' => function ($query) {
                    $query->orderBy('name')
                        ->select([
                            'id',
                            'business_category_id',
                            'name',
                            'slug',
                            'description',
                        ]);
                },
            ])
            ->firstOrFail();

        return response()->json($category);
    }
}