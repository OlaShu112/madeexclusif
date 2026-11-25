<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_media', function (Blueprint $table) {

            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->string('name', 255);

            $table->string('original_name', 255);

            $table->string('disk', 50)
                ->default('public');

            $table->string('path', 500)
                ->unique();

            $table->string('mime_type', 100);

            $table->unsignedBigInteger('size');

            $table->unsignedInteger('width')
                ->nullable();

            $table->unsignedInteger('height')
                ->nullable();

            $table->string('alt_text', 255)
                ->nullable();

            $table->string('collection', 50)
                ->default('general');

            $table->timestamps();

            $table->index([
                'store_id',
                'collection',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_media');
    }
};