<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin approval gate + per-user SMS panel credentials (docs/starter.md §39 / §12).
 *
 * A customer only gets access to the panel features (send SMS, buy a line) after
 * an admin has reviewed the identity profile AND the uploaded documents and
 * flipped the account to "active". The customer's own Melipayamak panel
 * credentials are stored here by the admin so our panel can talk to the
 * customer's account (credit, single send).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Final account approval by the admin (docs/starter.md §39).
            $table->timestamp('approved_at')->nullable()->after('profile_completed_at');

            // Documents get their own review stage: pending | approved | rejected.
            $table->string('documents_status')->default('pending')->after('approved_at');
            $table->timestamp('documents_reviewed_at')->nullable()->after('documents_status');
            $table->string('documents_reject_reason')->nullable()->after('documents_reviewed_at');

            // The customer's own Melipayamak panel login (set by the admin after
            // approval). Password is stored encrypted via the model cast.
            $table->string('sms_username')->nullable()->after('documents_reject_reason');
            $table->text('sms_password')->nullable()->after('sms_username');
            $table->string('sms_sender')->nullable()->after('sms_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'approved_at',
                'documents_status',
                'documents_reviewed_at',
                'documents_reject_reason',
                'sms_username',
                'sms_password',
                'sms_sender',
            ]);
        });
    }
};
