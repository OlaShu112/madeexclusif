<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Finance\FinanceFilterRequest;

use App\Models\PlatformTaxRate;

use App\Services\Finance\FinanceDashboardService;
use App\Services\Finance\PlatformTransactionService;
use App\Services\Finance\PlatformInvoiceService;
use App\Services\Finance\SubscriptionPaymentService;
use App\Services\Finance\PlatformExpenseService;
use App\Services\Finance\PlatformCreditNoteService;
use App\Services\Finance\FinancialReportService;
use App\Services\Finance\FinancialReconciliationService;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformFinanceController extends Controller
{
    public function __construct(

        private FinanceDashboardService $dashboard,

        private PlatformTransactionService $transactions,

        private PlatformInvoiceService $invoices,

        private SubscriptionPaymentService $payments,

        private PlatformExpenseService $expenses,

        private PlatformCreditNoteService $creditNotes,

        private FinancialReportService $reports,

        private FinancialReconciliationService $reconciliation

    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Finance Dashboard
    |--------------------------------------------------------------------------
    */

    public function overview(
        FinanceFilterRequest $request
    ): JsonResponse {

        $filters = $request->validated();

        [$from, $to] = $this->period($filters);

        return response()->json([

            'data' => $this->dashboard->overview(

                $filters['currency'] ?? 'gbp',

                $filters['livemode'] ?? false,

                $from,

                $to
            ),
        ]);
    }

    public function trends(
        FinanceFilterRequest $request
    ): JsonResponse {

        $filters = $request->validated();

        return response()->json([

            'data' => $this->dashboard->monthlyTrend(

                $filters['currency'] ?? 'gbp',

                $filters['livemode'] ?? false,

                $filters['months'] ?? 12
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Financial Transactions
    |--------------------------------------------------------------------------
    */

    public function transactions(
        FinanceFilterRequest $request
    ): JsonResponse {

        return response()->json(

            $this->transactions->paginate(
                $request->validated()
            )
        );
    }

    public function showTransaction(
        FinanceFilterRequest $request,
        int $id
    ): JsonResponse {

        $transaction = $this->transactions->find($id);

        $this->ensureMode(
            $transaction->livemode,
            $request
        );

        return response()->json([
            'data' => $transaction,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Platform Invoices
    |--------------------------------------------------------------------------
    */

    public function invoices(
        FinanceFilterRequest $request
    ): JsonResponse {

        return response()->json(

            $this->invoices->paginate(
                $request->validated()
            )
        );
    }

    public function showInvoice(
        FinanceFilterRequest $request,
        int $id
    ): JsonResponse {

        $invoice = $this->invoices->find($id);

        $this->ensureMode(
            $invoice->livemode,
            $request
        );

        return response()->json([
            'data' => $invoice,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Subscription Payments
    |--------------------------------------------------------------------------
    */

    public function payments(
        FinanceFilterRequest $request
    ): JsonResponse {

        return response()->json(

            $this->payments->paginate(
                $request->validated()
            )
        );
    }

    public function showPayment(
        FinanceFilterRequest $request,
        int $id
    ): JsonResponse {

        $payment = $this->payments->find($id);

        $this->ensureMode(
            $payment->livemode,
            $request
        );

        return response()->json([
            'data' => $payment,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Expense Management
    |--------------------------------------------------------------------------
    */

    public function expenses(
        FinanceFilterRequest $request
    ): JsonResponse {

        return response()->json(

            $this->expenses->paginate(
                $request->validated()
            )
        );
    }

    public function expenseCategories(): JsonResponse
    {
        return response()->json([

            'data' => $this->expenses->categories(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Credit Notes
    |--------------------------------------------------------------------------
    */

    public function creditNotes(
        FinanceFilterRequest $request
    ): JsonResponse {

        return response()->json(

            $this->creditNotes->paginate(
                $request->validated()
            )
        );
    }

    public function showCreditNote(
        FinanceFilterRequest $request,
        int $id
    ): JsonResponse {

        $creditNote = $this->creditNotes->find($id);

        $invoice = $creditNote->invoice()->firstOrFail();

        $this->ensureMode(
            $invoice->livemode,
            $request
        );

        return response()->json([
            'data' => $creditNote,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Tax Configuration
    |--------------------------------------------------------------------------
    */

    public function taxRates(): JsonResponse
    {
        $rates = PlatformTaxRate::query()
            ->orderBy('country')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $rates,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Financial Reports
    |--------------------------------------------------------------------------
    */

    public function reports(
        FinanceFilterRequest $request
    ): JsonResponse {

        $filters = $request->validated();

        [$from, $to] = $this->period($filters);

        $currency = $filters['currency'] ?? 'gbp';

        $livemode = $filters['livemode'] ?? false;

        return response()->json([

            'data' => [

                'summary' => $this->reports->summary(

                    $from,

                    $to,

                    $currency,

                    $livemode
                ),

                'transaction_breakdown' =>
                    $this->reports->transactionBreakdown(

                        $from,

                        $to,

                        $currency,

                        $livemode
                    ),
            ],
        ]);
    }

    public function exportTransactions(
        FinanceFilterRequest $request
    ): StreamedResponse {

        $filters = $request->validated();

        [$from, $to] = $this->period($filters);

        return $this->reports->exportTransactions(

            $from,

            $to,

            $filters['currency'] ?? 'gbp',

            $filters['livemode'] ?? false
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Financial Reconciliation
    |--------------------------------------------------------------------------
    */

    public function reconciliation(
        FinanceFilterRequest $request
    ): JsonResponse {

        $filters = $request->validated();

        return response()->json([

            'data' => $this->reconciliation->overview(

                $filters['currency'] ?? 'gbp',

                $filters['livemode'] ?? false
            ),
        ]);
    }

    public function unreconciledTransactions(
        FinanceFilterRequest $request
    ): JsonResponse {

        return response()->json(

            $this->reconciliation->unreconciled(
                $request->validated()
            )
        );
    }

    public function reconciliationAudit(
        FinanceFilterRequest $request
    ): JsonResponse {

        $filters = $request->validated();

        return response()->json([

            'data' => $this->reconciliation->audit(

                $filters['currency'] ?? 'gbp',

                $filters['livemode'] ?? false
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Helpers
    |--------------------------------------------------------------------------
    */

    private function period(array $filters): array
    {
        $from = $filters['from']
            ?? now()->startOfMonth()->toDateString();

        $to = $filters['to']
            ?? now()->toDateString();

        if ($from > $to) {

            throw ValidationException::withMessages([

                'to' =>
                    'The end date must be on or after the start date.',
            ]);
        }

        return [$from, $to];
    }

    private function ensureMode(
        bool $livemode,
        FinanceFilterRequest $request
    ): void {

        $requestedMode =
            $request->validated()['livemode'] ?? false;

        abort_unless(
            $livemode === (bool) $requestedMode,
            404
        );
    }
}