<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Delivery receipts for sent SMS (docs/starter.md §12/§14 "Delivery"). After a
 * message leaves the panel we poll the provider's GetDelivery2 on a schedule
 * until the outcome is final; these columns hold that outcome so a settled
 * message is never polled again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            // pending | delivered | undelivered | failed | unknown  (null = not polled yet)
            $table->string('delivery_status')->nullable()->after('status');
            $table->string('delivery_code', 8)->nullable()->after('delivery_status'); // raw provider code
            $table->timestamp('delivery_checked_at')->nullable()->after('delivery_code');

            $table->index(['status', 'delivery_status']);
        });
    }

    public function down(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropIndex(['status', 'delivery_status']);
            $table->dropColumn(['delivery_status', 'delivery_code', 'delivery_checked_at']);
        });
    }
};
