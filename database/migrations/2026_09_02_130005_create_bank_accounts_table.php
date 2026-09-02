<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Company bank accounts shown to the customer on the "ثبت فیش" screen
 * (docs/starter.md §22). Admin-managed from Filament.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name');
            $table->string('owner_name');
            $table->string('card_number')->nullable();
            $table->string('sheba')->nullable();          // IBAN without the leading "IR"
            $table->string('account_number')->nullable();
            $table->string('note')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
