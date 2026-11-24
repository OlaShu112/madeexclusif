
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_accounts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('store_id')
                ->unique()
                ->constrained('stores')
                ->restrictOnDelete();

            $table->string('stripe_account_id')
                ->nullable()
                ->unique();

            $table->char('currency', 3)
                ->default('gbp');

            $table->string('status', 30)
                ->default('pending');

            $table->boolean('charges_enabled')
                ->default(false);

            $table->boolean('payouts_enabled')
                ->default(false);

            $table->boolean('details_submitted')
                ->default(false);

            $table->timestamp('onboarded_at')
                ->nullable();

            $table->timestamps();
        });

        Schema::create('commission_rules', function (Blueprint $table) {

            $table->id();

            $table->foreignId('store_id')
                ->nullable()
                ->constrained('stores')
                ->restrictOnDelete();

            $table->string('name');

            $table->decimal('percentage', 5, 2);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamp('starts_at')
                ->nullable();

            $table->timestamp('ends_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'store_id',
                'is_active',
            ]);
        });

        Schema::create('seller_payouts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('seller_account_id')
                ->constrained('seller_accounts')
                ->restrictOnDelete();

            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('reference')
                ->unique();

            $table->string('idempotency_key')
                ->unique();

            $table->string('stripe_transfer_id')
                ->nullable()
                ->unique();

            $table->string('stripe_payout_id')
                ->nullable()
                ->unique();

            $table->unsignedBigInteger('amount_minor');

            $table->char('currency', 3)
                ->default('gbp');

            $table->boolean('livemode')
                ->default(false);

            $table->string('status', 30)
                ->default('requested');

            $table->text('failure_reason')
                ->nullable();

            $table->timestamp('requested_at')
                ->nullable();

            $table->timestamp('approved_at')
                ->nullable();

            $table->timestamp('processed_at')
                ->nullable();

            $table->timestamp('paid_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'seller_account_id',
                'status',
            ]);
        });

        Schema::create('seller_transactions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('seller_account_id')
                ->constrained('seller_accounts')
                ->restrictOnDelete();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->restrictOnDelete();

            $table->foreignId('refund_id')
                ->nullable()
                ->constrained('refunds')
                ->restrictOnDelete();

            $table->foreignId('seller_payout_id')
                ->nullable()
                ->constrained('seller_payouts')
                ->restrictOnDelete();

            $table->string('reference')
                ->unique();

            $table->string('type', 30);

            $table->string('direction', 10);

            $table->unsignedBigInteger('amount_minor');

            $table->char('currency', 3)
                ->default('gbp');

            $table->string('status', 30)
                ->default('pending');

            $table->text('description')
                ->nullable();

            $table->timestamp('available_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'seller_account_id',
                'status',
            ]);

            $table->index([
                'seller_account_id',
                'type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_transactions');
        Schema::dropIfExists('seller_payouts');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('seller_accounts');
    }
};
