<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'platform_admin_permissions',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->string('permission', 150);

                $table->foreignId('granted_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('granted_at')
                    ->useCurrent();

                $table->timestamp('expires_at')
                    ->nullable();

                $table->timestamps();

                $table->unique([
                    'user_id',
                    'permission',
                ]);

                $table->index([
                    'permission',
                    'expires_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'platform_admin_permissions'
        );
    }
};