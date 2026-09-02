<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When a group's contacts were last pulled from Melipayamak (docs/starter.md §17).
 * Group import and per-group contact import are now separate steps.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_groups', function (Blueprint $table) {
            $table->timestamp('contacts_synced_at')->nullable()->after('synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('contact_groups', function (Blueprint $table) {
            $table->dropColumn('contacts_synced_at');
        });
    }
};
