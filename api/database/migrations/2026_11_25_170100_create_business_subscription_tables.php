<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->unsignedBigInteger('monthly_price_minor')->default(0);
            $table->unsignedBigInteger('annual_price_minor')->default(0);
            $table->string('currency', 3)->default('gbp');

            $table->string('stripe_monthly_price_id')->nullable();
            $table->string('stripe_annual_price_id')->nullable();

            $table->unsignedInteger('product_limit')->nullable();
            $table->unsignedBigInteger('storage_limit_bytes')->nullable();
            $table->unsignedInteger('ai_monthly_limit')->nullable();

            $table->json('features')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('store_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            $table->foreignId('subscription_plan_id')
                ->constrained('subscription_plans')
                ->restrictOnDelete();

            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable()->unique();

            $table->string('status', 30)->default('pending');
            $table->string('billing_interval', 20)->default('monthly');

            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->boolean('cancel_at_period_end')->default(false);

            $table->timestamps();
            $table->index(['store_id', 'status']);
        });

        Schema::create('store_usage_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->string('metric', 100);
            $table->date('period_start');
            $table->unsignedBigInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique([
                'store_id',
                'metric',
                'period_start'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_usage_records');
        Schema::dropIfExists('store_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
