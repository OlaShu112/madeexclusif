<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Platform Tax Rates
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_tax_rates', function (Blueprint $table) {

            $table->id();

            $table->string('name');
            $table->string('country', 2)->default('GB');

            $table->decimal('percentage', 5, 2);

            $table->boolean('is_inclusive')->default(false);
            $table->boolean('is_active')->default(false);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();

            $table->index(['country', 'is_active']);
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Invoices
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_invoices', function (Blueprint $table) {

            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            $table->foreignId('store_subscription_id')
                ->nullable()
                ->constrained('store_subscriptions')
                ->restrictOnDelete();

            $table->string('invoice_number')->unique();

            $table->string('stripe_invoice_id')
                ->nullable()
                ->unique();

            $table->string('type', 50)
                ->default('subscription');

            $table->string('status', 30)
                ->default('draft');

            $table->char('currency', 3)
                ->default('gbp');

            $table->unsignedBigInteger('subtotal_minor')
                ->default(0);

            $table->unsignedBigInteger('discount_minor')
                ->default(0);

            $table->unsignedBigInteger('tax_minor')
                ->default(0);

            $table->unsignedBigInteger('total_minor')
                ->default(0);

            $table->unsignedBigInteger('amount_paid_minor')
                ->default(0);

            $table->unsignedBigInteger('amount_due_minor')
                ->default(0);

            $table->boolean('livemode')->default(false);

            $table->json('billing_details')->nullable();

            $table->text('notes')->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();

            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();

            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['status', 'issued_at']);
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Invoice Items
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_invoice_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('platform_invoice_id')
                ->constrained('platform_invoices')
                ->restrictOnDelete();

            $table->foreignId('platform_tax_rate_id')
                ->nullable()
                ->constrained('platform_tax_rates')
                ->restrictOnDelete();

            $table->string('type', 50)
                ->default('subscription');

            $table->string('description');

            $table->unsignedInteger('quantity')
                ->default(1);

            $table->unsignedBigInteger('unit_price_minor');

            $table->unsignedBigInteger('subtotal_minor');

            $table->unsignedBigInteger('discount_minor')
                ->default(0);

            $table->decimal('tax_percentage', 5, 2)
                ->default(0);

            $table->unsignedBigInteger('tax_minor')
                ->default(0);

            $table->unsignedBigInteger('total_minor');

            $table->json('metadata')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Subscription Payments
        |--------------------------------------------------------------------------
        */

        Schema::create('subscription_payments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            $table->foreignId('store_subscription_id')
                ->constrained('store_subscriptions')
                ->restrictOnDelete();

            $table->foreignId('platform_invoice_id')
                ->nullable()
                ->constrained('platform_invoices')
                ->restrictOnDelete();

            $table->string('reference')->unique();

            $table->string('idempotency_key')->unique();

            $table->string('provider', 50)
                ->default('stripe');

            $table->string('provider_payment_id')
                ->nullable();

            $table->string('stripe_payment_intent_id')
                ->nullable()
                ->unique();

            $table->unsignedBigInteger('amount_minor');

            $table->unsignedBigInteger('fee_minor')
                ->default(0);

            $table->unsignedBigInteger('refunded_minor')
                ->default(0);

            $table->char('currency', 3)
                ->default('gbp');

            $table->string('status', 30)
                ->default('pending');

            $table->string('payment_method', 50)
                ->nullable();

            $table->boolean('livemode')
                ->default(false);

            $table->text('failure_reason')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['provider', 'provider_payment_id'],
                'sub_payment_provider_unique'
            );

            $table->index(['store_id', 'status']);
            $table->index(['status', 'paid_at']);
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Expense Categories
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_expense_categories', function (Blueprint $table) {

            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Expenses
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_expenses', function (Blueprint $table) {

            $table->id();

            $table->foreignId('platform_expense_category_id')
                ->constrained('platform_expense_categories')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('reference')->unique();

            $table->string('title');

            $table->string('supplier')->nullable();

            $table->text('description')->nullable();

            $table->unsignedBigInteger('subtotal_minor');

            $table->unsignedBigInteger('tax_minor')
                ->default(0);

            $table->unsignedBigInteger('total_minor');

            $table->char('currency', 3)
                ->default('gbp');

            $table->string('status', 30)
                ->default('pending');

            $table->string('payment_method', 50)
                ->nullable();

            $table->string('receipt_path', 500)
                ->nullable();

            $table->boolean('is_recurring')
                ->default(false);

            $table->string('recurring_interval', 30)
                ->nullable();

            $table->date('expense_date');

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'expense_date']);
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Credit Notes
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_credit_notes', function (Blueprint $table) {

            $table->id();

            $table->foreignId('platform_invoice_id')
                ->constrained('platform_invoices')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('credit_note_number')->unique();

            $table->string('provider_credit_note_id')
                ->nullable()
                ->unique();

            $table->unsignedBigInteger('amount_minor');

            $table->unsignedBigInteger('tax_minor')
                ->default(0);

            $table->char('currency', 3)
                ->default('gbp');

            $table->string('status', 30)
                ->default('draft');

            $table->text('reason');

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Platform Financial Transactions
        |--------------------------------------------------------------------------
        */

        Schema::create('platform_transactions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('store_id')
                ->nullable()
                ->constrained('stores')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('reference')->unique();

            $table->string('idempotency_key')->unique();

            $table->string('type', 50);

            // credit or debit

            $table->string('direction', 10);

            $table->unsignedBigInteger('amount_minor');

            $table->char('currency', 3)
                ->default('gbp');

            $table->string('status', 30)
                ->default('pending');

            $table->string('source_type', 100)
                ->nullable();

            $table->unsignedBigInteger('source_id')
                ->nullable();

            $table->string('provider', 50)
                ->nullable();

            $table->string('provider_reference')
                ->nullable();

            $table->boolean('livemode')
                ->default(false);

            $table->text('description')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamp('occurred_at');

            $table->timestamp('reconciled_at')->nullable();

            $table->timestamps();

            $table->index(
                ['source_type', 'source_id'],
                'platform_tx_source_index'
            );

            $table->index(['type', 'status']);
            $table->index(['currency', 'occurred_at']);
            $table->index(['store_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_transactions');
        Schema::dropIfExists('platform_credit_notes');
        Schema::dropIfExists('platform_expenses');
        Schema::dropIfExists('platform_expense_categories');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('platform_invoice_items');
        Schema::dropIfExists('platform_invoices');
        Schema::dropIfExists('platform_tax_rates');
    }
};