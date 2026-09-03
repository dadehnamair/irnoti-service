<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-recipient outcome of a {@see messenger_campaigns} row (docs/starter.md §91)
 * — one line per number / chat id, so the customer and the admin can see exactly
 * which recipients failed and why.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('messenger_campaign_id')->constrained()->cascadeOnDelete();

            $table->string('to');
            $table->string('type', 10)->default('mobile');   // mobile | chat
            $table->string('status', 10)->default('queued');  // queued | sent | failed
            $table->string('provider_ref')->nullable();
            $table->string('error')->nullable();
            $table->timestamps();

            $table->unique(['messenger_campaign_id', 'to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_recipients');
    }
};
