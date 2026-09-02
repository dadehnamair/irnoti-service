<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan purchases (docs/starter.md §8 / §51). Mirrors line_orders: a free plan
 * activates instantly, a paid one goes through shetabit/multipay and is verified
 * in the callback. Public status page is keyed by `token`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('token', 32)->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();

            // Snapshot of the plan at purchase time (survives plan edits/deletes).
            $table->string('plan_name');
            $table->string('billing_period')->default('monthly'); // monthly | yearly
            $table->unsignedBigInteger('price')->default(0);

            // pending | awaiting_payment | paid | active | cancelled | expired
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();

            $table->string('transaction_id')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('payment_driver')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
