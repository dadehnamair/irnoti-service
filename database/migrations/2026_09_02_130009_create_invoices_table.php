<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A billing document the admin issues for a customer (docs/starter.md §22 / §51).
 * Customer pays it with any method — online, wallet balance, or a bank receipt —
 * then the admin's confirmation (automatic for online/wallet) marks it paid.
 * `token` is the unguessable route key; `number` is the human-facing id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('token', 24)->unique();
            $table->string('number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('status')->default('draft'); // draft, issued, awaiting_payment, paid, cancelled
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->text('description')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->date('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
