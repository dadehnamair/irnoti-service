<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One bulk dispatch to a messenger network — بله / ایتا / واتساپ
 * (docs/starter.md §91). The row is created "queued" with the wallet already
 * debited (recipients_count * tariff); SendMessengerCampaignJob fills the
 * success/failed counts, moves the status, and refunds the failed portion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('channel', 20);              // bale | eitaa | whatsapp
            $table->text('body');

            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->string('status', 20)->default('queued'); // queued|sending|sent|partial|failed
            $table->string('batch_id')->nullable();          // channel's own dispatch reference

            $table->unsignedBigInteger('cost')->default(0);      // Toman debited up front
            $table->unsignedBigInteger('refunded')->default(0);  // Toman returned for failures

            $table->string('error')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_campaigns');
    }
};
