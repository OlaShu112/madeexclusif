<?php

use App\Http\Controllers\Admin\AnalyticsAdminController;
use App\Http\Controllers\Admin\BrandCategoryAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailAdminController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RefundAdminController;
use App\Http\Controllers\Admin\StoreAdminController;

use App\Http\Controllers\Seller\SellerFinanceController;
use App\Http\Controllers\Seller\SellerOrderController;
use App\Http\Controllers\Seller\SellerProductController;
use App\Http\Controllers\Seller\StoreThemeController;
use App\Http\Controllers\Seller\StoreMediaController;

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessCategoryController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\WishlistController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| These routes are accessible without authentication.
|
*/

/*
|--------------------------------------------------------------------------
| Made Exclusif AI Chatbot
|--------------------------------------------------------------------------
|
| Customers can interact with the AI assistant without
| logging into their accounts.
|
*/

Route::post(
    '/ai/chat',
    [AiChatController::class, 'chat']
)
    ->middleware('throttle:20,1');

/*
|--------------------------------------------------------------------------
| Product Catalogue
|--------------------------------------------------------------------------
*/

Route::get(
    '/products',
    [CatalogController::class, 'index']
);

Route::get(
    '/products/{slug}',
    [CatalogController::class, 'show']
);

Route::get(
    '/filters',
    [CatalogController::class, 'filters']
);

/*
|--------------------------------------------------------------------------
| Business Categories
|--------------------------------------------------------------------------
*/

Route::get(
    '/business-categories',
    [BusinessCategoryController::class, 'index']
);

Route::get(
    '/business-categories/{slug}',
    [BusinessCategoryController::class, 'show']
);

/*
|--------------------------------------------------------------------------
| Public Storefront
|--------------------------------------------------------------------------
*/

Route::get(
    '/stores/{slug}',
    [StoreController::class, 'show']
);

/*
|--------------------------------------------------------------------------
| Coupons
|--------------------------------------------------------------------------
*/

Route::post(
    '/coupons/check',
    [CouponController::class, 'check']
)
    ->middleware('throttle:20,1');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::post(
    '/register',
    [AuthController::class, 'register']
)
    ->middleware('throttle:10,1');

Route::post(
    '/login',
    [AuthController::class, 'login']
)
    ->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Password Recovery
|--------------------------------------------------------------------------
|
| Customers, sellers and administrators can request
| a password reset link and securely reset their
| passwords without logging into their accounts.
|
*/

Route::post(
    '/forgot-password',
    [PasswordResetController::class, 'forgotPassword']
)
    ->middleware('throttle:5,1')
    ->name('password.email');

Route::post(
    '/reset-password',
    [PasswordResetController::class, 'resetPassword']
)
    ->middleware('throttle:5,1')
    ->name('password.update');

/*
|--------------------------------------------------------------------------
| Email Verification
|--------------------------------------------------------------------------
|
| Customers can verify their email addresses using
| secure, signed and time-limited verification links.
|
*/

Route::get(
    '/email/verify/{id}/{hash}',
    [EmailVerificationController::class, 'verify']
)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

/*
|--------------------------------------------------------------------------
| Checkout and Payments
|--------------------------------------------------------------------------
|
| Supports guest checkout and authenticated customers.
| Stripe webhook authenticity is verified inside
| StripeWebhookController.
|
*/

Route::post(
    '/orders',
    [OrderController::class, 'store']
)
    ->middleware('throttle:20,1');

Route::get(
    '/checkout/confirm',
    [OrderController::class, 'confirm']
)
    ->middleware('throttle:60,1');

Route::post(
    '/stripe/webhook',
    StripeWebhookController::class
);

/*
|--------------------------------------------------------------------------
| Authenticated Customer Routes
|--------------------------------------------------------------------------
|
| Requires a valid Laravel Sanctum authentication token.
|
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Customer Account
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/me',
        [AuthController::class, 'me']
    );

    Route::post(
        '/logout',
        [AuthController::class, 'logout']
    );

    /*
    |--------------------------------------------------------------------------
    | Resend Email Verification
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/email/verification-notification',
        [EmailVerificationController::class, 'resend']
    )
        ->middleware('throttle:6,1')
        ->name('verification.send');

    /*
    |--------------------------------------------------------------------------
    | Customer Orders
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/orders',
        [OrderController::class, 'index']
    );

    Route::get(
        '/orders/{number}',
        [OrderController::class, 'show']
    );

    /*
    |--------------------------------------------------------------------------
    | Product Reviews
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/products/{slug}/reviews',
        [ReviewController::class, 'store']
    );

    /*
    |--------------------------------------------------------------------------
    | Customer Wishlist
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/wishlist',
        [WishlistController::class, 'index']
    );

    Route::post(
        '/wishlist/{productId}',
        [WishlistController::class, 'toggle']
    );

    /*
    |--------------------------------------------------------------------------
    | Business Registration and Store Applications
    |--------------------------------------------------------------------------
    |
    | Authenticated customers can apply to become
    | sellers and monitor their application status.
    |
    */

    Route::post(
        '/stores',
        [StoreController::class, 'open']
    )
        ->middleware('throttle:5,1');

    Route::get(
        '/my-store',
        [StoreController::class, 'mine']
    );
});

/*
|--------------------------------------------------------------------------
| Seller Routes
|--------------------------------------------------------------------------
|
| Requires authentication and an approved seller store.
| Seller controllers must restrict access to resources
| belonging to the authenticated seller's store.
|
*/

Route::prefix('seller')
    ->middleware(['auth:sanctum', 'seller'])
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Independent Website Management
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/website/theme',
            [StoreThemeController::class, 'show']
        );

        Route::put(
            '/website/theme',
            [StoreThemeController::class, 'update']
        )
            ->middleware('throttle:30,1');

        Route::post(
            '/website/theme/publish',
            [StoreThemeController::class, 'publish']
        )
            ->middleware('throttle:10,1');

        Route::post(
            '/website/theme/reset',
            [StoreThemeController::class, 'reset']
        )
            ->middleware('throttle:5,1');

        /*
        |--------------------------------------------------------------------------
        | Website Media Management
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/website/media',
            [StoreMediaController::class, 'index']
        );

        Route::post(
            '/website/media',
            [StoreMediaController::class, 'store']
        )
            ->middleware('throttle:20,1');

        Route::get(
            '/website/media/{media}',
            [StoreMediaController::class, 'show']
        )
            ->whereNumber('media');

        Route::patch(
            '/website/media/{media}',
            [StoreMediaController::class, 'update']
        )
            ->whereNumber('media')
            ->middleware('throttle:30,1');

        /*
        |--------------------------------------------------------------------------
        | Seller Store Management
        |--------------------------------------------------------------------------
        */

        Route::put(
            '/store',
            [StoreController::class, 'update']
        );

        /*
        |--------------------------------------------------------------------------
        | Seller Product Management
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Seller Order Management
        |--------------------------------------------------------------------------
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

        /*
        |--------------------------------------------------------------------------
        | Seller Financial Management
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/finance',
            [SellerFinanceController::class, 'show']
        );

        Route::get(
            '/finance/transactions',
            [SellerFinanceController::class, 'transactions']
        );

        Route::get(
            '/finance/payouts',
            [SellerFinanceController::class, 'payouts']
        );
    });

/*
|--------------------------------------------------------------------------
| Administrator Routes
|--------------------------------------------------------------------------
|
| Requires authentication and platform administrator
| privileges.
|
*/

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Administrator Profile Management
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/profile',
            [ProfileController::class, 'show']
        );

        Route::patch(
            '/profile',
            [ProfileController::class, 'update']
        )
            ->middleware('throttle:10,1');

        Route::post(
            '/profile/avatar',
            [ProfileController::class, 'uploadAvatar']
        )
            ->middleware('throttle:5,1');

        Route::delete(
            '/profile/avatar',
            [ProfileController::class, 'removeAvatar']
        )
            ->middleware('throttle:5,1');

        Route::put(
            '/profile/password',
            [ProfileController::class, 'updatePassword']
        )
            ->middleware('throttle:5,1');

        /*
        |--------------------------------------------------------------------------
        | Administrator Dashboard and Analytics
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/dashboard',
            [DashboardController::class, 'index']
        );

        Route::get(
            '/analytics',
            [AnalyticsAdminController::class, 'index']
        );

        /*
        |--------------------------------------------------------------------------
        | Administrator Product Management
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Brand Management
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/brands',
            [BrandCategoryAdminController::class, 'brands']
        );

        Route::post(
            '/brands',
            [BrandCategoryAdminController::class, 'storeBrand']
        );

        /*
        |--------------------------------------------------------------------------
        | Category Management
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/categories',
            [BrandCategoryAdminController::class, 'categories']
        );

        Route::post(
            '/categories',
            [BrandCategoryAdminController::class, 'storeCategory']
        );

        /*
        |--------------------------------------------------------------------------
        | Administrator Order Management
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Seller Store and Application Management
        |--------------------------------------------------------------------------
        |
        | Administrators can review business applications,
        | approve or reject stores, suspend active stores,
        | reactivate suspended stores and reopen applications.
        |
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

        /*
        |--------------------------------------------------------------------------
        | Transactional Email Management
        |--------------------------------------------------------------------------
        |
        | Administrators can monitor transactional email
        | records, processing statuses and email statistics.
        |
        */

        Route::get(
            '/emails/statistics',
            [EmailAdminController::class, 'statistics']
        );

        Route::get(
            '/emails',
            [EmailAdminController::class, 'index']
        );

        /*
        |--------------------------------------------------------------------------
        | Refund Management
        |--------------------------------------------------------------------------
        |
        | Administrators can review refund records,
        | inspect individual refunds and initiate
        | authorised order refund requests.
        |
        | Refund eligibility, payment verification,
        | duplicate prevention and financial safeguards
        | must be enforced by the refund service.
        |
        */

        Route::get(
            '/refunds',
            [RefundAdminController::class, 'index']
        );

        Route::get(
            '/refunds/{refund}',
            [RefundAdminController::class, 'show']
        );

        Route::post(
            '/orders/{order}/refunds',
            [RefundAdminController::class, 'store']
        )
            ->middleware('throttle:10,1');
    });


/*
|--------------------------------------------------------------------------
| Business Management Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/business-management.php';

/*
|--------------------------------------------------------------------------
| Platform Administration Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/platform-admin.php';

/*
|--------------------------------------------------------------------------
| Platform Finance and Communication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/platform-finance.php';

require __DIR__.'/platform-communication.php';

/*
|--------------------------------------------------------------------------
| Platform Finance and Communication Routes
|--------------------------------------------------------------------------
*/

require __DIR__ . '/platform-finance.php';

require __DIR__ . '/platform-finance-write.php';

require __DIR__ . '/platform-communication.php';

require __DIR__ . '/platform-system.php';