<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe Publishable Key
    |--------------------------------------------------------------------------
    |
    | Used by the frontend when Stripe.js is required.
    | This key starts with pk_test_ or pk_live_.
    |
    */

    'key' => env('STRIPE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Secret API Key
    |--------------------------------------------------------------------------
    |
    | Used by the Laravel backend to communicate with Stripe.
    |
    | Examples:
    | sk_test_...
    | sk_live_...
    |
    | A restricted key (rk_test_ / rk_live_) may also be used
    | if it has the necessary API permissions.
    |
    | Never expose this key to the frontend.
    |
    */

    'secret' => env('STRIPE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Webhook Signing Secret
    |--------------------------------------------------------------------------
    |
    | Used to verify webhook events received from Stripe.
    |
    | This is different from your Stripe API secret key.
    | Webhook signing secrets begin with whsec_.
    |
    */

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Frontend URL
    |--------------------------------------------------------------------------
    |
    | Stripe redirects customers here after payment
    | succeeds or when they cancel checkout.
    |
    | Local Vue frontend:
    | http://localhost:5173
    |
    */

    'frontend_url' => rtrim(
        (string) env(
            'FRONTEND_URL',
            'http://localhost:5173'
        ),
        '/'
    ),

    /*
    |--------------------------------------------------------------------------
    | Stripe Currency
    |--------------------------------------------------------------------------
    |
    | Made Exclusif currently accepts GBP payments.
    |
    */

    'currency' => strtolower(
        (string) env('STRIPE_CURRENCY', 'gbp')
    ),

];
