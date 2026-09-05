<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «باندل اختصاصی خط» (docs/lines-landing.md) — a curated product sold from a
 * line landing page: a dedicated line + a chunk of SMS credit + a validity
 * window, at a single price. Bought through the existing LineOrder flow (see
 * the bundle columns added to line_orders).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_bundles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('line_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sms_line_id')->nullable()->constrained()->nullOnDelete(); // concrete variant, null = generic

            $table->string('slug')->unique();
            $table->string('title');
            $table->string('description')->nullable();

            $table->unsignedInteger('sms_credit')->default(0);      // bundled SMS count
            $table->unsignedInteger('validity_days')->nullable();   // اعتبار زمانی

            // Pricing — Toman.
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('compare_at_price')->nullable();

            $table->string('badge_label')->nullable();
            $table->string('badge_style')->nullable();

            $table->json('features')->nullable();

            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_bundles');
    }
};
