<?php

namespace Tests\Feature\Finance;

use App\Services\Finance\FinanceDashboardService;
use App\Services\Finance\PlatformTransactionService;
use App\Services\Finance\PlatformInvoiceService;

class FinanceModeIsolationTest extends FinanceTestCase
{
    public function test_transactions_are_separated_by_mode(): void
    {
        $test = $this->recordTransaction([
            'livemode' => false,
        ]);

        $live = $this->recordTransaction([
            'livemode' => true,
        ]);

        $service = app(
            PlatformTransactionService::class
        );

        $testRecords = $service->paginate([
            'livemode' => false,
        ]);

        $liveRecords = $service->paginate([
            'livemode' => true,
        ]);

        $this->assertSame(
            1,
            $testRecords->total()
        );

        $this->assertSame(
            1,
            $liveRecords->total()
        );

        $this->assertSame(
            $test->id,
            $testRecords->items()[0]->id
        );

        $this->assertSame(
            $live->id,
            $liveRecords->items()[0]->id
        );
    }

    public function test_default_mode_is_test_mode(): void
    {
        $this->recordTransaction([
            'livemode' => true,
            'amount_minor' => 100000,
        ]);

        $dashboard = app(
            FinanceDashboardService::class
        )->overview();

        $this->assertFalse(
            $dashboard['livemode']
        );

        $this->assertSame(
            0,
            $dashboard['revenue']['gross_revenue_minor']
        );
    }

    public function test_invoices_are_separated_by_mode(): void
    {
        $this->createDraftInvoice([
            'livemode' => false,
        ]);

        $this->createDraftInvoice([
            'livemode' => true,
        ]);

        $service = app(
            PlatformInvoiceService::class
        );

        $this->assertSame(
            1,
            $service->paginate([
                'livemode' => false,
            ])->total()
        );

        $this->assertSame(
            1,
            $service->paginate([
                'livemode' => true,
            ])->total()
        );
    }

    public function test_live_transaction_is_hidden_from_test_mode_api(): void
    {
        $admin = $this->createFinanceAdmin();

        $this->authenticate($admin);

        $transaction = $this->recordTransaction([
            'livemode' => true,
        ]);

        $this->getJson(
            $this->financeUrl(
                'transactions/'
                . $transaction->id
                . '?livemode=false'
            )
        )->assertNotFound();
    }

    public function test_live_invoice_is_hidden_from_test_mode_api(): void
    {
        $admin = $this->createFinanceAdmin();

        $this->authenticate($admin);

        $invoice = $this->createDraftInvoice([
            'livemode' => true,
        ]);

        $this->getJson(
            $this->financeUrl(
                'invoices/'
                . $invoice->id
                . '?livemode=false'
            )
        )->assertNotFound();
    }

    public function test_invalid_mode_filter_is_rejected(): void
    {
        $admin = $this->createFinanceAdmin();

        $this->authenticate($admin);

        $this->getJson(
            $this->financeUrl(
                'overview?livemode=invalid'
            )
        )->assertUnprocessable();
    }
}