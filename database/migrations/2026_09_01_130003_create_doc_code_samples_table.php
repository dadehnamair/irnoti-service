<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_code_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doc_article_id')->constrained('doc_articles')->cascadeOnDelete();
            $table->string('language', 32);
            $table->string('label')->nullable();
            $table->longText('code');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_code_samples');
    }
};
