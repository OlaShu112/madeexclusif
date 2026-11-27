<?php

namespace Tests\Feature\Finance;

use App\Models\PlatformInvoice;
use App\Models\PlatformExpense;
use App\Models\PlatformTransaction;

class FinanceWriteApiTest extends FinanceTestCase
{
    /*
    |--------------------------------------------------------------------------
    | Invoice Lifecycle
    |--------------------------------------------------------------------------
    */

    public function test_invoice_http_lifecycle(): void
    {
        $admin = $this->createFinanceAdmin();

        $this->authenticate($admin);

        $store = $this->createStore();

        $response = $this->postJson(
            $this->financeUrl('invoices'),
            [
                'store_id' => $store->id,
                'type' => 'service',
                'currency' => 'gbp',
                'livemode' => false,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $invoiceId = $response->json('data.id');

        $this->postJson(
            $this->financeUrl(
                "invoices/{$invoiceId}/items"
            ),
            [
                'type' => 'service',
                'description' => 'Platform service',
                'quantity' => 2,
                'unit_price_minor' => 5000,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.total_minor',
                10000
            );

        $this->postJson(
            $this->financeUrl(
                "invoices/{$invoiceId}/issue"
            )
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'open');

        $this->getJson(
            $this->financeUrl(
                "invoices/{$invoiceId}"
            )
        )
            ->assertOk()
            ->assertJsonPath(
                'data.amount_due_minor',
                10000
            );

        $this->assertDatabaseHas(
            'platform_invoices',
            [
                'id' => $invoiceId,
                'status' => 'open',
                'total_minor' => 10000,
                'livemode' => false,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Expense Lifecycle
    |--------------------------------------------------------------------------
    */

    public function test_expense_http_lifecycle(): void
    {
        $creator = $this->createFinanceAdmin();

        $approver = $this->createFinanceAdmin();

        $category = $this->createExpenseCategory();

        $this->authenticate($creator);

        $response = $this->postJson(
            $this->financeUrl('expenses'),
            [
                'platform_expense_category_id' =>
                    $category->id,

                'title' => 'Cloud hosting',

                'subtotal_minor' => 5000,

                'tax_minor' => 1000,

                'currency' => 'gbp',

                'livemode' => false,

                'is_recurring' => false,

                'expense_date' =>
                    now()->toDateString(),
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                'pending'
            )
            ->assertJsonPath(
                'data.total_minor',
                6000
            );

        $expenseId = $response->json('data.id');

        $this->authenticate($approver);

        $this->postJson(
            $this->financeUrl(
                "expenses/{$expenseId}/approve"
            )
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'approved'
            );

        $this->postJson(
            $this->financeUrl(
                "expenses/{$expenseId}/mark-paid"
            ),
            [
                'payment_method' => 'bank_transfer',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'paid'
            );

        $this->assertDatabaseHas(
            'platform_transactions',
            [
                'source_type' => 'platform_expense',
                'source_id' => $expenseId,
                'type' => 'expense',
                'direction' => 'debit',
                'amount_minor' => 6000,
                'status' => 'completed',
                'livemode' => false,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Credit Note Lifecycle
    |--------------------------------------------------------------------------
    */

    public function test_credit_note_http_lifecycle(): void
    {
        $admin = $this->createFinanceAdmin();

        $this->authenticate($admin);

        $invoice = $this->createIssuedInvoice(10000);

        $response = $this->postJson(
            $this->financeUrl('credit-notes'),
            [
                'platform_invoice_id' =>
                    $invoice->id,

                'amount_minor' => 2000,

                'tax_minor' => 0,

                'reason' =>
                    'Platform service adjustment',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                'draft'
            );

        $creditNoteId = $response->json('data.id');

        $this->postJson(
            $this->financeUrl(
                "credit-notes/{$creditNoteId}/issue"
            )
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'issued'
            );

        $this->assertDatabaseHas(
            'platform_invoices',
            [
                'id' => $invoice->id,
                'amount_due_minor' => 8000,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Permission Protection
    |--------------------------------------------------------------------------
    */

    public function test_admin_without_write_permission_is_denied(): void
    {
        $admin = $this->createAdmin();

        $this->grantPermission(
            $admin,
            'finance.view'
        );

        $this->authenticate($admin);

        $this->postJson(
            $this->financeUrl('invoices'),
            []
        )->assertForbidden();

        $this->postJson(
            $this->financeUrl('expenses'),
            []
        )->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Live Mode Protection
    |--------------------------------------------------------------------------
    */

    public function test_live_invoice_creation_is_blocked(): void
    {
        $admin = $this->createFinanceAdmin();

        $this->authenticate($admin);

        $store = $this->createStore();

        $this->postJson(
            $this->financeUrl('invoices'),
            [
                'store_id' => $store->id,
                'type' => 'service',
                'currency' => 'gbp',
                'livemode' => true,
            ]
        )->assertForbidden();
    }

    public function test_live_invoice_cannot_be_modified(): void
    {
        $admin = $this->createFinanceAdmin();

        $this->authenticate($admin);

        $invoice = $this->createDraftInvoice([
            'livemode' => true,
        ]);

        $this->postJson(
            $this->financeUrl(
                "invoices/{$invoice->id}/items"
            ),
            [
                'type' => 'service',
                'description' => 'Test item',
                'quantity' => 1,
                'unit_price_minor' => 1000,
            ]
        )->assertNotFound();
    }

    public function test_live_mode_parameter_is_rejected_on_action(): void
    {
        $admin = $this->createFinanceAdmin();

        $this->authenticate($admin);

        $invoice = $this->createDraftInvoice();

        $this->postJson(
            $this->financeUrl(
                "invoices/{$invoice->id}/issue"
            ),
            [
                'livemode' => true,
            ]
        )->assertForbidden();

        $this->assertSame(
            'draft',
            $invoice->fresh()->status
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    */

    public function test_successful_write_creates_audit_log(): void
    {
        $admin = $this->createFinanceAdmin();

        $this->authenticate($admin);

        $store = $this->createStore();

        $before = $this->financeAuditCount();

        $this->postJson(
            $this->financeUrl('invoices'),
            [
                'store_id' => $store->id,
                'type' => 'service',
                'currency' => 'gbp',
                'livemode' => false,
            ]
        )->assertCreated();

        $this->assertGreaterThan(
            $before,
            $this->financeAuditCount()
        );
    }

    public function test_invalid_write_does_not_create_invoice(): void
    {
        $admin = $this->createFinanceAdmin();

        $this->authenticate($admin);

        $before = PlatformInvoice::count();

        $this->postJson(
            $this->financeUrl('invoices'),
            []
        )->assertUnprocessable();

        $this->assertSame(
            $before,
            PlatformInvoice::count()
        );
    }
}