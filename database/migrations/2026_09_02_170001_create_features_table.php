<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Panel-feature catalogue (docs/starter.md §15). One row per item in the customer
 * dashboard mega-menu. Seeded from App\Support\FeatureCatalog and edited from the
 * Filament admin panel. `is_active` is the global «بزودی» switch — a feature only
 * shows as a real link once it is switched on AND granted to the account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('group_key')->index();
            $table->string('group_label');
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('route')->nullable();   // Laravel route name, when the page exists
            $table->string('url')->nullable();     // external URL alternative
            $table->text('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
