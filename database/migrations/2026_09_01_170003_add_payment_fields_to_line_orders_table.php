<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online payment support for line orders (shetabit/multipay). The gateway
 * transaction id is stored on `purchase`, the bank reference id + paid_at on a
 * successful `verify` in the callback — see docs/starter.md §11.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('line_orders', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->after('status');
            $table->string('reference_id')->nullable()->after('transaction_id');
            $table->string('payment_driver')->nullable()->after('reference_id');
            $table->timestamp('paid_at')->nullable()->after('payment_driver');

            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('line_orders', function (Blueprint $table) {
            $table->dropIndex(['transaction_id']);
            $table->dropColumn(['transaction_id', 'reference_id', 'payment_driver', 'paid_at']);
        });
    }
};
