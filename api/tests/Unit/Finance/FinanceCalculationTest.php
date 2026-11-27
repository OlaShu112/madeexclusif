<?php

namespace Tests\Unit\Finance;

use PHPUnit\Framework\TestCase;

class FinanceCalculationTest extends TestCase
{
    public function test_minor_unit_addition(): void
    {
        $subtotal = 10000;

        $tax = 2000;

        $this->assertSame(
            12000,
            $subtotal + $tax
        );
    }

    public function test_discount_calculation(): void
    {
        $subtotal = 15000;

        $discount = 1000;

        $this->assertSame(
            14000,
            $subtotal - $discount
        );
    }

    public function test_exclusive_tax_calculation(): void
    {
        $subtotal = 10000;

        $basisPoints = 2000;

        $tax = intdiv(
            ($subtotal * $basisPoints) + 5000,
            10000
        );

        $this->assertSame(
            2000,
            $tax
        );
    }

    public function test_inclusive_tax_calculation(): void
    {
        $total = 12000;

        $basisPoints = 2000;

        $denominator = 10000 + $basisPoints;

        $tax = intdiv(
            ($total * $basisPoints)
                + intdiv($denominator, 2),
            $denominator
        );

        $this->assertSame(
            2000,
            $tax
        );
    }

    public function test_refund_reduces_net_revenue(): void
    {
        $revenue = 100000;

        $refund = 10000;

        $this->assertSame(
            90000,
            $revenue - $refund
        );
    }

    public function test_expenses_reduce_cash_surplus(): void
    {
        $revenue = 100000;

        $expenses = 25000;

        $fees = 5000;

        $this->assertSame(
            70000,
            $revenue - $expenses - $fees
        );
    }

    public function test_credit_note_reduces_outstanding_balance(): void
    {
        $invoiceTotal = 10000;

        $payments = 2000;

        $creditNotes = 3000;

        $balance = max(
            0,
            $invoiceTotal - $payments - $creditNotes
        );

        $this->assertSame(
            5000,
            $balance
        );
    }

    public function test_balance_cannot_be_negative(): void
    {
        $invoiceTotal = 10000;

        $payments = 12000;

        $this->assertSame(
            0,
            max(0, $invoiceTotal - $payments)
        );
    }
}