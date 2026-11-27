<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PlatformFinanceWriteController;
use App\Http\Middleware\EnsureFinanceTestMode;

/*
|--------------------------------------------------------------------------
| IVISION Platform Finance Write API
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'admin',
    'throttle:30,1',
    'finance.permission',
    EnsureFinanceTestMode::class,
    'finance.audit',
])
    ->prefix('admin/finance')
    ->name('admin.finance.')
    ->controller(PlatformFinanceWriteController::class)
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Invoice Management
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/invoices',
            'createInvoice'
        )->name('invoices.store');

        Route::post(
            '/invoices/{id}/items',
            'addInvoiceItem'
        )
            ->whereNumber('id')
            ->name('invoices.items.store');

        Route::delete(
            '/invoices/{id}/items/{itemId}',
            'removeInvoiceItem'
        )
            ->whereNumber(['id', 'itemId'])
            ->name('invoices.items.destroy');

        Route::post(
            '/invoices/{id}/issue',
            'issueInvoice'
        )
            ->whereNumber('id')
            ->name('invoices.issue');

        Route::post(
            '/invoices/{id}/void',
            'voidInvoice'
        )
            ->whereNumber('id')
            ->name('invoices.void');

        /*
        |--------------------------------------------------------------------------
        | Expense Management
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/expenses',
            'createExpense'
        )->name('expenses.store');

        Route::put(
            '/expenses/{id}',
            'updateExpense'
        )
            ->whereNumber('id')
            ->name('expenses.update');

        Route::post(
            '/expenses/{id}/approve',
            'approveExpense'
        )
            ->whereNumber('id')
            ->name('expenses.approve');

        Route::post(
            '/expenses/{id}/reject',
            'rejectExpense'
        )
            ->whereNumber('id')
            ->name('expenses.reject');

        Route::post(
            '/expenses/{id}/mark-paid',
            'markExpensePaid'
        )
            ->whereNumber('id')
            ->name('expenses.mark-paid');

        Route::post(
            '/expenses/{id}/cancel',
            'cancelExpense'
        )
            ->whereNumber('id')
            ->name('expenses.cancel');

        /*
        |--------------------------------------------------------------------------
        | Credit Note Management
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/credit-notes',
            'createCreditNote'
        )->name('credit-notes.store');

        Route::post(
            '/credit-notes/{id}/issue',
            'issueCreditNote'
        )
            ->whereNumber('id')
            ->name('credit-notes.issue');

        Route::post(
            '/credit-notes/{id}/void',
            'voidCreditNote'
        )
            ->whereNumber('id')
            ->name('credit-notes.void');
    });