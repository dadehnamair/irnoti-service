<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Key/value store for the public-site "base info" (brand, colours, contact,
     * SEO, socials) — see docs/starter.md §67. `config/theme.php` keeps the
     * hard defaults; App\Providers\AppServiceProvider overlays whatever rows
     * exist here on top of `config('theme.*')` at boot, so the views never
     * change and the site can be re-themed from the admin panel.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string | text | color | bool | int | url
            $table->string('group')->default('general'); // general | contact | theme | seo | social
            $table->string('label')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
