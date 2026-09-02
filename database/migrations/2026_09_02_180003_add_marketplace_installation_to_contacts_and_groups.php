<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a marketplace app own the contacts / groups it pulls in (docs/starter.md §17).
 * `source` distinguishes the customer's manual phonebook (`manual`) from data a
 * marketplace app synced (`irplus`, …); `marketplace_installation_id` links the
 * row to the installation so its own page can list "its" groups and uninstalling
 * can optionally purge them.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['contact_groups', 'contacts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('source')->default('manual')->index();
                $table->foreignId('marketplace_installation_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['contact_groups', 'contacts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('marketplace_installation_id');
                $table->dropColumn('source');
            });
        }
    }
};
