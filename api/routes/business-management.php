<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Seller\StorePageController;
use App\Http\Controllers\Seller\BusinessAnalyticsController;
use App\Http\Controllers\Seller\BusinessCustomerController;
use App\Http\Controllers\StorePagePublicController;

Route::middleware(['auth:sanctum', 'seller'])
    ->prefix('seller')
    ->group(function () {
        Route::get(
            'website/pages',
            [StorePageController::class, 'index']
        );

        Route::post(
            'website/pages',
            [StorePageController::class, 'store']
        )->middleware('throttle:30,1');

        Route::get(
            'website/pages/{page}',
            [StorePageController::class, 'show']
        )->whereNumber('page');

        Route::put(
            'website/pages/{page}',
            [StorePageController::class, 'update']
        )->whereNumber('page')
         ->middleware('throttle:30,1');

        Route::post(
            'website/pages/{page}/publish',
            [StorePageController::class, 'publish']
        )->whereNumber('page')
         ->middleware('throttle:10,1');

        Route::post(
            'website/pages/{page}/unpublish',
            [StorePageController::class, 'unpublish']
        )->whereNumber('page')
         ->middleware('throttle:10,1');

        Route::delete(
            'website/pages/{page}',
            [StorePageController::class, 'destroy']
        )->whereNumber('page')
         ->middleware('throttle:10,1');

        Route::get(
            'business-analytics',
            [BusinessAnalyticsController::class, 'index']
        );

        Route::get(
            'customers',
            [BusinessCustomerController::class, 'index']
        );

        Route::get(
            'customers/{customer}',
            [BusinessCustomerController::class, 'show']
        )->whereNumber('customer');
    });

Route::get(
    'stores/{store}/pages',
    [StorePagePublicController::class, 'index']
);

Route::get(
    'stores/{store}/pages/{slug}',
    [StorePagePublicController::class, 'show']
);
