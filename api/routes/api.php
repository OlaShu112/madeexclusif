<?php

use App\Http\Controllers\Admin\BrandCategoryAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

// ---- Public ----
Route::get('/products', [CatalogController::class, 'index']);
Route::get('/products/{slug}', [CatalogController::class, 'show']);
Route::get('/filters', [CatalogController::class, 'filters']);
Route::post('/coupons/check', [CouponController::class, 'check'])->middleware('throttle:20,1');

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Checkout (guest or signed in - OrderController reads the token when present)
Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:20,1');
Route::get('/checkout/confirm', [OrderController::class, 'confirm'])->middleware('throttle:60,1');

// Stripe calls this server-to-server; authenticity is checked via the signature header.
Route::post('/stripe/webhook', StripeWebhookController::class);

// ---- Authenticated customer ----
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{number}', [OrderController::class, 'show']);

    Route::post('/products/{slug}/reviews', [ReviewController::class, 'store']);

    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/{productId}', [WishlistController::class, 'toggle']);
});

// ---- Admin ----
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/dashboard', DashboardController::class);

    // Images are uploaded with multipart forms, which cannot send PUT - the admin UI posts
    // to /admin/products/{id} with _method=PUT, which Laravel handles natively.
    Route::apiResource('products', ProductAdminController::class)->except('show');
    Route::post('/brands', [BrandCategoryAdminController::class, 'storeBrand']);
    Route::post('/categories', [BrandCategoryAdminController::class, 'storeCategory']);

    Route::get('/orders', [OrderAdminController::class, 'index']);
    Route::get('/orders/{order}', [OrderAdminController::class, 'show']);
    Route::patch('/orders/{order}', [OrderAdminController::class, 'update']);
});