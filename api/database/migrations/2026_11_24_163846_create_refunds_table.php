
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {

            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->restrictOnDelete();

            $table->foreignId('store_id')
                ->nullable()
                ->constrained('stores')
                ->nullOnDelete();

            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('reference', 100)
                ->unique();

            $table->string('idempotency_key', 150)
                ->unique();

            $table->string('stripe_refund_id')
                ->nullable()
                ->unique();

            $table->string('stripe_payment_intent');

            $table->boolean('livemode')
                ->default(false);

            $table->unsignedBigInteger('amount_minor');

            $table->char('currency', 3)
                ->default('gbp');

            $table->string('type', 20)
                ->default('full');

            $table->string('status', 30)
                ->default('requested');

            $table->text('reason');

            $table->text('admin_notes')
                ->nullable();

            $table->string('failure_reason')
                ->nullable();

            $table->text('error_message')
                ->nullable();

            $table->timestamp('requested_at')
                ->nullable();

            $table->timestamp('submitted_at')
                ->nullable();

            $table->timestamp('succeeded_at')
                ->nullable();

            $table->timestamp('failed_at')
                ->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);

            $table->index(['store_id', 'status']);

            $table->index(['status', 'created_at']);

            $table->index('stripe_payment_intent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
