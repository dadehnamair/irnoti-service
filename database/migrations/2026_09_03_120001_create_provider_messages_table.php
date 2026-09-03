<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local mirror of the customer's message archive as the SMS provider reports it
 * (docs/starter.md §14) — the «پیام‌ها» menu. The SyncProviderMessagesJob pulls
 * دریافتی (incoming) + ارسالی (outgoing) on page open and upserts here, keyed by
 * the provider message id, so the pages render from our DB and survive the
 * provider being slow or down.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('direction', 10);            // inbox | sent
            $table->string('provider_msg_id');          // provider MsgID

            $table->string('sender')->nullable();
            $table->string('receiver')->nullable();
            $table->text('body')->nullable();
            $table->unsignedSmallInteger('parts')->default(1);

            $table->unsignedInteger('rec_count')->default(0);
            $table->unsignedInteger('rec_success')->default(0);
            $table->unsignedInteger('rec_failed')->default(0);

            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'direction', 'provider_msg_id']);
            $table->index(['user_id', 'direction', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_messages');
    }
};
