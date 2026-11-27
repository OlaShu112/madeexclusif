<?php

namespace Tests\Feature\Finance;

use App\Models\PlatformTaxRate;
use App\Services\Finance\PlatformInvoiceService;
use Illuminate\Validation\ValidationException;

class FinanceInvoiceTest extends FinanceTestCase
{
    private function service(): PlatformInvoiceService
    {
        return app(
            PlatformInvoiceService::class
        );
    }

    public function test_draft_invoice_can_be_created(): void
    {
        $invoice = $this->createDraftInvoice();

        $this->assertSame(
            'draft',
            $invoice->status
        );

        $this->assertSame(
            0,
            $invoice->total_minor
        );

        $this->assertDatabaseHas(
            'platform_invoices',
            [
                'id' => $invoice->id,
                'status' => 'draft',
                'currency' => 'gbp',
            ]
        );
    }

    public function test_invoice_numbers_are_unique(): void
    {
        $first = $this->createDraftInvoice();

        $second = $this->createDraftInvoice();

        $this->assertNotSame(
            $first->invoice_number,
            $second->invoice_number
        );
    }

    public function test_invoice_item_calculation(): void
    {
        $invoice = $this->createDraftInvoice();

        $invoice = $this->service()->addItem(
            $invoice->id,
            [
                'type' => 'service',
                'description' => 'Platform services',
                'quantity' => 3,
                'unit_price_minor' => 5000,
                'discount_minor' => 1000,
            ]
        );

        $this->assertSame(
            15000,
            $invoice->subtotal_minor
        );

        $this->assertSame(
            1000,
            $invoice->discount_minor
        );

        $this->assertSame(
            14000,
            $invoice->total_minor
        );
    }

    public function test_exclusive_tax_calculation(): void
    {
        $tax = PlatformTaxRate::create([
            'name' => 'Test VAT 20%',
            'country' => 'GB',
            'percentage' => 20,
            'is_inclusive' => false,
            'is_active' => true,
        ]);

        $invoice = $this->createDraftInvoice();

        $invoice = $this->service()->addItem(
            $invoice->id,
            [
                'type' => 'service',
                'description' => 'Taxable service',
                'quantity' => 1,
                'unit_price_minor' => 10000,
                'platform_tax_rate_id' => $tax->id,
            ]
        );

        $this->assertSame(
            2000,
            $invoice->tax_minor
        );

        $this->assertSame(
            12000,
            $invoice->total_minor
        );
    }

    public function test_inclusive_tax_calculation(): void
    {
        $tax = PlatformTaxRate::create([
            'name' => 'Inclusive VAT',
            'country' => 'GB',
            'percentage' => 20,
            'is_inclusive' => true,
            'is_active' => true,
        ]);

        $invoice = $this->createDraftInvoice();

        $invoice = $this->service()->addItem(
            $invoice->id,
            [
                'type' => 'service',
                'description' => 'Tax inclusive service',
                'quantity' => 1,
                'unit_price_minor' => 12000,
                'platform_tax_rate_id' => $tax->id,
            ]
        );

        $this->assertSame(
            2000,
            $invoice->tax_minor
        );

        $this->assertSame(
            12000,
            $invoice->total_minor
        );
    }

    public function test_discount_cannot_exceed_item_value(): void
    {
        $invoice = $this->createDraftInvoice();

        $this->expectException(
            ValidationException::class
        );

        $this->service()->addItem(
            $invoice->id,
            [
                'type' => 'service',
                'description' => 'Invalid discount',
                'quantity' => 1,
                'unit_price_minor' => 1000,
                'discount_minor' => 2000,
            ]
        );
    }

    public function test_invoice_item_can_be_removed(): void
    {
        $invoice = $this->createDraftInvoice();

        $this->service()->addItem(
            $invoice->id,
            [
                'type' => 'service',
                'description' => 'Temporary item',
                'quantity' => 1,
                'unit_price_minor' => 5000,
            ]
        );

        $item = $invoice->items()->firstOrFail();

        $invoice = $this->service()->removeItem(
            $invoice->id,
            $item->id
        );

        $this->assertSame(
            0,
            $invoice->total_minor
        );

        $this->assertDatabaseMissing(
            'platform_invoice_items',
            [
                'id' => $item->id,
            ]
        );
    }

    public function test_invoice_can_be_issued(): void
    {
        $invoice = $this->createIssuedInvoice();

        $this->assertSame(
            'open',
            $invoice->status
        );

        $this->assertSame(
            10000,
            $invoice->amount_due_minor
        );

        $this->assertNotNull(
            $invoice->issued_at
        );
    }

    public function test_empty_invoice_cannot_be_issued(): void
    {
        $invoice = $this->createDraftInvoice();

        $this->expectException(
            ValidationException::class
        );

        $this->service()->issue(
            $invoice->id
        );
    }

    public function test_issued_invoice_cannot_be_modified(): void
    {
        $invoice = $this->createIssuedInvoice();

        $this->expectException(
            ValidationException::class
        );

        $this->service()->addItem(
            $invoice->id,
            [
                'type' => 'service',
                'description' => 'Unauthorised modification',
                'quantity' => 1,
                'unit_price_minor' => 1000,
            ]
        );
    }

    public function test_unpaid_invoice_can_be_voided(): void
    {
        $invoice = $this->createIssuedInvoice();

        $invoice = $this->service()->void(
            $invoice->id
        );

        $this->assertSame(
            'void',
            $invoice->status
        );

        $this->assertSame(
            0,
            $invoice->amount_due_minor
        );

        $this->assertNotNull(
            $invoice->voided_at
        );
    }

    public function test_paid_invoice_cannot_be_voided(): void
    {
        $invoice = $this->createIssuedInvoice();

        $invoice->update([
            'status' => 'paid',
            'amount_paid_minor' => 10000,
            'amount_due_minor' => 0,
            'paid_at' => now(),
        ]);

        $this->expectException(
            ValidationException::class
        );

        $this->service()->void(
            $invoice->id
        );
    }
}