<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('sn')->unique()->comment('Serial number e.g. CHK-20240115-001');

            // Linked records
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();

            // Vehicle snapshot at time of checklist
            $table->string('reg_no');
            $table->string('make');
            $table->string('model');
            $table->string('colour')->nullable();

            // Fuel & mileage
            $table->enum('fuel_level', ['F', '3/4', '1/2', '1/4', 'E'])->nullable();
            $table->unsignedInteger('odometer')->nullable()->comment('km reading at check-in');

            // Payment method
            $table->enum('payment_option', ['mpesa', 'cash', 'insurance', 'cheque', 'other'])->nullable();

            // Checklist items stored as JSON
            // Each item: { "status": "ok|missing|damaged", "note": "" }
            $table->json('exterior')->nullable()->comment('Exterior checklist items');
            $table->json('interior')->nullable()->comment('Interior checklist items');
            $table->json('engine_compartment')->nullable()->comment('Engine compartment items');
            $table->json('extras')->nullable()->comment('Extras: jack, spare wheel, first aid etc');

            // Remarks
            $table->text('exterior_remarks')->nullable();
            $table->text('interior_remarks')->nullable();

            // Release info
            $table->string('released_from')->nullable();
            $table->string('released_to')->nullable();
            $table->string('released_by')->nullable();
            $table->string('received_by')->nullable();
            $table->string('released_by_tel')->nullable();
            $table->string('received_by_tel')->nullable();
            $table->string('received_by_id')->nullable()->comment('National ID of receiver');

            // Signatures (base64 stored)
            $table->text('release_signature')->nullable();
            $table->text('receive_signature')->nullable();

            // Status
            $table->enum('status', ['check_in', 'check_out'])->default('check_in');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_checklists');
    }
};