<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name')->unique();
            $t->string('slug')->unique();
            $t->string('type', 20)->default('clothing');   // perfume | clothing
            $t->string('tagline', 140)->nullable();
            $t->string('logo')->nullable();
            $t->string('accent', 9)->default('#1a1a1a');
            $t->boolean('is_main')->default(false);
            $t->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $t->timestamps();
        });

        Schema::table('categories', function (Blueprint $t) {
            $t->string('type', 20)->default('perfume')->after('slug');
        });

        Schema::table('products', function (Blueprint $t) {
            $t->foreignId('store_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $t) {
            $t->foreignId('store_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        // Clothing variants have no millilitres; they are described by a label such as "M · Black".
        Schema::table('product_variants', function (Blueprint $t) {
            $t->unsignedSmallInteger('size_ml')->nullable()->change();
        });
        Schema::table('product_variants', function (Blueprint $t) {
            $t->string('size_label', 60)->nullable()->after('size_ml');
        });

        Schema::table('order_items', function (Blueprint $t) {
            $t->unsignedSmallInteger('size_ml')->nullable()->change();
        });
        Schema::table('order_items', function (Blueprint $t) {
            $t->string('variant_label', 80)->nullable()->after('size_ml');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn('variant_label'));
        Schema::table('product_variants', fn (Blueprint $t) => $t->dropColumn('size_label'));
        Schema::table('orders', fn (Blueprint $t) => $t->dropConstrainedForeignId('store_id'));
        Schema::table('products', fn (Blueprint $t) => $t->dropConstrainedForeignId('store_id'));
        Schema::table('categories', fn (Blueprint $t) => $t->dropColumn('type'));
        Schema::dropIfExists('stores');
    }
};