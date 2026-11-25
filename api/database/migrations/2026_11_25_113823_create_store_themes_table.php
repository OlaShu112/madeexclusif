<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_themes', function (Blueprint $table) {

            $table->id();

            $table->foreignId('store_id')
                ->unique()
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->string('primary_colour', 7)
                ->default('#222222');

            $table->string('secondary_colour', 7)
                ->default('#f5f5f5');

            $table->string('background_colour', 7)
                ->default('#ffffff');

            $table->string('text_colour', 7)
                ->default('#222222');

            $table->string('header_colour', 7)
                ->default('#222222');

            $table->string('footer_colour', 7)
                ->default('#222222');

            $table->text('footer_description')->nullable();

            $table->string('contact_email')->nullable();

            $table->string('contact_phone', 50)->nullable();

            $table->text('delivery_information')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_themes');
    }
};