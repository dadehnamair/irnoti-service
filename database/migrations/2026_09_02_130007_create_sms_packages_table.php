<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A one-off SMS credit bundle for sale (docs/starter.md §12) — e.g. «۱۰٬۰۰۰ پیامک».
 * Separate from subscription Plans; buying one adds to users.sms_credit. Admin-
 * managed like {@see Plan}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('sms_count');
            $table->unsignedBigInteger('price');                  // Toman
            $table->unsignedBigInteger('compare_at_price')->nullable();
            $table->string('badge_label')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_packages');
    }
};
