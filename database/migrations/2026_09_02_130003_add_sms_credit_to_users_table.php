<?php

use App\Models\PackageOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Purchased SMS credit as a plain piece count (docs/starter.md §12). Topped up by
 * SMS package orders ({@see PackageOrder}) and by activating a plan
 * that bundles `sms_count`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('sms_credit')->default(0)->after('sms_sender');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sms_credit');
        });
    }
};
