<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->string('stripe_session_id')->nullable()->unique()->after('tracking_number');
            $t->string('stripe_payment_intent')->nullable()->after('stripe_session_id');
            $t->timestamp('paid_at')->nullable()->after('stripe_payment_intent');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->dropColumn(['stripe_session_id', 'stripe_payment_intent', 'paid_at']);
        });
    }
};