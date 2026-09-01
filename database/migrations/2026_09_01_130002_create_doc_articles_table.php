<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doc_category_id')->constrained('doc_categories')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('http_method', 12)->nullable();
            $table->string('endpoint')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->timestamps();

            $table->unique(['doc_category_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_articles');
    }
};
