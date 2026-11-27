<?php

namespace Tests\Feature\Finance;

use App\Models\User;

class FinanceAuthenticationTest extends FinanceTestCase
{
    public function test_guest_cannot_access_finance_dashboard(): void
    {
        $this->getJson(
            $this->financeUrl('overview')
        )->assertUnauthorized();
    }

    public function test_guest_cannot_access_transactions(): void
    {
        $this->getJson(
            $this->financeUrl('transactions')
        )->assertUnauthorized();
    }

    public function test_guest_cannot_access_invoices(): void
    {
        $this->getJson(
            $this->financeUrl('invoices')
        )->assertUnauthorized();
    }

    public function test_guest_cannot_access_payments(): void
    {
        $this->getJson(
            $this->financeUrl('payments')
        )->assertUnauthorized();
    }

    public function test_guest_cannot_access_expenses(): void
    {
        $this->getJson(
            $this->financeUrl('expenses')
        )->assertUnauthorized();
    }

    public function test_guest_cannot_access_credit_notes(): void
    {
        $this->getJson(
            $this->financeUrl('credit-notes')
        )->assertUnauthorized();
    }

    public function test_guest_cannot_access_reports(): void
    {
        $this->getJson(
            $this->financeUrl('reports')
        )->assertUnauthorized();
    }

    public function test_guest_cannot_access_reconciliation(): void
    {
        $this->getJson(
            $this->financeUrl('reconciliation')
        )->assertUnauthorized();
    }

    public function test_guest_cannot_create_expense(): void
    {
        $this->postJson(
            $this->financeUrl('expenses'),
            []
        )->assertUnauthorized();
    }

    public function test_guest_cannot_create_invoice(): void
    {
        $this->postJson(
            $this->financeUrl('invoices'),
            []
        )->assertUnauthorized();
    }

    public function test_guest_cannot_export_financial_reports(): void
    {
        $this->getJson(
            $this->financeUrl('reports/export')
        )->assertUnauthorized();
    }

    public function test_non_admin_cannot_access_finance(): void
    {
        $user = User::factory()->create();

        $this->assertNotSame(
            'admin',
            $user->fresh()->role
        );

        $this->authenticate($user);

        $this->getJson(
            $this->financeUrl('overview')
        )->assertForbidden();
    }

    public function test_non_admin_cannot_create_expense(): void
    {
        $user = User::factory()->create();

        $this->assertNotSame(
            'admin',
            $user->fresh()->role
        );

        $this->authenticate($user);

        $this->postJson(
            $this->financeUrl('expenses'),
            []
        )->assertForbidden();
    }
}