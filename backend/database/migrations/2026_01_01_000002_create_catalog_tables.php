<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('slug')->unique();
            $t->timestamps();
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('slug')->unique();
            $t->string('description')->nullable();
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $t->foreignId('category_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->string('notes_top')->nullable();
            $t->string('notes_heart')->nullable();
            $t->string('notes_base')->nullable();
            $t->enum('gender', ['women', 'men', 'unisex'])->default('unisex');
            $t->string('concentration', 30)->default('Eau de Parfum');
            $t->string('image')->nullable();
            $t->string('accent', 9)->default('#c9a86a'); // used by the SVG bottle placeholder
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['is_active', 'is_featured']);
        });

        Schema::create('product_variants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('sku')->unique();
            $t->unsignedSmallInteger('size_ml');
            $t->decimal('price', 10, 2);
            $t->unsignedInteger('stock')->default(0);
            $t->timestamps();
            $t->unique(['product_id', 'size_ml']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
    }
};
