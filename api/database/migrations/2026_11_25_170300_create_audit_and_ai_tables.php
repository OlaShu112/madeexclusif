<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->nullable()
                ->constrained('stores')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('action', 100);
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('reference', 100)->unique();
            $table->string('title')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->index(['store_id', 'user_id']);
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ai_conversation_id')
                ->constrained('ai_conversations')
                ->cascadeOnDelete();

            $table->string('role', 20);
            $table->longText('content');
            $table->string('model', 100)->nullable();
            $table->json('sources')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_knowledge_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('source_type', 50);
            $table->string('source_reference', 500)->nullable();
            $table->longText('content')->nullable();
            $table->string('index_status', 30)->default('pending');
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'index_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_documents');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('audit_logs');
    }
};
