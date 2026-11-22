<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandCategoryAdminController extends Controller
{
    public function storeBrand(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', 'unique:brands,name']]);

        return response()->json(Brand::create($data), 201);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', 'unique:categories,name']]);

        return response()->json(Category::create($data), 201);
    }
}