<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legal-entity (شخص حقوقی) registration on top of the natural-person profile
 * (docs/starter.md §26). The account type is chosen at the top of profile
 * wizard step 1. For a legal account we also capture the company registration
 * data (شناسه ملی / شماره ثبت / کد اقتصادی …), the signing representative's
 * role, and the company gazette documents — the representative's own identity
 * fields (first_name / national_code / ID images) are still filled in as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // individual | legal — natural person vs. registered company.
            $table->string('account_type', 20)->default('individual')->after('status');

            // Company registration data (docs/starter.md §26) — only for account_type = legal.
            $table->string('company_type')->nullable()->after('company');            // نوع شخصیت حقوقی
            $table->string('company_national_id', 20)->nullable()->after('company_type');       // شناسه ملی
            $table->string('company_registration_number', 40)->nullable()->after('company_national_id'); // شماره ثبت
            $table->string('company_registered_at', 20)->nullable()->after('company_registration_number'); // تاریخ ثبت (شمسی، متنی)
            $table->string('company_economic_code', 30)->nullable()->after('company_registered_at');     // کد اقتصادی
            $table->string('company_phone', 30)->nullable()->after('company_economic_code');             // تلفن شرکت
            $table->string('company_postal_code', 20)->nullable()->after('company_phone');               // کد پستی شرکت
            $table->text('company_address')->nullable()->after('company_postal_code');                    // نشانی شرکت
            $table->string('rep_role')->nullable()->after('company_address');        // سمت نماینده/امضاکننده

            // Company gazette documents — stored on the private "local" disk.
            $table->string('company_registration_doc')->nullable()->after('identity_doc_image'); // آگهی تأسیس / روزنامه رسمی
            $table->string('company_changes_doc')->nullable()->after('company_registration_doc'); // آگهی آخرین تغییرات
            $table->json('company_extra_docs')->nullable()->after('company_changes_doc');         // مدارک اضافه (چند فایل)

            $table->index('account_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_type']);
            $table->dropColumn([
                'account_type',
                'company_type',
                'company_national_id',
                'company_registration_number',
                'company_registered_at',
                'company_economic_code',
                'company_phone',
                'company_postal_code',
                'company_address',
                'rep_role',
                'company_registration_doc',
                'company_changes_doc',
                'company_extra_docs',
            ]);
        });
    }
};
