<?php

use App\Models\SmsPackage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The «بازارچه» catalogue (docs/starter.md §15). One row per installable
 * add-on — an external integration (ایرپلاس) or an internal capability
 * (کارت ویزیت، منشی پیامکی). Each row names a handler class (see
 * config/marketplace.php) and its own pricing. Admin-managed like {@see SmsPackage}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_apps', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();          // route key
            $table->string('name');
            $table->string('vendor')->nullable();      // «ایرپلاس»
            $table->string('category')->default('other')->index(); // integration|messaging|card|tool|other
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();   // markdown
            $table->string('icon')->nullable();        // uploaded logo path
            $table->string('accent_color')->nullable();

            $table->string('handler');                 // key in config('marketplace.handlers')

            $table->string('billing_type')->default('free'); // free|one_time|subscription
            $table->unsignedBigInteger('price')->default(0); // Toman
            $table->string('billing_period')->nullable();    // monthly|yearly
            $table->unsignedInteger('trial_days')->nullable();

            $table->json('config_schema')->nullable(); // [{key,label,type,required,help,placeholder,secret}]
            $table->json('capabilities')->nullable();  // feature keys this app unlocks

            $table->boolean('is_active')->default(false); // «بزودی» → زنده
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->string('docs_url')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_apps');
    }
};
