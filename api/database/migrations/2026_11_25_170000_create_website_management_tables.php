<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('slug', 150);
            $table->string('type', 50)->default('custom');
            $table->longText('content')->nullable();
            $table->json('draft_settings')->nullable();

            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();

            $table->boolean('is_published')->default(false);
            $table->boolean('show_in_header')->default(false);
            $table->boolean('show_in_footer')->default(false);

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'slug']);
            $table->index(['store_id', 'is_published']);
        });

        Schema::create('store_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->string('domain', 253)->unique();
            $table->string('verification_token', 100);
            $table->string('status', 30)->default('pending');
            $table->string('ssl_status', 30)->default('pending');
            $table->boolean('is_primary')->default(false);

            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();

            $table->timestamps();
            $table->index(['store_id', 'status']);
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->string('email');
            $table->string('name')->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('confirmation_token_hash', 64)
                ->nullable()->unique();
            $table->string('unsubscribe_token_hash', 64)
                ->nullable()->unique();

            $table->string('consent_version', 50);
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();

            $table->timestamps();

            $table->unique(['store_id', 'email']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('subject');
            $table->longText('content');
            $table->string('status', 30)->default('draft');

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_campaigns');
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('store_domains');
        Schema::dropIfExists('store_pages');
    }
};
