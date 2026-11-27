<?php

namespace Tests\Feature\Finance;

use App\Services\Finance\FinancialReconciliationService;

class FinanceReconciliationTest extends FinanceTestCase
{
    private function service(): FinancialReconciliationService
    {
        return app(
            FinancialReconciliationService::class
        );
    }

    public function test_empty_reconciliation_has_no_discrepancies(): void
    {
        $audit = $this->service()->audit();

        $this->assertSame(
            0,
            $audit['total_discrepancies']
        );

        $this->assertSame(
            'passed',
            $audit['status']
        );
    }

    public function test_unreconciled_transactions_are_returned(): void
    {
        $transaction = $this->recordTransaction();

        $records = $this->service()->unreconciled([
            'currency' => 'gbp',
            'livemode' => false,
        ]);

        $this->assertSame(
            1,
            $records->total()
        );

        $this->assertSame(
            $transaction->id,
            $records->items()[0]->id
        );
    }

    public function test_reconciled_transactions_are_excluded(): void
    {
        $transaction = $this->recordTransaction();

        $transaction->update([
            'reconciled_at' => now(),
        ]);

        $records = $this->service()->unreconciled([
            'currency' => 'gbp',
            'livemode' => false,
        ]);

        $this->assertSame(
            0,
            $records->total()
        );
    }

    public function test_pending_transactions_are_excluded(): void
    {
        $this->recordTransaction([
            'status' => 'pending',
        ]);

        $records = $this->service()->unreconciled([
            'currency' => 'gbp',
            'livemode' => false,
        ]);

        $this->assertSame(
            0,
            $records->total()
        );
    }

    public function test_correct_invoice_has_no_discrepancies(): void
    {
        $this->createIssuedInvoice();

        $discrepancies =
            $this->service()->invoiceDiscrepancies();

        $this->assertEmpty(
            $discrepancies
        );
    }

    public function test_incorrect_invoice_balance_is_detected(): void
    {
        $invoice = $this->createIssuedInvoice();

        $invoice->update([
            'amount_due_minor' => 500,
        ]);

        $discrepancies =
            $this->service()->invoiceDiscrepancies();

        $this->assertNotEmpty(
            $discrepancies
        );
    }

    public function test_audit_requires_review_when_discrepancy_exists(): void
    {
        $invoice = $this->createIssuedInvoice();

        $invoice->update([
            'amount_due_minor' => 1,
        ]);

        $audit = $this->service()->audit();

        $this->assertSame(
            'review_required',
            $audit['status']
        );

        $this->assertGreaterThan(
            0,
            $audit['total_discrepancies']
        );
    }

    public function test_reconciliation_separates_modes(): void
    {
        $this->recordTransaction([
            'livemode' => false,
        ]);

        $this->recordTransaction([
            'livemode' => true,
        ]);

        $records = $this->service()->unreconciled([
            'currency' => 'gbp',
            'livemode' => false,
        ]);

        $this->assertSame(
            1,
            $records->total()
        );
    }
}