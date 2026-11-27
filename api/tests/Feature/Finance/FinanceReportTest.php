<?php

namespace Tests\Feature\Finance;

use App\Services\Finance\FinancialReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceReportTest extends FinanceTestCase
{
    private function service(): FinancialReportService
    {
        return app(
            FinancialReportService::class
        );
    }

    public function test_report_calculates_revenue(): void
    {
        $this->recordTransaction([
            'amount_minor' => 50000,
        ]);

        $report = $this->service()->summary(
            now()->startOfMonth()->toDateString(),
            now()->toDateString(),
            'gbp',
            false
        );

        $this->assertSame(
            50000,
            $report['revenue']['gross_revenue_minor']
        );
    }

    public function test_report_excludes_transactions_outside_period(): void
    {
        $this->recordTransaction([
            'amount_minor' => 50000,
            'occurred_at' => now()->subMonths(2),
        ]);

        $this->recordTransaction([
            'amount_minor' => 10000,
        ]);

        $report = $this->service()->summary(
            now()->startOfMonth()->toDateString(),
            now()->toDateString(),
            'gbp',
            false
        );

        $this->assertSame(
            10000,
            $report['revenue']['gross_revenue_minor']
        );
    }

    public function test_transaction_breakdown_is_generated(): void
    {
        $this->recordTransaction([
            'type' => 'subscription_payment',
            'amount_minor' => 10000,
        ]);

        $this->recordTransaction([
            'type' => 'commission',
            'amount_minor' => 2000,
        ]);

        $breakdown =
            $this->service()->transactionBreakdown(
                now()->startOfMonth()->toDateString(),
                now()->toDateString(),
                'gbp',
                false
            );

        $this->assertCount(
            2,
            $breakdown
        );
    }

    public function test_csv_export_returns_streamed_response(): void
    {
        $this->recordTransaction();

        $response = $this->service()->exportTransactions(
            now()->startOfMonth()->toDateString(),
            now()->toDateString(),
            'gbp',
            false
        );

        $this->assertInstanceOf(
            StreamedResponse::class,
            $response
        );
    }

    public function test_csv_contains_transaction_reference(): void
    {
        $transaction = $this->recordTransaction();

        $response = $this->service()->exportTransactions(
            now()->startOfMonth()->toDateString(),
            now()->toDateString(),
            'gbp',
            false
        );

        ob_start();

        try {

            $response->sendContent();

            $csv = ob_get_contents();

        } finally {

            ob_end_clean();
        }

        $this->assertStringContainsString(
            $transaction->reference,
            $csv
        );

        $this->assertStringContainsString(
            'Reference',
            $csv
        );
    }

    public function test_csv_export_excludes_live_transactions(): void
    {
        $test = $this->recordTransaction([
            'livemode' => false,
        ]);

        $live = $this->recordTransaction([
            'livemode' => true,
        ]);

        $response = $this->service()->exportTransactions(
            now()->startOfMonth()->toDateString(),
            now()->toDateString(),
            'gbp',
            false
        );

        ob_start();

        try {

            $response->sendContent();

            $csv = ob_get_contents();

        } finally {

            ob_end_clean();
        }

        $this->assertStringContainsString(
            $test->reference,
            $csv
        );

        $this->assertStringNotContainsString(
            $live->reference,
            $csv
        );
    }

    public function test_csv_formula_injection_is_prevented(): void
    {
        $this->recordTransaction([
            'description' => '=1+1',
        ]);

        $response = $this->service()->exportTransactions(
            now()->startOfMonth()->toDateString(),
            now()->toDateString(),
            'gbp',
            false
        );

        ob_start();

        try {

            $response->sendContent();

            $csv = ob_get_contents();

        } finally {

            ob_end_clean();
        }

        $this->assertStringContainsString(
            "'=1+1",
            $csv
        );
    }

    public function test_invalid_report_date_range_is_rejected(): void
    {
        $admin = $this->createFinanceAdmin();

        $this->authenticate($admin);

        $this->getJson(
            $this->financeUrl(
                'reports?from=2026-11-30&to=2026-11-01'
            )
        )->assertUnprocessable();
    }
}