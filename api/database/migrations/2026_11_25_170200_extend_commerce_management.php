<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('status', 30)->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'user_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->json('attributes')->nullable();
            $table->decimal('weight_kg', 10, 3)->nullable();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->json('attributes')->nullable();
            $table->unsignedInteger('low_stock_threshold')->default(5);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('path', 500);
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('type', 30);
            $table->integer('quantity_change');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->string('reference')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
        });

        Schema::create('store_shipping_methods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('country', 2)->default('GB');
            $table->unsignedBigInteger('price_minor')->default(0);
            $table->unsignedBigInteger('free_above_minor')->nullable();

            $table->unsignedInteger('minimum_delivery_days')->nullable();
            $table->unsignedInteger('maximum_delivery_days')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->restrictOnDelete();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('reference')->unique();
            $table->string('status', 30)->default('requested');
            $table->text('reason');
            $table->text('seller_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_return_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_return_id')
                ->constrained('order_returns')
                ->cascadeOnDelete();

            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique([
                'order_return_id',
                'order_item_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_return_items');
        Schema::dropIfExists('order_returns');
        Schema::dropIfExists('store_shipping_methods');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('product_images');

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn([
                'attributes',
                'low_stock_threshold'
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'attributes',
                'weight_kg'
            ]);
        });

        Schema::dropIfExists('store_customers');
    }
};
