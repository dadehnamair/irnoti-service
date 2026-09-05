<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Attach every existing dedicated line to its per-prefix landing page
 * (line_groups). The `prefix` column stays the display + fallback key; the FK
 * is what the landing page and the SmsLine::saving() hook keep in sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_lines', function (Blueprint $table) {
            $table->foreignId('line_group_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // Backfill: one line_group per distinct prefix, then point the lines at it.
        $prefixes = DB::table('sms_lines')->distinct()->pluck('prefix');

        foreach ($prefixes as $prefix) {
            if (blank($prefix)) {
                continue;
            }

            $slug = Str::slug((string) $prefix) ?: 'line-'.Str::lower(Str::random(6));

            $groupId = DB::table('line_groups')->where('prefix', $prefix)->value('id');

            if (! $groupId) {
                $groupId = DB::table('line_groups')->insertGetId([
                    'slug' => $slug,
                    'prefix' => $prefix,
                    'title' => 'خط اختصاصی '.$prefix,
                    'tagline' => 'خرید و فعال‌سازی خط '.$prefix.' برای اطلاع‌رسانی و تبلیغات کسب‌وکار.',
                    'is_active' => true,
                    'sort' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('sms_lines')->where('prefix', $prefix)->update(['line_group_id' => $groupId]);
        }
    }

    public function down(): void
    {
        Schema::table('sms_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('line_group_id');
        });
    }
};
