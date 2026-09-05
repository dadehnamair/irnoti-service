<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A LineOrder can now also represent a «باندل اختصاصی خط» purchase (line + SMS
 * credit + validity). The bundle values are snapshotted onto the order the same
 * way `line_label` / `price` already are, so the order stays meaningful even if
 * the bundle later changes or is removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('line_orders', function (Blueprint $table) {
            $table->foreignId('line_bundle_id')->nullable()->after('sms_line_id')->constrained()->nullOnDelete();
            $table->string('bundle_label')->nullable()->after('line_label');
            $table->unsignedInteger('sms_credit')->default(0)->after('price');
            $table->unsignedInteger('validity_days')->nullable()->after('sms_credit');
        });
    }

    public function down(): void
    {
        Schema::table('line_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('line_bundle_id');
            $table->dropColumn(['bundle_label', 'sms_credit', 'validity_days']);
        });
    }
};
