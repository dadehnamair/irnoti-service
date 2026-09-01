<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doc_article_id')->constrained('doc_articles')->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 40)->nullable();
            $table->boolean('is_required')->default(false);
            $table->string('description', 500)->nullable();
            $table->string('example')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_parameters');
    }
};
