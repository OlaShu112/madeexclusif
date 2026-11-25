<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'store_payment_settings',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('store_id')
                    ->unique()
                    ->constrained('stores')
                    ->cascadeOnDelete();

                $table->string('payment_mode', 30)
                    ->default('not_configured');

                $table->string('provider', 50)
                    ->nullable();

                $table->string('status', 30)
                    ->default('pending_setup');

                $table->boolean('checkout_enabled')
                    ->default(false);

                $table->timestamp('preference_changed_at')
                    ->nullable();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('store_payment_settings');
    }
};