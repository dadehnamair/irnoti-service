<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer phonebook groups (docs/starter.md §14 / §17). Each row is mirrored to
 * the customer's own Melipayamak panel (Contacts.asmx/AddGroup) when they have
 * one; `remote_id` is the Melipayamak GroupID, `sync_status` tracks the mirror.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('remote_id')->nullable(); // Melipayamak GroupID
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('show_to_child')->default(false);
            $table->unsignedInteger('contact_count')->default(0);

            $table->string('sync_status')->default('local'); // local | synced | error
            $table->text('sync_error')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->unique(['user_id', 'remote_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_groups');
    }
};
