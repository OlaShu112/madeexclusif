<?php

namespace Tests\Feature\Finance;

use App\Models\PlatformTransaction;
use Illuminate\Validation\ValidationException;

class FinanceTransactionTest extends FinanceTestCase
{
    public function test_transaction_can_be_recorded(): void
    {
        $transaction = $this->recordTransaction();

        $this->assertDatabaseHas(
            'platform_transactions',
            [
                'id' => $transaction->id,
                'amount_minor' => 10000,
                'currency' => 'gbp',
                'direction' => 'credit',
                'status' => 'completed',
                'livemode' => false,
            ]
        );
    }

    public function test_transaction_currency_is_normalised(): void
    {
        $transaction = $this->recordTransaction([
            'currency' => 'GBP',
        ]);

        $this->assertSame(
            'gbp',
            $transaction->currency
        );
    }

    public function test_duplicate_transaction_is_not_created(): void
    {
        $attributes = [

            'idempotency_key' =>
                'finance-duplicate-test',

            'amount_minor' => 15000,
        ];

        $first = $this->recordTransaction(
            $attributes
        );

        $second = $this->recordTransaction(
            $attributes
        );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertSame(
            1,
            PlatformTransaction::query()
                ->where(
                    'idempotency_key',
                    'finance-duplicate-test'
                )
                ->count()
        );
    }

    public function test_conflicting_duplicate_is_rejected(): void
    {
        $this->recordTransaction([

            'idempotency_key' =>
                'finance-conflict-test',

            'amount_minor' => 10000,
        ]);

        $this->expectException(
            ValidationException::class
        );

        $this->recordTransaction([

            'idempotency_key' =>
                'finance-conflict-test',

            'amount_minor' => 20000,
        ]);
    }

    public function test_negative_transaction_is_rejected(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->recordTransaction([
            'amount_minor' => -1000,
        ]);
    }

    public function test_zero_transaction_is_rejected(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->recordTransaction([
            'amount_minor' => 0,
        ]);
    }

    public function test_invalid_credit_direction_is_rejected(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->recordTransaction([

            'type' => 'subscription_payment',

            'direction' => 'debit',
        ]);
    }

    public function test_invalid_expense_direction_is_rejected(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->recordTransaction([

            'type' => 'expense',

            'direction' => 'credit',
        ]);
    }

    public function test_expense_debit_can_be_recorded(): void
    {
        $transaction = $this->recordTransaction([

            'type' => 'expense',

            'direction' => 'debit',

            'amount_minor' => 2500,
        ]);

        $this->assertSame(
            'debit',
            $transaction->direction
        );

        $this->assertSame(
            2500,
            $transaction->amount_minor
        );
    }

    public function test_invalid_currency_is_rejected(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->recordTransaction([
            'currency' => 'INVALID',
        ]);
    }
}