<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->enum('type', ['percent', 'fixed']);
            $t->decimal('value', 10, 2);
            $t->decimal('min_subtotal', 10, 2)->default(0);
            $t->unsignedInteger('usage_limit')->nullable();
            $t->unsignedInteger('used_count')->default(0);
            $t->timestamp('expires_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('addresses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('full_name');
            $t->string('line1');
            $t->string('line2')->nullable();
            $t->string('city');
            $t->string('postcode', 20);
            $t->string('country', 2)->default('GB');
            $t->boolean('is_default')->default(false);
            $t->timestamps();
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('email');
            $t->enum('status', ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])->default('pending');
            $t->string('payment_method', 30)->default('card');
            $t->string('payment_status', 30)->default('unpaid');
            $t->decimal('subtotal', 10, 2);
            $t->decimal('discount', 10, 2)->default(0);
            $t->decimal('shipping', 10, 2)->default(0);
            $t->decimal('total', 10, 2);
            $t->string('coupon_code')->nullable();
            $t->json('shipping_address');
            $t->string('tracking_number')->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');           // snapshot at purchase time
            $t->unsignedSmallInteger('size_ml');
            $t->decimal('unit_price', 10, 2);
            $t->unsignedInteger('quantity');
            $t->decimal('line_total', 10, 2);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('coupons');
    }
};
