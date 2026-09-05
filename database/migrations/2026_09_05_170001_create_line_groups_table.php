<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-prefix landing pages for dedicated SMS lines (docs/lines-landing.md).
 * One row per line family (خطوط ۳۰۰۰، ۰۲۱، …) — carries the marketing copy,
 * SEO metadata and line-specific FAQ that the public /lines/{group} page shows
 * next to that prefix's own purchase variants and bundles. Fully admin-edited
 * from the Filament panel; nothing here is hard-coded in the views.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_groups', function (Blueprint $table) {
            $table->id();

            $table->string('slug')->unique();        // route key — defaults to the prefix
            $table->string('prefix')->unique();      // join key to sms_lines.prefix

            $table->string('title');                 // H1 / hero title
            $table->string('tagline')->nullable();   // hero sub-line
            $table->text('body')->nullable();        // long copy (Markdown)

            $table->json('features')->nullable();    // «ویژگی‌ها» bullet list
            $table->json('use_cases')->nullable();   // «مناسب چه کسب‌وکارهایی»
            $table->json('faqs')->nullable();        // [{q, a}] → FAQPage JSON-LD

            $table->string('seo_title')->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->string('og_image')->nullable();

            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_groups');
    }
};
