<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\PlatformFinanceController;

/*
|--------------------------------------------------------------------------
| IVISION Platform Finance Read API
|--------------------------------------------------------------------------
*/

Route::middleware([

    'auth:sanctum',

    'admin',

    'throttle:120,1',

    'finance.permission',

])
    ->prefix('admin/finance')
    ->name('admin.finance.')
    ->controller(PlatformFinanceController::class)
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/overview',
            'overview'
        )->name('overview');

        Route::get(
            '/trends',
            'trends'
        )->name('trends');

        /*
        |--------------------------------------------------------------------------
        | Transactions
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/transactions',
            'transactions'
        )->name('transactions.index');

        Route::get(
            '/transactions/{id}',
            'showTransaction'
        )
            ->whereNumber('id')
            ->name('transactions.show');

        /*
        |--------------------------------------------------------------------------
        | Invoices
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/invoices',
            'invoices'
        )->name('invoices.index');

        Route::get(
            '/invoices/{id}',
            'showInvoice'
        )
            ->whereNumber('id')
            ->name('invoices.show');

        /*
        |--------------------------------------------------------------------------
        | Subscription Payments
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/payments',
            'payments'
        )->name('payments.index');

        Route::get(
            '/payments/{id}',
            'showPayment'
        )
            ->whereNumber('id')
            ->name('payments.show');

        /*
        |--------------------------------------------------------------------------
        | Expenses
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/expenses',
            'expenses'
        )->name('expenses.index');

        Route::get(
            '/expense-categories',
            'expenseCategories'
        )->name('expense-categories.index');

        /*
        |--------------------------------------------------------------------------
        | Credit Notes
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/credit-notes',
            'creditNotes'
        )->name('credit-notes.index');

        Route::get(
            '/credit-notes/{id}',
            'showCreditNote'
        )
            ->whereNumber('id')
            ->name('credit-notes.show');

        /*
        |--------------------------------------------------------------------------
        | Tax Rates
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/tax-rates',
            'taxRates'
        )->name('tax-rates.index');

        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/reports',
            'reports'
        )->name('reports.index');

        Route::get(
            '/reports/export',
            'exportTransactions'
        )
            ->middleware('throttle:10,1')
            ->name('reports.export');

        /*
        |--------------------------------------------------------------------------
        | Reconciliation
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/reconciliation',
            'reconciliation'
        )->name('reconciliation.overview');

        Route::get(
            '/reconciliation/unreconciled',
            'unreconciledTransactions'
        )->name('reconciliation.unreconciled');

        Route::get(
            '/reconciliation/audit',
            'reconciliationAudit'
        )
            ->middleware('throttle:10,1')
            ->name('reconciliation.audit');
    });