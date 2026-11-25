<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BusinessCategory;
use App\Models\Category;
use App\Models\Store;
use App\Services\StoreNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
            ->with('businessCategory:id,name,slug')
            ->where('slug', $slug)
            ->firstOrFail([
                'id',
                'business_category_id',
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
     *
     * Automatically register business application
     * confirmation and administrator notifications.
     */
    public function open(
        Request $request,
        StoreNotificationService $notifications
    ): JsonResponse
    {
        $user = $request->user();

        if ($user->store()->exists()) {
            throw ValidationException::withMessages([
                'name' => 'You already have a store.',
            ]);
        }

        if (is_string($request->input('name'))) {
            $request->merge([
                'name' => trim($request->input('name')),
            ]);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:120',
                'unique:stores,name',
                'unique:brands,name',
            ],

            'business_category_id' => [
                'required',
                'integer',
                Rule::exists('business_categories', 'id')
                    ->where('is_active', true),
            ],

            'tagline' => [
                'nullable',
                'string',
                'max:255',
            ],

            'payment_mode' => [
                'required',
                Rule::in([
                    'platform',
                    'independent',
                    'not_configured',
                ]),
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
                $logoPath,
                $notifications
            ) {
                // Prevent simultaneous registrations
                // by the same customer.

                $lockedUser = $user->newQuery()
                    ->lockForUpdate()
                    ->findOrFail($user->id);

                if ($lockedUser->store()->exists()) {
                    throw ValidationException::withMessages([
                        'name' => 'You already have a store.',
                    ]);
                }

                // Retrieve the selected business category.

                $businessCategory = BusinessCategory::active()
                    ->lockForUpdate()
                    ->find($data['business_category_id']);

                if (! $businessCategory) {
                    throw ValidationException::withMessages([
                        'business_category_id' =>
                            'The selected business category is unavailable.',
                    ]);
                }

                // Create the store brand.

                $brand = Brand::create([
                    'name' => $data['name'],
                ]);

                // Create the seller storefront.

                $newStore = Store::create([
                    'user_id' => $user->id,

                    'brand_id' => $brand->id,

                    'business_category_id' =>
                        $businessCategory->id,

                    'name' => $data['name'],

                    'tagline' => $data['tagline'] ?? null,

                    'accent' => $data['accent'] ?? '#1a1a1a',

                    'logo' => $logoPath,

                    'type' => $businessCategory->slug,

                    'status' => 'pending',

                    'is_main' => false,
                ]);

                /*
                 * Register automatic business notifications.
                 *
                 * 1. Business application confirmation
                 *    to the store owner.
                 *
                 * 2. New business application notification
                 *    to the marketplace administrator.
                 */

                $newStore->paymentSetting()->create([
                    'payment_mode' => $data['payment_mode'],
                    'provider' => $data['payment_mode'] === 'platform'
                        ? 'stripe_connect'
                        : null,
                    'status' => 'pending_setup',
                    'checkout_enabled' => false,
                    'preference_changed_at' => now(),
                ]);

                $notifications->applicationSubmitted($newStore);

                return $newStore;
            });

        } catch (Throwable $e) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            throw $e;
        }

        return response()->json([
            'message' =>
                'Store created successfully. Your store is awaiting approval.',

            'store' => $store->load('businessCategory', 'paymentSetting'),
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

        $store->load('businessCategory', 'paymentSetting');

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

        // Retrieve product categories belonging
        // to the seller's business category.

        $categories = Category::query()
            ->whereNotNull('business_category_id')
            ->where(
                'business_category_id',
                $store->business_category_id
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
            ]);

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
     * Store name, slug, owner, business category and
     * approval status cannot be changed here.
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
                'max:255',
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

            'store' => $store->fresh()
                ->load('businessCategory'),
        ]);
    }
}