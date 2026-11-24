<?php

namespace App\Services;

use Stripe\StripeClient;
use RuntimeException;

class StripeFinancialService
{
    public function client(): StripeClient
    {
        $secret = config('stripe.secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException(
                'Stripe credentials are not configured.'
            );
        }

        return new StripeClient($secret);
    }

    public function assertExecutionAllowed(
        string $operation
    ): void {

        if (! in_array(
            $operation,
            ['refunds', 'payouts', 'stripe_connect'],
            true
        )) {
            throw new RuntimeException(
                'Unsupported financial operation.'
            );
        }

        if (! config("financial.{$operation}.enabled")) {
            throw new RuntimeException(
                'Financial execution is disabled.'
            );
        }

        $secret = config('stripe.secret');

        if (
            ! is_string($secret) ||
            ! str_starts_with($secret, 'sk_test_')
        ) {
            throw new RuntimeException(
                'Financial execution requires Stripe test credentials.'
            );
        }
    }
}
