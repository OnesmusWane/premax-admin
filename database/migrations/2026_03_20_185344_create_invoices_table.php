<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique()->comment('e.g. INV-001');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete() ->comment('The booking this invoice was generated from');
            $table->foreignId('checklist_id')
                  ->nullable()
                  ->constrained('vehicle_checklists')
                  ->nullOnDelete()
                  ->comment('The vehicle checklist completed before this service');

            // Amounts
            $table->unsignedInteger('subtotal')->default(0)->comment('KES before tax');
            $table->unsignedTinyInteger('vat_percent')->default(16);
            $table->unsignedInteger('vat_amount')->default(0);
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('total')->default(0);

            // Payment
            $table->enum('payment_method', ['cash', 'mpesa', 'card', 'bank_transfer', 'other'])
                  ->nullable();
            $table->string('mpesa_reference')->nullable();
            $table->enum('status', ['draft', 'pending', 'paid', 'partial', 'cancelled'])
                  ->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('total');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};