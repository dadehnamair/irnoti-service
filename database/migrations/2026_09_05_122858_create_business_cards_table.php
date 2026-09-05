<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_cards', function (Blueprint $table) {
            $table->id();
            $table->string('token', 32)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('domain_id')->constrained()->restrictOnDelete();
            $table->string('tier')->default('standard');
            $table->string('code');
            $table->string('title')->nullable();
            $table->string('position')->nullable();
            $table->string('company')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('telegram')->nullable();
            $table->string('instagram')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->json('socials')->nullable();
            $table->json('products')->nullable();
            $table->string('theme_color')->nullable();
            $table->string('status')->default('draft');
            $table->bigInteger('price')->default(0);
            $table->text('admin_note')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->string('transaction_id')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('payment_driver')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'code']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_cards');
    }
};
