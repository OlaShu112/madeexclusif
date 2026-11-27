<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\PlatformInvoice;
use App\Models\PlatformCreditNote;

use App\Services\Finance\PlatformInvoiceService;
use App\Services\Finance\PlatformExpenseService;
use App\Services\Finance\PlatformCreditNoteService;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PlatformFinanceWriteController extends Controller
{
    public function __construct(

        private PlatformInvoiceService $invoices,

        private PlatformExpenseService $expenses,

        private PlatformCreditNoteService $creditNotes

    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Invoice Management
    |--------------------------------------------------------------------------
    */

    public function createInvoice(
        Request $request
    ): JsonResponse {

        $invoice = $this->invoices->createDraft(
            $this->testData($request)
        );

        return response()->json([

            'message' =>
                'Draft invoice created successfully.',

            'data' => $invoice,

        ], 201);
    }

    public function addInvoiceItem(
        Request $request,
        int $id
    ): JsonResponse {

        $invoice = $this->withTestInvoice(
            $id,
            fn () => $this->invoices->addItem(
                $id,
                $this->testData($request)
            )
        );

        return response()->json([

            'message' =>
                'Invoice item added successfully.',

            'data' => $invoice,
        ]);
    }

    public function removeInvoiceItem(
        int $id,
        int $itemId
    ): JsonResponse {

        $invoice = $this->withTestInvoice(
            $id,
            fn () => $this->invoices->removeItem(
                $id,
                $itemId
            )
        );

        return response()->json([

            'message' =>
                'Invoice item removed successfully.',

            'data' => $invoice,
        ]);
    }

    public function issueInvoice(
        int $id
    ): JsonResponse {

        $invoice = $this->withTestInvoice(
            $id,
            fn () => $this->invoices->issue($id)
        );

        return response()->json([

            'message' =>
                'Invoice issued successfully.',

            'data' => $invoice,
        ]);
    }

    public function voidInvoice(
        int $id
    ): JsonResponse {

        $invoice = $this->withTestInvoice(
            $id,
            fn () => $this->invoices->void($id)
        );

        return response()->json([

            'message' =>
                'Invoice voided successfully.',

            'data' => $invoice,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Expense Management
    |--------------------------------------------------------------------------
    */

    public function createExpense(
        Request $request
    ): JsonResponse {

        $expense = $this->expenses->create(

            $this->testData($request),

            (int) $request->user()->id
        );

        return response()->json([

            'message' =>
                'Expense created successfully.',

            'data' => $expense,

        ], 201);
    }

    public function updateExpense(
        Request $request,
        int $id
    ): JsonResponse {

        $expense = $this->expenses->update(

            $id,

            $this->testData($request),

            false
        );

        return response()->json([

            'message' =>
                'Expense updated successfully.',

            'data' => $expense,
        ]);
    }

    public function approveExpense(
        Request $request,
        int $id
    ): JsonResponse {

        $expense = $this->expenses->approve(

            $id,

            (int) $request->user()->id,

            false
        );

        return response()->json([

            'message' =>
                'Expense approved successfully.',

            'data' => $expense,
        ]);
    }

    public function rejectExpense(
        Request $request,
        int $id
    ): JsonResponse {

        $validated = $request->validate([

            'reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $expense = $this->expenses->reject(

            $id,

            $validated['reason'],

            false
        );

        return response()->json([

            'message' =>
                'Expense rejected successfully.',

            'data' => $expense,
        ]);
    }

    public function markExpensePaid(
        Request $request,
        int $id
    ): JsonResponse {

        $validated = $request->validate([

            'payment_method' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $expense = $this->expenses->markAsPaid(

            $id,

            (int) $request->user()->id,

            $validated['payment_method'],

            false
        );

        return response()->json([

            'message' =>
                'Expense payment recorded successfully.',

            'data' => $expense,
        ]);
    }

    public function cancelExpense(
        int $id
    ): JsonResponse {

        $expense = $this->expenses->cancel(
            $id,
            false
        );

        return response()->json([

            'message' =>
                'Expense cancelled successfully.',

            'data' => $expense,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Credit Note Management
    |--------------------------------------------------------------------------
    */

    public function createCreditNote(
        Request $request
    ): JsonResponse {

        $validated = $request->validate([

            'platform_invoice_id' => [
                'required',
                'integer',
                'exists:platform_invoices,id',
            ],
        ]);

        $invoiceId =
            (int) $validated['platform_invoice_id'];

        $creditNote = $this->withTestInvoice(

            $invoiceId,

            fn () => $this->creditNotes->createDraft(

                $invoiceId,

                $this->testData($request),

                (int) $request->user()->id
            )
        );

        return response()->json([

            'message' =>
                'Draft credit note created successfully.',

            'data' => $creditNote,

        ], 201);
    }

    public function issueCreditNote(
        int $id
    ): JsonResponse {

        $creditNote = PlatformCreditNote::findOrFail(
            $id
        );

        $result = $this->withTestInvoice(

            (int) $creditNote->platform_invoice_id,

            fn () => $this->creditNotes->issue($id)
        );

        return response()->json([

            'message' =>
                'Credit note issued successfully.',

            'data' => $result,
        ]);
    }

    public function voidCreditNote(
        int $id
    ): JsonResponse {

        $creditNote = PlatformCreditNote::findOrFail(
            $id
        );

        $result = $this->withTestInvoice(

            (int) $creditNote->platform_invoice_id,

            fn () => $this->creditNotes->voidDraft($id)
        );

        return response()->json([

            'message' =>
                'Credit note voided successfully.',

            'data' => $result,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Test Mode Safety
    |--------------------------------------------------------------------------
    */

    private function testData(
        Request $request
    ): array {

        $request->validate([
            'livemode' => [
                'sometimes',
                'boolean',
            ],
        ]);

        abort_if(
            $request->boolean('livemode'),
            403,
            'Live financial operations are not enabled.'
        );

        return array_merge(
            $request->all(),
            ['livemode' => false]
        );
    }

    private function withTestInvoice(
        int $id,
        callable $callback
    ): mixed {

        return DB::transaction(
            function () use ($id, $callback) {

                PlatformInvoice::query()
                    ->where('livemode', false)
                    ->lockForUpdate()
                    ->findOrFail($id);

                return $callback();

            },
            3
        );
    }
}