<?php

namespace Tests\Feature\Finance;

use App\Models\PlatformExpense;
use App\Services\Finance\PlatformExpenseService;
use Illuminate\Validation\ValidationException;

class FinanceExpenseTest extends FinanceTestCase
{
    private function service(): PlatformExpenseService
    {
        return app(
            PlatformExpenseService::class
        );
    }

    private function expense(
        array $attributes = []
    ): PlatformExpense {

        $admin = $this->createAdmin();

        $category = $this->createExpenseCategory();

        return $this->service()->create(
            array_merge([
                'platform_expense_category_id' =>
                    $category->id,

                'title' => 'Cloud infrastructure',

                'supplier' => 'Test Hosting Provider',

                'subtotal_minor' => 10000,

                'tax_minor' => 2000,

                'currency' => 'gbp',

                'expense_date' => now()->toDateString(),

                'is_recurring' => false,

                'livemode' => false,

            ], $attributes),

            $admin->id
        );
    }

    public function test_expense_can_be_created(): void
    {
        $expense = $this->expense();

        $this->assertSame(
            'pending',
            $expense->status
        );

        $this->assertSame(
            12000,
            $expense->total_minor
        );
    }

    public function test_expense_can_be_approved(): void
    {
        $expense = $this->expense();

        $admin = $this->createAdmin();

        $expense = $this->service()->approve(
            $expense->id,
            $admin->id
        );

        $this->assertSame(
            'approved',
            $expense->status
        );

        $this->assertSame(
            $admin->id,
            $expense->approved_by
        );

        $this->assertNotNull(
            $expense->approved_at
        );
    }

    public function test_expense_can_be_rejected(): void
    {
        $expense = $this->expense();

        $expense = $this->service()->reject(
            $expense->id,
            'Insufficient supporting documentation.'
        );

        $this->assertSame(
            'rejected',
            $expense->status
        );
    }

    public function test_pending_expense_can_be_cancelled(): void
    {
        $expense = $this->expense();

        $expense = $this->service()->cancel(
            $expense->id
        );

        $this->assertSame(
            'cancelled',
            $expense->status
        );
    }

    public function test_approved_expense_can_be_marked_paid(): void
    {
        $expense = $this->expense();

        $admin = $this->createAdmin();

        $this->service()->approve(
            $expense->id,
            $admin->id
        );

        $expense = $this->service()->markAsPaid(
            $expense->id,
            $admin->id,
            'bank_transfer',
            false
        );

        $this->assertSame(
            'paid',
            $expense->status
        );

        $this->assertNotNull(
            $expense->paid_at
        );

        $this->assertDatabaseHas(
            'platform_transactions',
            [
                'source_type' => 'platform_expense',
                'source_id' => $expense->id,
                'type' => 'expense',
                'direction' => 'debit',
                'amount_minor' => 12000,
                'status' => 'completed',
                'livemode' => false,
            ]
        );
    }

    public function test_pending_expense_cannot_be_marked_paid(): void
    {
        $expense = $this->expense();

        $admin = $this->createAdmin();

        $this->expectException(
            ValidationException::class
        );

        $this->service()->markAsPaid(
            $expense->id,
            $admin->id,
            'bank_transfer',
            false
        );
    }

    public function test_duplicate_expense_payment_does_not_duplicate_ledger(): void
    {
        $expense = $this->expense();

        $admin = $this->createAdmin();

        $this->service()->approve(
            $expense->id,
            $admin->id
        );

        for ($i = 0; $i < 2; $i++) {

            $this->service()->markAsPaid(
                $expense->id,
                $admin->id,
                'bank_transfer',
                false
            );
        }

        $this->assertSame(
            1,
            \App\Models\PlatformTransaction::query()
                ->where(
                    'source_type',
                    'platform_expense'
                )
                ->where(
                    'source_id',
                    $expense->id
                )
                ->where('type', 'expense')
                ->count()
        );
    }

    public function test_paid_expense_cannot_be_cancelled(): void
    {
        $expense = $this->expense();

        $admin = $this->createAdmin();

        $this->service()->approve(
            $expense->id,
            $admin->id
        );

        $this->service()->markAsPaid(
            $expense->id,
            $admin->id,
            'bank_transfer',
            false
        );

        $this->expectException(
            ValidationException::class
        );

        $this->service()->cancel(
            $expense->id
        );
    }

    public function test_negative_expense_is_rejected(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->expense([
            'subtotal_minor' => -1000,
        ]);
    }

    public function test_inactive_category_is_rejected(): void
    {
        $category = $this->createExpenseCategory([
            'is_active' => false,
        ]);

        $this->expectException(
            ValidationException::class
        );

        $this->expense([
            'platform_expense_category_id' =>
                $category->id,
        ]);
    }
}