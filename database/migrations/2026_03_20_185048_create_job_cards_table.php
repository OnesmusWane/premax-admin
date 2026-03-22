<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('job_cards', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique()->comment('e.g. JC-20240115-001');
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('service_name')->comment('Snapshot at time of job creation');
            $table->foreignId('assigned_to')->nullable()->constrained('admin_users')->nullOnDelete();

            // Kanban stage
            $table->enum('stage', ['waiting', 'washing', 'repair', 'quality_check', 'done'])
                  ->default('waiting');

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('job_cards'); }
};