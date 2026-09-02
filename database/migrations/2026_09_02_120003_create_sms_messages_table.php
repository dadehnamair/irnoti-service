<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Outbox log for single SMS sent by a customer from the panel (docs/starter.md
 * §12). Sends go out through the customer's own Melipayamak credentials; this
 * table is our record of what left, its provider recId and delivery outcome.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('to');
            $table->text('body');
            $table->unsignedTinyInteger('parts')->default(1);

            $table->string('rec_id')->nullable();   // provider message id
            $table->string('status')->default('queued'); // queued | sent | failed
            $table->string('error')->nullable();

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
