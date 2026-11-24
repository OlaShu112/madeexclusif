<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->foreignId('store_id')
                ->nullable()
                ->constrained('stores')
                ->nullOnDelete();

            $table->string('notification_key')->unique();

            $table->string('recipient_email');

            $table->string('recipient_name')->nullable();

            $table->string('email_type', 100);

            $table->string('subject');

            $table->string('status', 30)
                ->default('pending');

            $table->unsignedInteger('attempts')
                ->default(0);

            $table->string('provider', 100)
                ->nullable();

            $table->string('provider_message_id')
                ->nullable();

            $table->text('error_message')
                ->nullable();

            $table->timestamp('queued_at')
                ->nullable();

            $table->timestamp('sent_at')
                ->nullable();

            $table->timestamp('delivered_at')
                ->nullable();

            $table->timestamp('failed_at')
                ->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);

            $table->index([
                'email_type',
                'created_at'
            ]);

            $table->index('recipient_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};