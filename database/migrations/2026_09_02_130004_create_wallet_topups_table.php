<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single "شارژ حساب" request (docs/starter.md §23). Mirrors line_orders /
 * subscriptions: unguessable `token` route key, its own payment columns, and a
 * status workflow. On success the wallet is credited once (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->string('token', 24)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('awaiting_payment'); // awaiting_payment, paid, cancelled
            $table->string('method')->nullable();                  // online | receipt
            $table->string('payment_driver')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('reference_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_topups');
    }
};
