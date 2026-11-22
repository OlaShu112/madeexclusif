<?php

return [
    // Secret key (sk_test_... / sk_live_...). Server-side only - never expose to the browser.
    'secret' => env('STRIPE_SECRET'),

    // Signing secret (whsec_...) used to verify that webhook calls really come from Stripe.
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    // Where Stripe sends the customer back to after paying or cancelling.
    'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/'),

    'currency' => 'gbp',
];