<?php

namespace Tests\Feature\Finance;

use App\Services\Finance\FinanceDashboardService;

class FinanceDashboardTest extends FinanceTestCase
{
    public function test_empty_dashboard_returns_zero_totals(): void
    {
        $data = app(
            FinanceDashboardService::class
        )->overview();

        $this->assertSame(
            0,
            $data['revenue']['gross_revenue_minor']
        );

        $this->assertSame(
            0,
            $data['expenses']['total_expenses_minor']
        );

        $this->assertSame(
            0,
            $data['summary']['net_cash_movement_minor']
        );

        $this->assertSame(
            'GBP',
            $data['currency']
        );

        $this->assertFalse(
            $data['livemode']
        );
    }

    public function test_dashboard_calculates_financial_totals(): void
    {
        $this->recordTransaction([
            'type' => 'subscription_payment',
            'amount_minor' => 100000,
        ]);

        $this->recordTransaction([
            'type' => 'commission',
            'amount_minor' => 20000,
        ]);

        $this->recordTransaction([
            'type' => 'other_income',
            'amount_minor' => 10000,
        ]);

        $this->recordTransaction([
            'type' => 'subscription_refund',
            'direction' => 'debit',
            'amount_minor' => 5000,
        ]);

        $this->recordTransaction([
            'type' => 'expense',
            'direction' => 'debit',
            'amount_minor' => 30000,
        ]);

        $this->recordTransaction([
            'type' => 'processing_fee',
            'direction' => 'debit',
            'amount_minor' => 2000,
        ]);

        $data = app(
            FinanceDashboardService::class
        )->overview();

        $this->assertSame(
            130000,
            $data['revenue']['gross_revenue_minor']
        );

        $this->assertSame(
            125000,
            $data['revenue']['net_revenue_minor']
        );

        $this->assertSame(
            32000,
            $data['expenses']['total_expenses_minor']
        );

        $this->assertSame(
            93000,
            $data['summary']['net_cash_movement_minor']
        );

        $this->assertSame(
            93000,
            $data['summary']['operating_cash_surplus_minor']
        );

        $this->assertSame(
            6,
            $data['summary']['transaction_count']
        );
    }

    public function test_pending_transactions_are_excluded(): void
    {
        $this->recordTransaction([
            'status' => 'pending',
            'amount_minor' => 100000,
        ]);

        $data = app(
            FinanceDashboardService::class
        )->overview();

        $this->assertSame(
            0,
            $data['revenue']['gross_revenue_minor']
        );
    }

    public function test_dashboard_separates_currencies(): void
    {
        $this->recordTransaction([
            'currency' => 'gbp',
            'amount_minor' => 10000,
        ]);

        $this->recordTransaction([
            'currency' => 'usd',
            'amount_minor' => 50000,
        ]);

        $data = app(
            FinanceDashboardService::class
        )->overview('gbp');

        $this->assertSame(
            10000,
            $data['revenue']['gross_revenue_minor']
        );
    }

    public function test_dashboard_separates_financial_modes(): void
    {
        $this->recordTransaction([
            'livemode' => false,
            'amount_minor' => 10000,
        ]);

        $this->recordTransaction([
            'livemode' => true,
            'amount_minor' => 90000,
        ]);

        $service = app(
            FinanceDashboardService::class
        );

        $test = $service->overview(
            'gbp',
            false
        );

        $live = $service->overview(
            'gbp',
            true
        );

        $this->assertSame(
            10000,
            $test['revenue']['gross_revenue_minor']
        );

        $this->assertSame(
            90000,
            $live['revenue']['gross_revenue_minor']
        );
    }

    public function test_monthly_trends_return_requested_months(): void
    {
        $data = app(
            FinanceDashboardService::class
        )->monthlyTrend(
            'gbp',
            false,
            6
        );

        $this->assertCount(
            6,
            $data
        );
    }

    public function test_monthly_trends_include_completed_revenue(): void
    {
        $this->recordTransaction([
            'amount_minor' => 25000,
        ]);

        $data = app(
            FinanceDashboardService::class
        )->monthlyTrend(
            'gbp',
            false,
            1
        );

        $this->assertSame(
            25000,
            $data[0]['credits_minor']
        );

        $this->assertSame(
            25000,
            $data[0]['net_minor']
        );
    }
}