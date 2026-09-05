<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The public marketing "امکانات" (features) catalogue — feeds the landing
 * page's #features teaser and the dedicated /features showcase page. Distinct
 * from `features` (the dashboard sidebar gating catalogue, App\Support\FeatureCatalog):
 * this table is pure marketing copy, fully admin-managed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_features', function (Blueprint $table) {
            $table->id();
            $table->string('icon')->nullable();
            $table->string('title');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->default('other');
            $table->string('badge')->nullable();
            $table->string('href')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_features');
    }
};
