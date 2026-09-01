<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Line purchase requests (docs/starter.md §11). The project has no payment
 * gateway or customer auth yet, so an order is captured here with contact
 * details and moves through the §11 status workflow, managed by the admin
 * from the Filament panel. The public "track" page is keyed by `token`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_orders', function (Blueprint $table) {
            $table->id();
            $table->string('token', 32)->unique(); // public tracking id

            $table->foreignId('sms_line_id')->nullable()->constrained('sms_lines')->nullOndelete();

            // Snapshot of the line at order time (survives line edits/deletes)
            $table->string('line_label');
            $table->unsignedBigInteger('price');

            // Buyer
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->string('company')->nullable();
            $table->string('desired_number')->nullable(); // شماره موردنظر کاربر
            $table->text('note')->nullable();

            // pending | awaiting_payment | paid | processing | completed | rejected | cancelled
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_orders');
    }
};
