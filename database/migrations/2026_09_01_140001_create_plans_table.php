<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();

            // Badge shown on the pricing card — style maps to the .pill CSS class
            $table->string('badge_label')->nullable();
            $table->string('badge_style')->default('neutral'); // neutral | primary | dark

            // Prices are stored in Toman. compare_at_* is the pre-discount price
            // rendered with a strike-through when present.
            $table->unsignedBigInteger('price_monthly')->default(0);
            $table->unsignedBigInteger('price_yearly')->nullable();
            $table->unsignedBigInteger('compare_at_monthly')->nullable();
            $table->unsignedBigInteger('compare_at_yearly')->nullable();

            // Quotas (nullable = not advertised / unlimited)
            $table->unsignedInteger('duration_days')->nullable();
            $table->unsignedBigInteger('sms_count')->nullable();
            $table->unsignedInteger('lines_count')->nullable();
            $table->unsignedInteger('users_count')->nullable();

            $table->json('features')->nullable();

            $table->string('cta_label')->default('انتخاب پلن');
            $table->string('cta_style')->default('btn-secondary'); // btn-primary | btn-secondary
            $table->string('cta_url')->nullable();

            $table->string('color')->nullable(); // optional accent hex

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
