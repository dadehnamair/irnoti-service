<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Which features each access group grants (docs/starter.md §15). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_user_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_group_id')->constrained()->cascadeOnDelete();
            $table->unique(['feature_id', 'user_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_user_group');
    }
};
