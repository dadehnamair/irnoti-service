<?php

use App\Models\SmsPackage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A purchase of an {@see SmsPackage}. Same shape as subscriptions /
 * line orders: unguessable `token` route key + snapshot columns + status
 * workflow. When it settles, users.sms_credit goes up by `sms_count`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_orders', function (Blueprint $table) {
            $table->id();
            $table->string('token', 24)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sms_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('package_name');
            $table->unsignedBigInteger('sms_count');
            $table->unsignedBigInteger('price');
            $table->string('status')->default('awaiting_payment'); // pending, awaiting_payment, paid, completed, cancelled
            $table->string('method')->nullable();                  // online | wallet | receipt
            $table->string('payment_driver')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('reference_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_orders');
    }
};
