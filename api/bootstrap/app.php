<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(

        web: __DIR__ . '/../routes/web.php',

        api: __DIR__ . '/../routes/api.php',

        commands: __DIR__ . '/../routes/console.php',

        health: '/up',
    )

    ->withMiddleware(
        function (Middleware $middleware): void {

            $middleware->alias([

                /*
                |--------------------------------------------------------------------------
                | Administrator Middleware
                |--------------------------------------------------------------------------
                */

                'admin' =>
                    \App\Http\Middleware\EnsureAdmin::class,

                /*
                |--------------------------------------------------------------------------
                | Seller Middleware
                |--------------------------------------------------------------------------
                */

                'seller' =>
                    \App\Http\Middleware\EnsureSeller::class,

                /*
                |--------------------------------------------------------------------------
                | Finance Middleware
                |--------------------------------------------------------------------------
                */

                'finance.permission' =>
                    \App\Http\Middleware\EnsureFinancePermission::class,

                'finance.audit' =>
                    \App\Http\Middleware\AuditFinanceOperation::class,

                /*
                |--------------------------------------------------------------------------
                | Communication Middleware
                |--------------------------------------------------------------------------
                */

                'communication.permission' =>
                    \App\Http\Middleware\EnsureCommunicationPermission::class,

            ]);
        }
    )

    ->withExceptions(
        function (Exceptions $exceptions): void {

            $exceptions->shouldRenderJsonWhen(

                fn (Request $request) =>

                    $request->is('api/*')
                    || $request->expectsJson(),
            );
        }
    )

    ->create();