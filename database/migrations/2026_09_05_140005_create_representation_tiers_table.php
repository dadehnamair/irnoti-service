<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-defined sales-representation tiers ("پنل نمایندگی") shown on the
 * public /representation page. docs/sales-representation.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representation_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('investment_amount')->nullable(); // Toman
            $table->unsignedTinyInteger('commission_percent')->nullable();
            $table->json('benefits')->nullable();
            $table->text('requirements')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representation_tiers');
    }
};
