<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns `users` into the customer account table (docs/starter.md §26 / §48).
 * Registration starts with just a verified mobile; the identity profile
 * (docs/starter.md §26 fields) and an optional password are filled in later
 * from the account dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Login identity — mobile + OTP is the primary path (docs/starter.md §27).
            $table->string('mobile', 20)->nullable()->unique()->after('email');
            $table->timestamp('mobile_verified_at')->nullable()->after('mobile');

            // pending | active | suspended | blocked (docs/starter.md §39)
            $table->string('status')->default('pending')->after('mobile_verified_at');
            $table->timestamp('last_login_at')->nullable()->after('status');

            // Identity profile (docs/starter.md §26) — all optional, completed in steps.
            $table->string('first_name')->nullable()->after('last_login_at');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('company')->nullable()->after('last_name');
            $table->string('phone', 30)->nullable()->after('company'); // تلفن ثابت
            $table->string('country')->default('ایران')->after('phone');
            $table->string('province')->nullable()->after('country');
            $table->string('city')->nullable()->after('province');
            $table->text('address')->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('address');
            $table->string('national_code', 20)->nullable()->after('postal_code');
            $table->string('birth_cert_number', 20)->nullable()->after('national_code'); // ش.ش.
            $table->text('description')->nullable()->after('birth_cert_number');

            // Verification documents — stored on the private "local" disk.
            $table->string('national_card_image')->nullable()->after('description');
            $table->string('national_card_back_image')->nullable()->after('national_card_image');
            $table->string('identity_doc_image')->nullable()->after('national_card_back_image');

            // Plan / subscription snapshot for quick reads (source of truth: subscriptions).
            $table->foreignId('plan_id')->nullable()->after('identity_doc_image')->constrained('plans')->nullOnDelete();
            $table->timestamp('plan_expires_at')->nullable()->after('plan_id');
            $table->timestamp('profile_completed_at')->nullable()->after('plan_expires_at');

            $table->index('status');
        });

        // Mobile-first registration: no email or password up front. Both become
        // optional (email keeps its unique index, which allows multiple NULLs).
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'mobile', 'mobile_verified_at', 'status', 'last_login_at',
                'first_name', 'last_name', 'company', 'phone', 'country', 'province',
                'city', 'address', 'postal_code', 'national_code', 'birth_cert_number',
                'description', 'national_card_image', 'national_card_back_image',
                'identity_doc_image', 'plan_id', 'plan_expires_at', 'profile_completed_at',
            ]);
        });
    }
};
