<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Communication\CommunicationOverviewController;
use App\Http\Controllers\Admin\Communication\CommunicationReadController;

/*
|--------------------------------------------------------------------------
| IVISION Platform Communication
|--------------------------------------------------------------------------
|
| Protected Communication administration endpoints.
|
*/

Route::middleware([

    'auth:sanctum',

    'admin',

    'communication.permission',

    'throttle:120,1',

])
    ->prefix('admin/communication')
    ->name('admin.communication.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Communication Overview
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/overview',
            [CommunicationOverviewController::class, 'index']
        )->name('overview');

        /*
        |--------------------------------------------------------------------------
        | Communications
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/messages',
            [CommunicationReadController::class, 'messages']
        )->name('messages.index');

        /*
        |--------------------------------------------------------------------------
        | Email Templates
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/templates',
            [CommunicationReadController::class, 'templates']
        )->name('templates.index');

        /*
        |--------------------------------------------------------------------------
        | Announcements
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/announcements',
            [CommunicationReadController::class, 'announcements']
        )->name('announcements.index');

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/notifications',
            [CommunicationReadController::class, 'notifications']
        )->name('notifications.index');

        /*
        |--------------------------------------------------------------------------
        | Recipients
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/recipients',
            [CommunicationReadController::class, 'recipients']
        )->name('recipients.index');

        /*
        |--------------------------------------------------------------------------
        | Delivery Logs
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/delivery',
            [CommunicationReadController::class, 'delivery']
        )->name('delivery.index');

        /*
        |--------------------------------------------------------------------------
        | Automation
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/automation',
            [CommunicationReadController::class, 'automation']
        )->name('automation.index');

        /*
        |--------------------------------------------------------------------------
        | Communication Preferences
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/preferences',
            [CommunicationReadController::class, 'preferences']
        )->name('preferences.index');

        /*
        |--------------------------------------------------------------------------
        | Communication Settings
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/settings',
            [CommunicationReadController::class, 'settings']
        )->name('settings.index');

        /*
        |--------------------------------------------------------------------------
        | Administrator Permissions
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/permissions',
            [CommunicationReadController::class, 'permissions']
        )->name('permissions.index');

    });