<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE stores
            MODIFY COLUMN status
            ENUM(
                'pending',
                'active',
                'suspended',
                'rejected'
            )
            NOT NULL DEFAULT 'pending'
        ");

        Schema::table('stores', function (Blueprint $table) {

            $table->timestamp('approved_at')
                ->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('rejected_at')
                ->nullable();

            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('rejection_reason')
                ->nullable();

            $table->timestamp('suspended_at')
                ->nullable();

            $table->foreignId('suspended_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('suspension_reason')
                ->nullable();

            $table->unsignedBigInteger('status_version')
                ->default(0);

            $table->index('status');
        });
    }

    public function down(): void
    {
        if (
            DB::table('stores')
                ->where('status', 'rejected')
                ->exists()
        ) {
            throw new \RuntimeException(
                'Cannot roll back while rejected stores exist.'
            );
        }

        Schema::table('stores', function (Blueprint $table) {

            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['suspended_by']);

            $table->dropIndex(['status']);

            $table->dropColumn([
                'approved_at',
                'approved_by',
                'rejected_at',
                'rejected_by',
                'rejection_reason',
                'suspended_at',
                'suspended_by',
                'suspension_reason',
                'status_version',
            ]);
        });

        DB::statement("
            ALTER TABLE stores
            MODIFY COLUMN status
            ENUM(
                'pending',
                'active',
                'suspended'
            )
            NOT NULL DEFAULT 'pending'
        ");
    }
};