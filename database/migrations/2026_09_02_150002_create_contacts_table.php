<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer phonebook contacts (docs/starter.md §14 / §17). Mirrored to the
 * customer's own Melipayamak panel (Contacts.asmx/AddContact2 / ChangeContact2);
 * `remote_id` is the Melipayamak ContactID. Melipayamak has no delete — a
 * removed contact is set inactive there (ChangeContact2 contactStatus=1) and
 * then deleted locally.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('remote_id')->nullable(); // Melipayamak ContactID
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile');
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->string('nickname')->nullable();
            $table->string('gender')->nullable(); // female | male
            $table->date('birth_date')->nullable();
            $table->text('description')->nullable();

            $table->string('sync_status')->default('local'); // local | synced | error
            $table->text('sync_error')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'mobile']);
            $table->unique(['user_id', 'remote_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
