<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // ── Auth link ─────────────────────────────────────────────────
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('Links to users table for login credentials');

            // ── Department ────────────────────────────────────────────────
            $table->foreignId('department_id')
                  ->nullable()
                  ->constrained('departments')
                  ->nullOnDelete();

            // ── Identity ──────────────────────────────────────────────────
            $table->string('employee_number')->unique()
                  ->comment('e.g. EMP-001');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('national_id')->nullable()->unique();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('avatar_url')->nullable();

            // ── Role & Position ───────────────────────────────────────────
            $table->enum('role', ['super_admin', 'admin', 'staff'])->default('staff');
            $table->string('job_title')->nullable()
                  ->comment('e.g. Lead Technician, Customer Relations Manager');
            $table->text('bio')->nullable();

            // ── Employment dates ──────────────────────────────────────────
            $table->date('start_date')->nullable()
                  ->comment('Date employment began');
            $table->date('end_date')->nullable()
                  ->comment('Date employment ended — null if still active');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])
                  ->default('full_time');
            $table->enum('employment_status', ['active', 'on_leave', 'suspended', 'terminated', 'resigned'])
                  ->default('active');
            $table->text('termination_reason')->nullable();

            // ── Compensation ──────────────────────────────────────────────
            $table->unsignedInteger('salary')->nullable()
                  ->comment('Monthly gross salary in KES');
            $table->enum('pay_frequency', ['monthly', 'weekly', 'daily'])->default('monthly');

            // ── Emergency contact ─────────────────────────────────────────
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();

            // ── Show on website ───────────────────────────────────────────
            $table->boolean('show_on_website')->default(false)
                  ->comment('Whether to display on the About/Team page');
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};