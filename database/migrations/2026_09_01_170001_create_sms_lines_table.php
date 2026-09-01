<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated SMS lines catalogue (docs/starter.md §9 / §10). Every field an
 * admin might change is a column here — nothing about a line is hard-coded in
 * the views. The public /lines page groups these by `prefix`, filters by
 * `digits` / `line_type`, and sorts by `price`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_lines', function (Blueprint $table) {
            $table->id();

            // Grouping / identity
            $table->string('prefix');                 // "1000" | "3000" | "021" | "9821" …
            $table->string('operator')->nullable();   // مگفا | آسیاتک | رایتل … (اپراتور)
            $table->string('number')->nullable();      // full number when a specific one is offered
            $table->unsignedTinyInteger('digits');     // تعداد ارقام

            $table->string('line_type')->default('shared'); // shared | dedicated | international …
            $table->boolean('is_rond')->default(false);     // رند / غیررند

            // Pricing — Toman. reseller_price is the نمایندگی price.
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('reseller_price')->nullable();
            $table->unsignedBigInteger('compare_at_price')->nullable(); // strike-through

            $table->string('description')->nullable();
            $table->json('features')->nullable();       // امکانات

            // وضعیت فروش: available | reserved | sold
            $table->string('sale_status')->default('available');
            $table->boolean('requires_inquiry')->default(false); // نیازمند استعلام
            $table->boolean('is_active')->default(true);         // وضعیت فعال

            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sale_status']);
            $table->index('prefix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_lines');
    }
};
