<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            // ── Reference ─────────────────────────────────────────────────
            $table->string('reference')->unique() ->comment('Human-readable booking ref e.g. PX-20240115-0042');

            // ── Service ───────────────────────────────────────────────────
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();

            // ── Vehicle ───────────────────────────────────────────────────
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();

            // ── Schedule ──────────────────────────────────────────────────
            $table->dateTime('scheduled_at')->comment('Combined booking_date + booking_time as a single datetime');

            // ── Customer ──────────────────────────────────────────────────
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // ── Booking Source ────────────────────────────────────────────
            $table->foreignId('booking_source_id')->nullable()->constrained('booking_sources')->nullOnDelete();

            // ── Status ────────────────────────────────────────────────────
            $table->foreignId('booking_status_id')->nullable()->constrained('booking_statuses')->nullOnDelete();
            

            $table->text('cancellation_reason')->nullable();

            // ── Notes ─────────────────────────────────────────────────────
            $table->text('customer_notes')->nullable()->comment('Any notes the customer added');
            $table->text('staff_notes')->nullable()->comment('Internal notes added by staff');

            // ── Timestamps ────────────────────────────────────────────────
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};