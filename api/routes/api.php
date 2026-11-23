<?php

use App\Http\Controllers\Admin\BrandCategoryAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\Admin\StoreAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Seller\SellerOrderController;
use App\Http\Controllers\Seller\SellerProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiChatController;
/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
// Made Exclusif AI Chatbot
// Customers can ask questions without logging in.
Route::post('/ai/chat', [AiChatController::class, 'chat'])
    ->middleware('throttle:20,1');

// Product Catalogue
Route::get('/products', [CatalogController::class, 'index']);

Route::get('/products/{slug}', [CatalogController::class, 'show']);

Route::get('/filters', [CatalogController::class, 'filters']);

Route::get('/stores/{slug}', [StoreController::class, 'show']);

Route::post('/coupons/check', [CouponController::class, 'check'])
    ->middleware('throttle:20,1');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:10,1');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Checkout and Payments
|--------------------------------------------------------------------------
*/

Route::post('/orders', [OrderController::class, 'store'])
    ->middleware('throttle:20,1');

Route::get('/checkout/confirm', [OrderController::class, 'confirm'])
    ->middleware('throttle:60,1');

Route::post('/stripe/webhook', StripeWebhookController::class);

/*
|--------------------------------------------------------------------------
| Authenticated Customer Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/orders', [OrderController::class, 'index']);

    Route::get('/orders/{number}', [OrderController::class, 'show']);

    Route::post(
        '/products/{slug}/reviews',
        [ReviewController::class, 'store']
    );

    Route::get('/wishlist', [WishlistController::class, 'index']);

    Route::post(
        '/wishlist/{productId}',
        [WishlistController::class, 'toggle']
    );

    /*
    | Store Registration
    */

    Route::post('/stores', [StoreController::class, 'open'])
        ->middleware('throttle:5,1');

    Route::get('/my-store', [StoreController::class, 'mine']);
});

/*
|--------------------------------------------------------------------------
| Seller Routes
|--------------------------------------------------------------------------
|
| Requires authentication and an approved seller store.
|
*/

Route::prefix('seller')
    ->middleware(['auth:sanctum', 'seller'])
    ->group(function () {

        /*
        | Store Management
        */

        Route::put('/store', [StoreController::class, 'update']);

        /*
        | Product Management
        */

        Route::get(
            '/products',
            [SellerProductController::class, 'index']
        );

        Route::post(
            '/products',
            [SellerProductController::class, 'store']
        );

        Route::put(
            '/products/{product}',
            [SellerProductController::class, 'update']
        );

        Route::delete(
            '/products/{product}',
            [SellerProductController::class, 'destroy']
        );

        /*
        | Seller Orders
        */

        Route::get(
            '/orders',
            [SellerOrderController::class, 'index']
        );

        Route::get(
            '/orders/{order}',
            [SellerOrderController::class, 'show']
        );

        Route::patch(
            '/orders/{order}',
            [SellerOrderController::class, 'update']
        );
    });

/*
|--------------------------------------------------------------------------
| Administrator Routes
|--------------------------------------------------------------------------
|
| Requires authentication and platform administrator privileges.
|
*/

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {

        /*
        | Dashboard
        */

        Route::get(
            '/dashboard',
            [DashboardController::class, 'index']
        );

        /*
        | Product Management
        */

        Route::get(
            '/products',
            [ProductAdminController::class, 'index']
        );

        Route::post(
            '/products',
            [ProductAdminController::class, 'store']
        );

        Route::put(
            '/products/{product}',
            [ProductAdminController::class, 'update']
        );

        Route::delete(
            '/products/{product}',
            [ProductAdminController::class, 'destroy']
        );

        /*
        | Brand and Category Management
        */

        Route::get(
            '/brands',
            [BrandCategoryAdminController::class, 'brands']
        );

        Route::post(
            '/brands',
            [BrandCategoryAdminController::class, 'storeBrand']
        );

        Route::get(
            '/categories',
            [BrandCategoryAdminController::class, 'categories']
        );

        Route::post(
            '/categories',
            [BrandCategoryAdminController::class, 'storeCategory']
        );

        /*
        | Order Management
        */

        Route::get(
            '/orders',
            [OrderAdminController::class, 'index']
        );

        Route::get(
            '/orders/{order}',
            [OrderAdminController::class, 'show']
        );

        Route::patch(
            '/orders/{order}',
            [OrderAdminController::class, 'update']
        );

        /*
        | Seller Store Management
        */

        Route::get(
            '/stores',
            [StoreAdminController::class, 'index']
        );

        Route::get(
            '/stores/{store}',
            [StoreAdminController::class, 'show']
        );

        Route::patch(
            '/stores/{store}',
            [StoreAdminController::class, 'update']
        );
    });

