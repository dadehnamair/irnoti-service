<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public leads submitted from /representation — reviewed manually by the
 * admin (no self-service payment/activation). docs/sales-representation.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representation_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('representation_tier_id')->nullable()->constrained('representation_tiers')->nullOnDelete();
            $table->string('full_name');
            $table->string('mobile');
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('company_name')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('pending'); // pending | contacted | approved | rejected
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representation_applications');
    }
};
