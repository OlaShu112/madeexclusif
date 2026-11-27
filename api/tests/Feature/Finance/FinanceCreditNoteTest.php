<?php

namespace Tests\Feature\Finance;

use App\Services\Finance\PlatformCreditNoteService;
use Illuminate\Validation\ValidationException;

class FinanceCreditNoteTest extends FinanceTestCase
{
    private function service(): PlatformCreditNoteService
    {
        return app(
            PlatformCreditNoteService::class
        );
    }

    public function test_credit_note_can_be_created(): void
    {
        $invoice = $this->createIssuedInvoice();

        $admin = $this->createAdmin();

        $creditNote = $this->service()->createDraft(
            $invoice->id,
            [
                'amount_minor' => 2000,
                'tax_minor' => 0,
                'reason' => 'Service adjustment',
            ],
            $admin->id
        );

        $this->assertSame(
            'draft',
            $creditNote->status
        );

        $this->assertSame(
            2000,
            $creditNote->amount_minor
        );
    }

    public function test_issued_credit_note_reduces_invoice_balance(): void
    {
        $invoice = $this->createIssuedInvoice();

        $admin = $this->createAdmin();

        $creditNote = $this->service()->createDraft(
            $invoice->id,
            [
                'amount_minor' => 2500,
                'tax_minor' => 0,
                'reason' => 'Billing adjustment',
            ],
            $admin->id
        );

        $creditNote = $this->service()->issue(
            $creditNote->id
        );

        $this->assertSame(
            'issued',
            $creditNote->status
        );

        $this->assertSame(
            7500,
            $invoice->fresh()->amount_due_minor
        );

        $this->assertSame(
            0,
            $invoice->fresh()->amount_paid_minor
        );
    }

    public function test_credit_note_cannot_exceed_invoice_balance(): void
    {
        $invoice = $this->createIssuedInvoice();

        $admin = $this->createAdmin();

        $this->expectException(
            ValidationException::class
        );

        $this->service()->createDraft(
            $invoice->id,
            [
                'amount_minor' => 15000,
                'tax_minor' => 0,
                'reason' => 'Excessive credit',
            ],
            $admin->id
        );
    }

    public function test_credit_note_cannot_be_issued_twice(): void
    {
        $invoice = $this->createIssuedInvoice();

        $admin = $this->createAdmin();

        $creditNote = $this->service()->createDraft(
            $invoice->id,
            [
                'amount_minor' => 1000,
                'tax_minor' => 0,
                'reason' => 'Billing correction',
            ],
            $admin->id
        );

        $this->service()->issue(
            $creditNote->id
        );

        $this->service()->issue(
            $creditNote->id
        );

        $this->assertSame(
            9000,
            $invoice->fresh()->amount_due_minor
        );
    }

    public function test_draft_credit_note_can_be_voided(): void
    {
        $invoice = $this->createIssuedInvoice();

        $admin = $this->createAdmin();

        $creditNote = $this->service()->createDraft(
            $invoice->id,
            [
                'amount_minor' => 1000,
                'tax_minor' => 0,
                'reason' => 'Temporary adjustment',
            ],
            $admin->id
        );

        $creditNote = $this->service()->voidDraft(
            $creditNote->id
        );

        $this->assertSame(
            'void',
            $creditNote->status
        );

        $this->assertSame(
            10000,
            $invoice->fresh()->amount_due_minor
        );
    }

    public function test_issued_credit_note_cannot_be_voided_as_draft(): void
    {
        $invoice = $this->createIssuedInvoice();

        $admin = $this->createAdmin();

        $creditNote = $this->service()->createDraft(
            $invoice->id,
            [
                'amount_minor' => 1000,
                'tax_minor' => 0,
                'reason' => 'Billing correction',
            ],
            $admin->id
        );

        $this->service()->issue(
            $creditNote->id
        );

        $this->expectException(
            ValidationException::class
        );

        $this->service()->voidDraft(
            $creditNote->id
        );
    }
}