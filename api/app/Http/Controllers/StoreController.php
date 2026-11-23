<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class StoreController extends Controller
{
    /**
     * Display public branding information for an active store.
     * Store owner details are not exposed.
     */
    public function show(string $slug): JsonResponse
    {
        $store = Store::active()
            ->where('slug', $slug)
            ->firstOrFail([
                'id',
                'name',
                'slug',
                'type',
                'tagline',
                'logo',
                'accent',
                'is_main',
            ]);

        return response()->json($store);
    }

    /**
     * Allow an authenticated user to create a store.
     * New stores require administrator approval.
     */
    public function open(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->store()->exists()) {
            throw ValidationException::withMessages([
                'name' => 'You already have a store.',
            ]);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:80',
                'unique:stores,name',
                'unique:brands,name',
            ],
            'tagline' => [
                'nullable',
                'string',
                'max:140',
            ],
            'accent' => [
                'nullable',
                'regex:/^#[0-9a-fA-F]{6}$/',
            ],
            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $logoPath = null;

        try {
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')
                    ->store('logos', 'public');
            }

            $store = DB::transaction(function () use (
                $user,
                $data,
                $logoPath
            ) {
                $brand = Brand::create([
                    'name' => $data['name'],
                ]);

                return Store::create([
                    'user_id' => $user->id,
                    'brand_id' => $brand->id,
                    'name' => $data['name'],
                    'tagline' => $data['tagline'] ?? null,
                    'accent' => $data['accent'] ?? '#1a1a1a',
                    'logo' => $logoPath,
                    'type' => 'clothing',
                    'status' => 'pending',
                    'is_main' => false,
                ]);
            });

        } catch (Throwable $e) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            throw $e;
        }

        return response()->json([
            'message' => 'Store created successfully. Your store is awaiting approval.',
            'store' => $store,
        ], 201);
    }

    /**
     * Retrieve the authenticated user's store.
     * Includes dashboard statistics and store categories.
     */
    public function mine(Request $request): JsonResponse
    {
        $store = $request->user()->store;

        if (! $store) {
            return response()->json([
                'store' => null,
                'stats' => null,
                'categories' => [],
            ]);
        }

        $paidStatuses = [
            'paid',
            'processing',
            'shipped',
            'delivered',
        ];

        $productsCount = $store->products()->count();

        $ordersCount = $store->orders()->count();

        $totalRevenue = $store->orders()
            ->whereIn('status', $paidStatuses)
            ->sum('total');

        $categories = Category::query()
            ->where('type', $store->type)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'store' => $store,
            'stats' => [
                'products' => $productsCount,
                'orders' => $ordersCount,
                'revenue' => (float) $totalRevenue,
            ],
            'categories' => $categories,
        ]);
    }

    /**
     * Allow an approved seller to update store branding.
     *
     * Store name, slug, owner, type and approval status
     * cannot be changed through this endpoint.
     */
    public function update(Request $request): JsonResponse
    {
        $store = $request->attributes->get('store');

        if (! $store instanceof Store) {
            return response()->json([
                'message' => 'Store access denied.',
            ], 403);
        }

        $data = $request->validate([
            'tagline' => [
                'sometimes',
                'nullable',
                'string',
                'max:140',
            ],
            'accent' => [
                'sometimes',
                'regex:/^#[0-9a-fA-F]{6}$/',
            ],
            'logo' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $oldLogo = $store->logo;
        $newLogo = null;

        try {
            if ($request->hasFile('logo')) {
                $newLogo = $request->file('logo')
                    ->store('logos', 'public');

                $data['logo'] = $newLogo;
            } else {
                unset($data['logo']);
            }

            $store->update($data);

        } catch (Throwable $e) {
            if ($newLogo) {
                Storage::disk('public')->delete($newLogo);
            }

            throw $e;
        }

        if (
            $newLogo &&
            $oldLogo &&
            $oldLogo !== $newLogo
        ) {
            Storage::disk('public')->delete($oldLogo);
        }

        return response()->json([
            'message' => 'Store branding updated successfully.',
            'store' => $store->fresh(),
        ]);
    }
}
