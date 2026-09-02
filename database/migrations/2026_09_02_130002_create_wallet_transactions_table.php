<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable financial ledger (docs/starter.md §22 / §50). One row per balance
 * change, with the balance snapshot before/after. Rows are never updated or
 * deleted (enforced on the model). `idempotency_key` makes gateway callbacks and
 * receipt approvals safe to replay (docs/starter.md §25).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type');                 // topup, plan_purchase, line_purchase, package_purchase, invoice_payment, refund, adjustment
            $table->string('direction');            // credit | debit
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('balance_before');
            $table->unsignedBigInteger('balance_after');
            $table->nullableMorphs('reference');    // reference_type + reference_id
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
