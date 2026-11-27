<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Finance Defaults
    |--------------------------------------------------------------------------
    */

    'currency' => 'gbp',

    'default_livemode' => false,

    'live_operations_enabled' => false,

    /*
    |--------------------------------------------------------------------------
    | Finance Permissions
    |--------------------------------------------------------------------------
    */

    'permissions' => [

        'finance.view',

        'finance.transactions.view',

        'finance.invoices.view',

        'finance.invoices.create',

        'finance.invoices.update',

        'finance.invoices.issue',

        'finance.invoices.void',

        'finance.payments.view',

        'finance.expenses.view',

        'finance.expenses.create',

        'finance.expenses.update',

        'finance.expenses.approve',

        'finance.expenses.pay',

        'finance.expenses.cancel',

        'finance.credit_notes.view',

        'finance.credit_notes.manage',

        'finance.commissions.view',

        'finance.commissions.manage',

        'finance.payouts.view',

        'finance.payouts.manage',

        'finance.refunds.view',

        'finance.refunds.manage',

        'finance.reports.view',

        'finance.reports.export',

        'finance.reconciliation.view',

        'finance.reconciliation.manage',

        'finance.tax.view',

        'finance.tax.manage',

        'finance.settings.manage',

        'finance.audit.view',

        'finance.permissions.manage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Permission Mapping
    |--------------------------------------------------------------------------
    */

    'route_permissions' => [

        // Dashboard

        'admin.finance.overview'
            => 'finance.view',

        'admin.finance.trends'
            => 'finance.view',

        // Transactions

        'admin.finance.transactions.index'
            => 'finance.transactions.view',

        'admin.finance.transactions.show'
            => 'finance.transactions.view',

        // Invoices

        'admin.finance.invoices.index'
            => 'finance.invoices.view',

        'admin.finance.invoices.show'
            => 'finance.invoices.view',

        'admin.finance.invoices.store'
            => 'finance.invoices.create',

        'admin.finance.invoices.items.store'
            => 'finance.invoices.update',

        'admin.finance.invoices.items.destroy'
            => 'finance.invoices.update',

        'admin.finance.invoices.issue'
            => 'finance.invoices.issue',

        'admin.finance.invoices.void'
            => 'finance.invoices.void',

        // Payments

        'admin.finance.payments.index'
            => 'finance.payments.view',

        'admin.finance.payments.show'
            => 'finance.payments.view',

        // Expenses

        'admin.finance.expenses.index'
            => 'finance.expenses.view',

        'admin.finance.expense-categories.index'
            => 'finance.expenses.view',

        'admin.finance.expenses.store'
            => 'finance.expenses.create',

        'admin.finance.expenses.update'
            => 'finance.expenses.update',

        'admin.finance.expenses.approve'
            => 'finance.expenses.approve',

        'admin.finance.expenses.reject'
            => 'finance.expenses.approve',

        'admin.finance.expenses.mark-paid'
            => 'finance.expenses.pay',

        'admin.finance.expenses.cancel'
            => 'finance.expenses.cancel',

        // Credit Notes

        'admin.finance.credit-notes.index'
            => 'finance.credit_notes.view',

        'admin.finance.credit-notes.show'
            => 'finance.credit_notes.view',

        'admin.finance.credit-notes.store'
            => 'finance.credit_notes.manage',

        'admin.finance.credit-notes.issue'
            => 'finance.credit_notes.manage',

        'admin.finance.credit-notes.void'
            => 'finance.credit_notes.manage',

        // Tax

        'admin.finance.tax-rates.index'
            => 'finance.tax.view',

        // Reports

        'admin.finance.reports.index'
            => 'finance.reports.view',

        'admin.finance.reports.export'
            => 'finance.reports.export',

        // Reconciliation

        'admin.finance.reconciliation.overview'
            => 'finance.reconciliation.view',

        'admin.finance.reconciliation.unreconciled'
            => 'finance.reconciliation.view',

        'admin.finance.reconciliation.audit'
            => 'finance.reconciliation.view',
    ],

    /*
    |--------------------------------------------------------------------------
    | Finance Permission Profiles
    |--------------------------------------------------------------------------
    |
    | These profiles are reusable permission collections.
    | They do not replace the existing users.role attribute.
    |
    */

    'profiles' => [

        'finance_manager' => [

            'finance.view',

            'finance.transactions.view',

            'finance.invoices.view',

            'finance.invoices.create',

            'finance.invoices.update',

            'finance.invoices.issue',

            'finance.invoices.void',

            'finance.payments.view',

            'finance.expenses.view',

            'finance.expenses.create',

            'finance.expenses.update',

            'finance.expenses.approve',

            'finance.expenses.pay',

            'finance.expenses.cancel',

            'finance.credit_notes.view',

            'finance.credit_notes.manage',

            'finance.commissions.view',

            'finance.payouts.view',

            'finance.refunds.view',

            'finance.reports.view',

            'finance.reports.export',

            'finance.reconciliation.view',

            'finance.reconciliation.manage',

            'finance.tax.view',

            'finance.audit.view',
        ],

        'finance_officer' => [

            'finance.view',

            'finance.transactions.view',

            'finance.invoices.view',

            'finance.invoices.create',

            'finance.invoices.update',

            'finance.payments.view',

            'finance.expenses.view',

            'finance.expenses.create',

            'finance.expenses.update',

            'finance.credit_notes.view',

            'finance.reports.view',

            'finance.tax.view',
        ],

        'financial_auditor' => [

            'finance.view',

            'finance.transactions.view',

            'finance.invoices.view',

            'finance.payments.view',

            'finance.expenses.view',

            'finance.credit_notes.view',

            'finance.commissions.view',

            'finance.payouts.view',

            'finance.refunds.view',

            'finance.reports.view',

            'finance.reports.export',

            'finance.reconciliation.view',

            'finance.tax.view',

            'finance.audit.view',
        ],

        'finance_read_only' => [

            'finance.view',

            'finance.invoices.view',

            'finance.payments.view',

            'finance.expenses.view',

            'finance.reports.view',
        ],
    ],
];