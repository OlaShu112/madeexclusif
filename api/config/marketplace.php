<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Marketplace Information
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'IVISION'),

    'currency' => env('STRIPE_CURRENCY', 'gbp'),

    /*
    |--------------------------------------------------------------------------
    | Marketplace Email Addresses
    |--------------------------------------------------------------------------
    */

    'emails' => [

        'admin' => env('MARKETPLACE_ADMIN_EMAIL'),

        'support' => env('MARKETPLACE_SUPPORT_EMAIL'),

        'orders' => env('MARKETPLACE_ORDERS_EMAIL'),

        'sellers' => env('MARKETPLACE_SELLERS_EMAIL'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Email Processing
    |--------------------------------------------------------------------------
    */

    'email_processing' => [

        'queue' => env('MARKETPLACE_EMAIL_QUEUE', 'default'),

        'max_attempts' => 3,

        'retry_delays' => [
            60,
            300,
            900,
        ],

    ],

];