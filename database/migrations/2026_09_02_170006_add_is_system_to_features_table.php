<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `is_system` marks a built-in dashboard page (خلاصه حساب، کیف پول، …) — it is
 * always visible once `is_active`, regardless of the access group. Only the
 * «بزودی» catalogue items are gated by groups / per-user overrides.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
