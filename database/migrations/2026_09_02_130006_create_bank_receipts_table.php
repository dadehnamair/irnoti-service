<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A "فیش بانکی" the customer submits as proof of an offline transfer
 * (docs/starter.md §22). `receiptable` is the thing being paid — a WalletTopup,
 * Subscription, LineOrder, PackageOrder or Invoice; null means a plain wallet
 * top-up. On admin approval the matching domain effect runs once
 * ({@see App\Support\BankReceiptService}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('receiptable');
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('tracking_code');                  // شماره پیگیری / رهگیری
            $table->string('transfer_type')->default('paya'); // paya, satna, card, pol, cash
            $table->date('paid_at');                          // تاریخ واریز (entered in Jalali, stored Gregorian)
            $table->string('image_path')->nullable();         // private "local" disk
            $table->string('status')->default('pending');     // pending, approved, rejected
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_receipts');
    }
};
