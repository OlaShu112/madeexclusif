<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\PlatformBrandingController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Admin\PlatformMediaController;
use App\Http\Controllers\Admin\PlatformCustomerController;
use App\Http\Controllers\Admin\PlatformAuditLogController;
use App\Http\Controllers\Admin\PlatformSubscriptionController;
use App\Http\Controllers\Admin\PlatformPromotionController;
use App\Http\Controllers\Admin\PlatformCouponController;
use App\Http\Controllers\Admin\PlatformSubscriptionHistoryController;
use App\Http\Controllers\SubscriptionPricingController;

/*
|--------------------------------------------------------------------------
| Public Platform Branding
|--------------------------------------------------------------------------
*/

Route::get(
    '/platform/branding',
    [PlatformBrandingController::class, 'publicShow']
)->middleware('throttle:120,1');

/*
|--------------------------------------------------------------------------
| Protected Platform Administration
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'admin',
])
    ->prefix('admin')
    ->group(function () {

        Route::get(
            '/platform/branding',
            [PlatformBrandingController::class, 'show']
        );

        Route::put(
            '/platform/branding',
            [PlatformBrandingController::class, 'update']
        );

        Route::post(
            '/platform/branding/reset',
            [PlatformBrandingController::class, 'reset']
        );

        Route::get(
            '/platform/settings',
            [PlatformSettingsController::class, 'index']
        );

        Route::put(
            '/platform/settings/{group}',
            [PlatformSettingsController::class, 'update']
        );

        Route::get(
            '/platform/media',
            [PlatformMediaController::class, 'index']
        );

        Route::post(
            '/platform/media',
            [PlatformMediaController::class, 'store']
        )->middleware('throttle:20,1');

        Route::get(
            '/customers',
            [PlatformCustomerController::class, 'index']
        );

        Route::get(
            '/customers/{customer}',
            [PlatformCustomerController::class, 'show']
        )->whereNumber('customer');

        Route::get(
            '/audit-logs',
            [PlatformAuditLogController::class, 'index']
        );

    });

/*
|--------------------------------------------------------------------------
| Platform Customer Management and Audit Logs
|--------------------------------------------------------------------------
*/

Route::prefix('admin/platform')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {

        // Customer management

        Route::get(
            '/customers',
            [PlatformCustomerController::class, 'index']
        );

        Route::get(
            '/customers/{customer}',
            [PlatformCustomerController::class, 'show']
        )->whereNumber('customer');

        Route::patch(
            '/customers/{customer}/memberships/{membership}',
            [
                PlatformCustomerController::class,
                'updateMembership'
            ]
        )
        ->whereNumber('customer')
        ->whereNumber('membership')
        ->middleware('throttle:30,1');

        // Platform audit logs

        Route::get(
            '/audit-logs',
            [PlatformAuditLogController::class, 'index']
        );

        Route::get(
            '/audit-logs/{log}',
            [PlatformAuditLogController::class, 'show']
        )->whereNumber('log');

    });

	/*
|--------------------------------------------------------------------------
| Platform Subscription Management
|--------------------------------------------------------------------------
*/

Route::prefix('admin/platform/subscriptions')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {

        // Subscription overview

        Route::get(
            '/overview',
            [PlatformSubscriptionController::class, 'overview']
        );

        // Subscription plans

        Route::get(
            '/plans',
            [PlatformSubscriptionController::class, 'plans']
        );

        Route::post(
            '/plans',
            [PlatformSubscriptionController::class, 'storePlan']
        );

        Route::get(
            '/plans/{plan}',
            [PlatformSubscriptionController::class, 'showPlan']
        )->whereNumber('plan');

        Route::put(
            '/plans/{plan}',
            [PlatformSubscriptionController::class, 'updatePlan']
        )->whereNumber('plan');

        Route::patch(
            '/plans/{plan}/status',
            [PlatformSubscriptionController::class, 'updatePlanStatus']
        )->whereNumber('plan');

        // Business subscriptions

        Route::get(
            '/businesses',
            [PlatformSubscriptionController::class, 'subscriptions']
        );

        Route::get(
            '/businesses/{subscription}',
            [PlatformSubscriptionController::class, 'showSubscription']
        )->whereNumber('subscription');

        // Promotional offers

        Route::get(
            '/offers',
            [PlatformPromotionController::class, 'index']
        );

        Route::post(
            '/offers',
            [PlatformPromotionController::class, 'store']
        );

        Route::get(
            '/offers/{offer}',
            [PlatformPromotionController::class, 'show']
        )->whereNumber('offer');

        Route::put(
            '/offers/{offer}',
            [PlatformPromotionController::class, 'update']
        )->whereNumber('offer');

        Route::patch(
            '/offers/{offer}/status',
            [PlatformPromotionController::class, 'updateStatus']
        )->whereNumber('offer');

        Route::post(
            '/offers/preview',
            [PlatformPromotionController::class, 'preview']
        );

        // Subscription coupons

        Route::get(
            '/coupons',
            [PlatformCouponController::class, 'index']
        );

        Route::post(
            '/coupons',
            [PlatformCouponController::class, 'store']
        );

        Route::get(
            '/coupons/{coupon}',
            [PlatformCouponController::class, 'show']
        )->whereNumber('coupon');

        Route::put(
            '/coupons/{coupon}',
            [PlatformCouponController::class, 'update']
        )->whereNumber('coupon');

        Route::patch(
            '/coupons/{coupon}/status',
            [PlatformCouponController::class, 'updateStatus']
        )->whereNumber('coupon');

        // Subscription price history

        Route::get(
            '/price-history',
            [PlatformSubscriptionHistoryController::class, 'index']
        );

        // Promotional redemptions

        Route::get(
            '/redemptions',
            [PlatformPromotionController::class, 'redemptions']
        );

        // Subscription usage

        Route::get(
            '/usage',
            [PlatformSubscriptionController::class, 'usage']
        );

    });

/*
|--------------------------------------------------------------------------
| Public Subscription Pricing
|--------------------------------------------------------------------------
*/

Route::get(
    '/platform/subscription-plans',
    [SubscriptionPricingController::class, 'index']
)->middleware('throttle:120,1');