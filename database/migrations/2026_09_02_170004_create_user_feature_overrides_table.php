<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user exceptions to the group grants (docs/starter.md §15) — «کم و زیاد
 * کردن برای هر کاربر». `mode` = grant (add a feature the group lacks) or revoke
 * (take one away the group has).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_feature_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->string('mode')->default('grant'); // grant | revoke
            $table->timestamps();
            $table->unique(['user_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_feature_overrides');
    }
};
