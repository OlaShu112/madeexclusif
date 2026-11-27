<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'platform_expenses',
            function (Blueprint $table) {

                $table->boolean('livemode')
                    ->default(false)
                    ->after('currency');

                $table->index(
                    ['livemode', 'currency', 'status'],
                    'platform_expenses_mode_currency_status_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'platform_expenses',
            function (Blueprint $table) {

                $table->dropIndex(
                    'platform_expenses_mode_currency_status_idx'
                );

                $table->dropColumn('livemode');
            }
        );
    }
};