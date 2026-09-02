<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multiple dedicated sender numbers per customer (docs/starter.md §12). An SMS
 * panel account can own several سرشماره; the list is pulled from the
 * GetUserNumbers API and cached here. `sms_sender` keeps its meaning as the
 * customer's selected default line.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('sms_numbers')->nullable()->after('sms_sender');
            $table->timestamp('sms_numbers_synced_at')->nullable()->after('sms_numbers');
        });

        Schema::table('sms_messages', function (Blueprint $table) {
            $table->string('from')->nullable()->after('to'); // sender line the message went out on
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sms_numbers', 'sms_numbers_synced_at']);
        });

        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropColumn('from');
        });
    }
};
