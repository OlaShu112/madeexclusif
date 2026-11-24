<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {

            $table->string('type', 100)->change();

            $table->string('tagline', 255)
                ->nullable()
                ->change();

        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Automatic rollback is disabled to prevent truncation of store data.'
        );
    }
};