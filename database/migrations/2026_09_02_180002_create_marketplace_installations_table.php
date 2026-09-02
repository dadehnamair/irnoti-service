<?php

use App\Models\MarketplaceApp;
use App\Models\Subscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A customer's installation of a {@see MarketplaceApp} (docs/starter.md §15).
 * Same shape as {@see Subscription}: unguessable `token` route key,
 * snapshot columns, status workflow, gateway payment columns. `config` holds the
 * per-user API credentials (encrypted); `settings` holds handler runtime state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_installations', function (Blueprint $table) {
            $table->id();
            $table->string('token', 24)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketplace_app_id')->constrained()->cascadeOnDelete();

            $table->string('status')->default('pending'); // pending|awaiting_payment|active|expired|suspended|cancelled

            $table->text('config')->nullable();   // encrypted:array — API credentials
            $table->json('settings')->nullable(); // handler runtime state (group_id, sync cursor…)

            // snapshot of the app's pricing at install time
            $table->unsignedBigInteger('price')->default(0);
            $table->string('billing_type')->default('free');
            $table->string('billing_period')->nullable();

            $table->string('payment_driver')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('reference_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamp('installed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->text('admin_note')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'marketplace_app_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_installations');
    }
};
