
<?php

return [

    'currency' => 'gbp',

    'refunds' => [
        'enabled' => env('MARKETPLACE_REFUNDS_ENABLED', false),
        'allow_live' => false,
        'partial_refunds' => false,
    ],

    'commissions' => [
        'default_percentage' => 10,
    ],

    'payouts' => [
        'enabled' => env('MARKETPLACE_PAYOUTS_ENABLED', false),
        'allow_live' => false,
        'minimum_amount_minor' => 1000,
        'holding_period_days' => 14,
    ],

    'stripe_connect' => [
        'enabled' => env('STRIPE_CONNECT_ENABLED', false),
    ],

];
